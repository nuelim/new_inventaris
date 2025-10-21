<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssetTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|string|in:IT,Kendaraan,Bangunan,Mesin,Lainnya',
            'depreciation_years' => 'required|integer|min:1|max:50',
            'requires_calibration' => 'boolean',
            'requires_maintenance' => 'boolean',
            'is_active' => 'boolean',
        ];

        if ($this->isMethod('post')) {
            $rules['code'] = 'required|string|max:50|unique:asset_types,code';
        } else {
            $rules['code'] = 'required|string|max:50|unique:asset_types,code,' . $this->asset_type->id;
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama tipe aset wajib diisi.',
            'code.required' => 'Kode tipe aset wajib diisi.',
            'code.unique' => 'Kode tipe aset sudah digunakan.',
            'category.required' => 'Kategori wajib dipilih.',
            'category.in' => 'Kategori tidak valid.',
            'depreciation_years.required' => 'Umur depresiasi wajib diisi.',
            'depreciation_years.min' => 'Umur depresiasi minimal 1 tahun.',
            'depreciation_years.max' => 'Umur depresiasi maksimal 50 tahun.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'phone.required' => 'Telepon wajib diisi.',
            'address.required' => 'Alamat wajib diisi.',
            'city.required' => 'Kota wajib diisi.',
            'province.required' => 'Provinsi wajib diisi.',
            'contact_person.required' => 'Nama kontak wajib diisi.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'requires_calibration' => $this->has('requires_calibration'),
            'requires_maintenance' => $this->has('requires_maintenance'),
            'is_active' => $this->has('is_active'),
        ]);
    }
}