<?php

use App\Filament\Resources\ProjectResource\Pages\ViewProject;
use App\Filament\Resources\ProjectResource\RelationManagers\BudgetPositionsRelationManager;
use App\Filament\Resources\ProjectResource\RelationManagers\CostEntriesRelationManager;
use App\Models\Company;
use App\Models\CostEntry;
use App\Models\Project;
use Cafm\ReceiptCapture\ReceiptCaptureAction;
use Cafm\ReceiptCapture\Support\ReceiptTextParser;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(fn () => requiresPlugin('receipt-capture'));

it('parses amount, dates, invoice number, tax rate, vat id, iban and supplier from OCR text', function () {
    $text = <<<'TXT'
    Elektro Müller GmbH
    Hauptstraße 1, 50667 Köln
    USt-IdNr. DE 123456789
    Rechnung Nr. RE-2026-0815
    Rechnungsdatum: 03.09.2026
    Zahlbar bis 17.09.2026
    Position 1  Kabelkanal   250,00 €
    Position 2  Montage     1.000,00 €
    Netto 1.250,00 €
    19 % MwSt. 237,50 €
    Gesamtbetrag 1.487,50 EUR
    IBAN DE02 1203 0000 0000 2020 51
    TXT;

    $parser = ReceiptTextParser::make($text, [7 => 'Elektro Müller GmbH', 9 => 'Gloria']);
    $fields = $parser->fields();

    expect($fields['gross_amount'])->toBe(1487.5)
        ->and($fields['booked_on'])->toBe('2026-09-03')
        ->and($fields['due_on'])->toBe('2026-09-17')
        ->and($fields['invoice_number'])->toBe('RE-2026-0815')
        ->and($fields['tax_rate'])->toBe(19)
        ->and($fields['supplier_vat_id'])->toBe('DE123456789')
        ->and($fields['supplier_iban'])->toBe('DE02120300000000202051')
        ->and($fields['supplier_id'])->toBe(7)
        ->and($parser->supplierNameSuggestion())->toBe('Elektro Müller GmbH')
        ->and(ReceiptTextParser::make("Invoice\nInvoice number 2C04A255-0027\nDate of issue April 20, 2026\nExample Corp\n548 Market Street\nBill to\nAmount due €74.31")->supplierNameSuggestion())->toBe('Example Corp')
        ->and(ReceiptCaptureAction::netAmount(1487.5, 19))->toBe(1250.0);

    expect(ReceiptTextParser::make("Kassenbon\nSumme 12.99\n7% MwSt")->fields())
        ->gross_amount->toBe(12.99)
        ->tax_rate->toBe(7)
        ->booked_on->toBeNull();

    $parser = ReceiptTextParser::make('x');

    expect($parser->parseDate('Invoice date: April 20, 2026'))->toBe('2026-04-20')
        ->and($parser->parseDate('Datum: 20. April 2026'))->toBe('2026-04-20')
        ->and($parser->parseDate('Due: 3rd Sept 2026'))->toBe('2026-09-03')
        ->and($parser->parseDate('Belegdatum 20 Apr 2026'))->toBe('2026-04-20')
        ->and($parser->parseDate('Datum 20/04/26'))->toBe('2026-04-20')
        ->and($parser->parseDate('kein Datum hier'))->toBeNull()
        ->and(ReceiptTextParser::make("Rechnungsdatum: April 20, 2026\nZahlbar bis May 4, 2026")->fields())->booked_on->toBe('2026-04-20')
        ->and(ReceiptTextParser::make("Rechnungsdatum: April 20, 2026\nZahlbar bis May 4, 2026")->dueOn())->toBe('2026-05-04');
});

it('captures a receipt with upload, supplier creation and categorisation into a cost entry', function () {
    Storage::fake('uploads');
    loginAsAdmin();
    $project = Project::factory()->create(['budget' => 50000]);
    $position = $project->budgetPositions()->create(['name' => 'Elektro', 'budget' => 20000, 'sort' => 1]);

    $component = Livewire::test(CostEntriesRelationManager::class, ['ownerRecord' => $project, 'pageClass' => ViewProject::class]);

    $component
        ->callAction(TestAction::make('captureReceipt')->table(), [
            'receipt_file' => UploadedFile::fake()->create('rechnung.pdf', 120, 'application/pdf'),
            'supplier_id' => Company::query()->create(['name' => 'Elektro Müller GmbH'])->getKey(),
            'receipt_type' => 'expense',
            'invoice_number' => 'RE-2026-0815',
            'booked_on' => '2026-09-03',
            'due_on' => '2026-09-17',
            'category_id' => $position->getKey(),
            'tax_rate' => '19',
            'gross_amount' => 1487.5,
        ])
        ->assertHasNoActionErrors();

    $entry = CostEntry::query()->firstOrFail();

    expect($entry->company->name)->toBe('Elektro Müller GmbH')
        ->and($entry->vendor)->toBe('Elektro Müller GmbH')
        ->and((float) $entry->amount)->toBe(1487.5)
        ->and((float) $entry->net_amount)->toBe(1250.0)
        ->and((float) $entry->tax_rate)->toBe(19.0)
        ->and($entry->invoice_number)->toBe('RE-2026-0815')
        ->and($entry->due_on?->toDateString())->toBe('2026-09-17')
        ->and($entry->budget_position_id)->toBe($position->getKey())
        ->and(Storage::disk('uploads')->exists($entry->receipt))->toBeTrue()
        ->and($entry->mediaFiles('receipt')->exists())->toBeTrue()
        ->and($position->actual())->toBe(1487.5);
});

