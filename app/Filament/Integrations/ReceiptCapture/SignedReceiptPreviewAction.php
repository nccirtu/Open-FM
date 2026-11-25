<?php

namespace App\Filament\Integrations\ReceiptCapture;

use App\Support\Uploads;
use Cafm\ReceiptCapture\ReceiptPreviewAction;

class SignedReceiptPreviewAction extends ReceiptPreviewAction
{
    public function getReceiptUrl(): ?string
    {
        return Uploads::url($this->getPath(), $this->evaluate($this->disk) ?? Uploads::receiptDisk());
    }
}
