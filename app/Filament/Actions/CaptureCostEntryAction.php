<?php

namespace App\Filament\Actions;

use App\Enums\CompanyRole;
use App\Enums\CostEntryKind;
use App\Filament\CostCenterSelectField;
use App\Filament\Fm\Resources\CompanyResource;
use App\Filament\Integrations\ReceiptCapture\SignedReceiptPreviewAction;
use App\Models\BudgetPosition;
use App\Models\Company;
use App\Models\CostEntry;
use App\Models\Project;
use App\Services\EntityFolders;
use App\Support\Plugins\Plugins;
use App\Support\Uploads;
use Cafm\ReceiptCapture\ReceiptCaptureAction;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;

final class CaptureCostEntryAction
{
    public const PLUGIN = 'receipt-capture';

    public static function for(Model $owner, bool $fixedCategory = false): Action
    {
        if (! Plugins::active(self::PLUGIN)) {
            return PluginRequiredAction::make('captureReceipt', self::PLUGIN)->label(__('plugins.features.receipt_capture'));
        }

        $isProject = $owner instanceof Project;
        $storedFile = null;

        $action = ReceiptCaptureAction::make('captureReceipt')
            ->storeReceiptUsing(function (UploadedFile $file) use ($owner, &$storedFile): ?string {
                ['file' => $storedFile, 'path' => $path] = EntityFolders::storeReceipt($owner, $file);

                return $path;
            })
            ->supplierOptions(fn (): array => Company::query()->orderBy('name')->pluck('name', 'id')->all())
            ->supplierForm(CompanyResource::masterComponents(CompanyRole::Supplier))
            ->createSupplierUsing(function (array $data): string {
                abort_unless(Gate::allows('create', Company::class), 403);

                return Company::query()->create($data)->getKey();
            })
            ->hideCategory(! $isProject || $fixedCategory)
            ->extraComponents([CostCenterSelectField::make()])
            ->defaults(fn (?Model $record): array => $fixedCategory && $record instanceof BudgetPosition ? ['category_id' => $record->getKey(), 'cost_center_id' => $record->cost_center_id] : [])
            ->onSave(function (array $data, ?string $receiptPath) use ($owner, &$storedFile): CostEntry {
                $entry = self::store($owner, $data, $receiptPath);

                if ($storedFile !== null) {
                    EntityFolders::attachReceipt($entry, $storedFile);
                }

                return $entry;
            });

        $action->authorize(fn (): bool => Gate::allows('update', $owner));

        if ($isProject) {
            $action->categoryOptions(fn (): array => $owner->budgetPositions()->pluck('name', 'id')->all(), label: __('projects.costs.position'));
        }

        return $action;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function store(Model $owner, array $data, ?string $receiptPath): CostEntry
    {
        $gross = (float) $data['gross_amount'];
        $rate = is_numeric($data['tax_rate'] ?? null) ? (float) $data['tax_rate'] : null;
        $type = $data['receipt_type'] ?? 'expense';
        $company = isset($data['supplier_id']) ? Company::query()->find($data['supplier_id']) : null;

        return $owner->costEntries()->create([
            'budget_position_id' => $owner instanceof Project ? ($data['category_id'] ?? null) : null,
            'cost_center_id' => $data['cost_center_id'] ?? null,
            'company_id' => $company?->getKey(),
            'kind' => CostEntryKind::Actual,
            'receipt_type' => $type,
            'amount' => $type === 'expense' ? $gross : -$gross,
            'tax_rate' => $rate,
            'net_amount' => $rate === null ? null : ReceiptCaptureAction::netAmount($gross, $rate),
            'invoice_number' => $data['invoice_number'] ?? null,
            'booked_on' => $data['booked_on'],
            'due_on' => $data['due_on'] ?? null,
            'vendor' => $company?->name,
            'description' => $data['description'] ?? null,
            'receipt' => $receiptPath,
        ]);
    }

    public static function preview(): Action
    {
        if (Plugins::active(self::PLUGIN)) {
            return SignedReceiptPreviewAction::make()->path(fn (?CostEntry $record): ?string => $record?->receipt);
        }

        return Action::make('previewReceipt')
            ->label(__('projects.costs.receipt'))
            ->icon('heroicon-o-eye')
            ->visible(fn (?CostEntry $record): bool => filled($record?->receipt))
            ->url(fn (?CostEntry $record): ?string => filled($record?->receipt) ? Uploads::url($record->receipt, Uploads::receiptDisk()) : null)
            ->openUrlInNewTab();
    }
}