it('prefills empty fields from recognised text and saves without a document', function () {
    loginAsAdmin();
    $project = Project::factory()->create();
    $supplier = Company::query()->create(['name' => 'Gloria Brandschutz']);

    $component = Livewire::test(CostEntriesRelationManager::class, ['ownerRecord' => $project, 'pageClass' => ViewProject::class])
        ->mountAction(TestAction::make('captureReceipt')->table())
        ->setActionData(['ocr_text' => "Gloria Brandschutz\nRechnungsnummer 4711-A\nDatum 01.09.2026\nGesamtbetrag 119,00 €\n19 % USt"]);

    $component->assertActionDataSet(function (array $data) use ($supplier): void {
        expect((float) $data['gross_amount'])->toBe(119.0)
            ->and((string) $data['booked_on'])->toStartWith('2026-09-01')
            ->and($data['invoice_number'])->toBe('4711-A')
            ->and((int) $data['tax_rate'])->toBe(19)
            ->and($data['supplier_id'])->toBe($supplier->getKey());
    });

    $component
        ->setActionData(['no_document' => true, 'receipt_type' => 'credit_note'])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    $entry = CostEntry::query()->firstOrFail();

    expect((float) $entry->amount)->toBe(-119.0)
        ->and($entry->receipt)->toBeNull()
        ->and($entry->receipt_type)->toBe('credit_note')
        ->and($entry->company->is($supplier))->toBeTrue();
});

it('creates a supplier from the drawer select and offers to create an unknown supplier from OCR', function () {
    loginAsAdmin();
    $project = Project::factory()->create();

    Livewire::test(CostEntriesRelationManager::class, ['ownerRecord' => $project, 'pageClass' => ViewProject::class])
        ->callAction([TestAction::make('captureReceipt')->table(), TestAction::make('createOption')->schemaComponent('supplier_id')], ['name' => 'Neue Firma GmbH', 'role' => 'supplier'])
        ->assertHasNoActionErrors();

    expect(Company::query()->where('name', 'Neue Firma GmbH')->exists())->toBeTrue();

    $component = Livewire::test(CostEntriesRelationManager::class, ['ownerRecord' => $project, 'pageClass' => ViewProject::class])
        ->mountAction(TestAction::make('captureReceipt')->table())
        ->setActionData(['ocr_text' => "Unbekannte Werkstatt GmbH\nMusterweg 3\nUSt-IdNr. DE987654321\nRechnung Nr. 77\nGesamtbetrag 59,50 €"]);

    $component->assertActionDataSet(function (array $data): void {
        expect($data['supplier_id'] ?? null)->toBeNull()
            ->and($data['supplier_suggestion'])->toBe('Unbekannte Werkstatt GmbH')
            ->and($data['supplier_vat_id'])->toBe('DE987654321');
    });

    $component
        ->callAction(TestAction::make('createSuggestedSupplier')->schemaComponent('supplier_suggestion_actions'))
        ->assertHasNoActionErrors();

    $created = Company::query()->where('name', 'Unbekannte Werkstatt GmbH')->firstOrFail();

    expect($created->vat_id)->toBe('DE987654321');

    $component->assertActionDataSet(fn (array $data) => expect($data['supplier_id'])->toBe($created->getKey())->and($data['supplier_suggestion'])->toBeNull());
});

it('captures a receipt for a fixed budget position from the positions list and previews stored receipts', function () {
    Storage::fake('uploads');
    loginAsAdmin();
    $project = Project::factory()->create();
    $position = $project->budgetPositions()->create(['name' => 'Sanitär', 'budget' => 5000, 'sort' => 1]);

    Livewire::test(BudgetPositionsRelationManager::class, ['ownerRecord' => $project, 'pageClass' => ViewProject::class])
        ->callAction(TestAction::make('captureReceipt')->table($position), [
            'receipt_file' => UploadedFile::fake()->create('bon.pdf', 20, 'application/pdf'),
            'receipt_type' => 'expense',
            'booked_on' => '2026-09-04',
            'tax_rate' => '7',
            'gross_amount' => 107,
        ])
        ->assertHasNoActionErrors()
        ->callAction(TestAction::make('details')->table($position))
        ->assertHasNoActionErrors();

    $entry = CostEntry::query()->firstOrFail();

    expect($entry->budget_position_id)->toBe($position->getKey())
        ->and((float) $entry->net_amount)->toBe(100.0)
        ->and($position->actual())->toBe(107.0);

    $withoutReceipt = $project->costEntries()->create(['kind' => 'actual', 'amount' => 5, 'booked_on' => now()]);

    Livewire::test(CostEntriesRelationManager::class, ['ownerRecord' => $project, 'pageClass' => ViewProject::class])
        ->assertSeeHtml("mountAction('previewReceipt'")
        ->assertActionVisible(TestAction::make('previewReceipt')->table($entry))
        ->assertActionHidden(TestAction::make('previewReceipt')->table($withoutReceipt))
        ->callAction(TestAction::make('previewReceipt')->table($entry))
        ->assertHasNoActionErrors();
});
