<?php

namespace App\Filament\Resources\StockMovementResource\Pages;

use App\Filament\Resources\StockMovementResource;
use Filament\Actions;
use App\Models\Item;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Actions\Action;
use Filament\Support\Exceptions\Halt;

class CreateStockMovement extends CreateRecord
{
    protected static string $resource = StockMovementResource::class;

    // item stock will be updated automatically after creating a stock movement
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure the item_id is set before proceeding
        if (isset($data['item_id'])) {
            $item = Item::find($data['item_id']);
            if ($item) {
                // Update the stock based on the transaction type
                if ($data['transaction_type'] === 'STOCK_IN') {
                    $item->increment('stock', $data['quantity']);
                } elseif ($data['transaction_type'] === 'STOCK_OUT') {
                    // reqeuire confirmation if minimum stock is reached
                    if ($item->stock <= $item->min_inventory_qty) {
                        Notification::make()
                            ->title('Low Stock Warning')
                            ->body("The stock for {$item->name} has reached or below the minimum level.")
                            ->warning()
                            ->duration(10000)
                            ->persistent()
                            ->send();
                        $item->decrement('stock', $data['quantity']);
                    } elseif ($item->stock < $data['quantity']) {
                        Notification::make()
                            ->title('Insufficient Stock')
                            ->body("The stock for {$item->name} is insufficient for this transaction.")
                            ->danger()
                            ->duration(10000)
                            ->persistent()
                            ->send();
                        throw new Halt();
                    } else {
                        $item->decrement('stock', $data['quantity']);
                    }
                }
            }
        }

        return $data;
    }
}
