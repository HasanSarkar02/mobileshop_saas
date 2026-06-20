<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'shop_id', 'branch_id', 'entry_number', 'entry_date', 'description',
    'reference_type', 'reference_id', 'reverses_entry_id', 'reversed_by_entry_id',
    'created_by', 'posted_at',
])]
class JournalEntry extends Model
{
    use HasFactory, BelongsToShop;

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'posted_at' => 'datetime',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function reverses(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reverses_entry_id');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversed_by_entry_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isReversed(): bool
    {
        return $this->reversed_by_entry_id !== null;
    }

    /**
     * Posted entries are immutable. The only legitimate update after
     * creation is linking a reversal — anything else is blocked, even
     * from tinker or a future careless controller.
     */
    public function save(array $options = [])
    {
        if ($this->exists) {
            $dirtyKeys = array_keys($this->getDirty());
            $allowedKeys = ['reverses_entry_id', 'reversed_by_entry_id'];
            $onlyLinkFields = count(array_diff($dirtyKeys, $allowedKeys)) === 0;

            if (! empty($dirtyKeys) && ! $onlyLinkFields) {
                throw new \RuntimeException('Posted journal entries are immutable. Use AccountingService::reverseEntry() to correct one.');
            }
        }

        return parent::save($options);
    }

    public function delete()
    {
        throw new \RuntimeException('Journal entries can never be deleted. Use AccountingService::reverseEntry() instead.');
    }
}