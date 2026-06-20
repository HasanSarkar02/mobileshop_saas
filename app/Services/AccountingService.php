<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class AccountingService
{
    /**
     * The ONLY way any module creates a journal entry. Every line must
     * reference an Account belonging to the same shop. Debits must equal
     * credits exactly (compared in integer cents — never raw floats), or
     * the whole entry is rejected before anything touches the database.
     *
     * @param  array<int, array{account_id: int, debit?: float, credit?: float, description?: string}>  $lines
     */
    public function postEntry(
        Shop $shop,
        string $description,
        array $lines,
        ?\DateTimeInterface $entryDate = null,
        ?Model $reference = null,
        ?int $branchId = null,
        ?User $actor = null,
    ): JournalEntry {
        $entryDate ??= now();

        $this->assertPeriodIsOpen($shop, $entryDate);
        $this->assertBalanced($lines);

        return DB::transaction(function () use ($shop, $description, $lines, $entryDate, $reference, $branchId, $actor) {
            $entry = JournalEntry::create([
                'shop_id' => $shop->id,
                'branch_id' => $branchId,
                'entry_number' => $this->nextEntryNumber($shop),
                'entry_date' => $entryDate,
                'description' => $description,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'created_by' => $actor?->id,
                'posted_at' => now(),
            ]);

            foreach ($lines as $line) {
                $this->assertAccountBelongsToShop($line['account_id'], $shop);

                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'description' => $line['description'] ?? null,
                ]);
            }

            return $entry->fresh('lines');
        });
    }

    /**
     * The ONLY way to correct a posted entry. Creates a brand new entry
     * with every line's debit/credit swapped, linked both ways to the
     * original. The original is never edited or deleted — bank-submitted
     * statements stay exactly as they were submitted.
     */
    public function reverseEntry(JournalEntry $original, string $reason, ?User $actor = null): JournalEntry
    {
        if ($original->isReversed()) {
            throw new RuntimeException("Journal entry {$original->entry_number} has already been reversed.");
        }

        $this->assertPeriodIsOpen($original->shop, now());

        $reversalLines = $original->lines->map(fn (JournalEntryLine $line) => [
            'account_id' => $line->account_id,
            'debit' => (float) $line->credit,
            'credit' => (float) $line->debit,
            'description' => $line->description,
        ])->all();

        return DB::transaction(function () use ($original, $reversalLines, $reason, $actor) {
            $reversal = $this->postEntry(
                shop: $original->shop,
                description: "Reversal of {$original->entry_number}: {$reason}",
                lines: $reversalLines,
                branchId: $original->branch_id,
                actor: $actor,
            );

            $reversal->update(['reverses_entry_id' => $original->id]);
            $original->update(['reversed_by_entry_id' => $reversal->id]);

            return $reversal;
        });
    }

    private function assertBalanced(array $lines): void
    {
        if (count($lines) < 2) {
            throw new InvalidArgumentException('A journal entry needs at least two lines.');
        }

        $totalDebitCents = 0;
        $totalCreditCents = 0;

        foreach ($lines as $line) {
            $debit = (float) ($line['debit'] ?? 0);
            $credit = (float) ($line['credit'] ?? 0);

            if ($debit > 0 && $credit > 0) {
                throw new InvalidArgumentException('A single line cannot have both a debit and a credit.');
            }

            if ($debit <= 0 && $credit <= 0) {
                throw new InvalidArgumentException('Every line needs a positive debit or credit amount.');
            }

            // Integer-cents comparison — never compare monetary floats directly,
            // floating point rounding can silently make a balanced entry look unbalanced.
            $totalDebitCents += (int) round($debit * 100);
            $totalCreditCents += (int) round($credit * 100);
        }

        if ($totalDebitCents !== $totalCreditCents) {
            $totalDebit = $totalDebitCents / 100;
            $totalCredit = $totalCreditCents / 100;
            throw new InvalidArgumentException("Journal entry does not balance: debit {$totalDebit} vs credit {$totalCredit}.");
        }
    }

    private function assertAccountBelongsToShop(int $accountId, Shop $shop): void
    {
        $account = Account::withoutGlobalScopes()
            ->where('id', $accountId)
            ->where('shop_id', $shop->id)
            ->first();

        if (! $account) {
            throw new InvalidArgumentException("Account [{$accountId}] does not belong to shop [{$shop->id}].");
        }

        if ($account->is_header) {
            throw new InvalidArgumentException("Account [{$account->name}] is a header/grouping account and cannot receive postings directly.");
        }

        if (! $account->is_active) {
            throw new InvalidArgumentException("Account [{$account->name}] is inactive and cannot receive postings.");
        }
    }

    private function assertPeriodIsOpen(Shop $shop, \DateTimeInterface $entryDate): void
    {
        if ($shop->books_locked_through && $entryDate <= $shop->books_locked_through) {
            $lockedThrough = $shop->books_locked_through->format('Y-m-d');
            $attempted = $entryDate->format('Y-m-d');
            throw new RuntimeException("Cannot post an entry dated {$attempted} — books are locked through {$lockedThrough}.");
        }
    }

    private function nextEntryNumber(Shop $shop): string
    {
        $year = now()->format('Y');
        $counterKey = "journal_entry_{$year}";

        DB::statement(
            'INSERT INTO shop_counters (shop_id, counter_key, current_value, created_at, updated_at)
             VALUES (?, ?, 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE current_value = current_value + 1, updated_at = NOW()',
            [$shop->id, $counterKey]
        );

        $sequence = DB::table('shop_counters')
            ->where('shop_id', $shop->id)
            ->where('counter_key', $counterKey)
            ->value('current_value');

        return sprintf('JE-%s-%05d', $year, $sequence);
    }
}