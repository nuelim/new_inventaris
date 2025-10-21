<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LocationRequest extends FormRequest
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
            'building' => 'required|string|max:255',
            'floor' => 'nullable|string|max:50',
            'room' => 'nullable|string|max:100',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:20',
            'manager_name' => 'nullable|string|max:255',
            'manager_email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
        ];

        if ($this->isMethod('post')) {
            $rules['code'] = 'required|string|max:50|unique:locations,code';
        } else {
            $rules['code'] = 'required|string|max:50|unique:locations,code,' . $this->location->id;
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama lokasi wajib diisi.',
            'code.required' => 'Kode lokasi wajib diisi.',
            'code.unique' => 'Kode lokasi sudah digunakan.',
            'building.required' => 'Nama gedung wajib diisi.',
            'address.required' => 'Alamat wajib diisi.',
            'city.required' => 'Kota wajib diisi.',
            'province.required' => 'Provinsi wajib diisi.',
            'manager_email.email' => 'Format email manager tidak valid.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'is_active' => $this->has('is_active'),
        ]);
    }
}