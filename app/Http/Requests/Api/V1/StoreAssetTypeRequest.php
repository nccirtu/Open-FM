<?php

namespace App\Http\Requests\Api\V1;

use App\Models\AssetType;
use Illuminate\Validation\Rule;

class StoreAssetTypeRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allows('create', AssetType::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('asset_types', 'name')],
        ];
    }
}
