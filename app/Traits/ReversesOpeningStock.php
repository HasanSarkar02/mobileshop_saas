<?php

namespace App\Traits;

use App\Actions\Inventory\ReverseOpeningStockAction;
use App\Models\StockAdjustment;
use Illuminate\Support\Facades\Auth;

trait ReversesOpeningStock
{
    public bool   $showReverseModal = false;
    public ?int   $reversingEntryId = null;
    public string $reversalReason   = '';

    public function openReverseModal(int $id): void
    {
        $this->requirePermission('inventory.create');

        if (! Auth::user()->isOwner()) {
            abort(403, 'Only the shop owner can reverse opening stock entries.');
        }

        $this->reversingEntryId = $id;
        $this->reversalReason   = '';
        $this->showReverseModal = true;
    }

    public function confirmReverse(ReverseOpeningStockAction $action): void
    {
        $this->validate(['reversalReason' => 'required|string|min:10'], [
            'reversalReason.required' => 'Please explain the correction.',
            'reversalReason.min'      => 'Please explain the correction in at least 10 characters — this becomes part of the permanent audit trail.',
        ]);

        $shop     = Auth::user()->shop()->withoutGlobalScopes()->findOrFail(Auth::user()->shop_id);
        $original = StockAdjustment::where('shop_id', $shop->id)->findOrFail($this->reversingEntryId);

        try {
            $result = $action->execute($shop, $original, $this->reversalReason, Auth::user());

            $this->showReverseModal = false;
            $message = 'Entry reversed. Stock and accounting records have been corrected.';
            if ($result['warning']) {
                $message .= ' ⚠ ' . $result['warning'];
            }
            $this->dispatch('notify', ['type' => 'success', 'message' => $message]);
            $this->afterReversal();

        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /** Override in the host component to refresh whatever list it shows. No-op by default. */
    protected function afterReversal(): void {}
}