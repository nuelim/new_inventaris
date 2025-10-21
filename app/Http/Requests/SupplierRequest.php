<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupplierRequest extends FormRequest
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
            'contact_person' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:50',
            'payment_terms' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ];

        if ($this->isMethod('post')) {
            $rules['code'] = 'required|string|max:50|unique:suppliers,code';
            $rules['email'] = 'required|email|max:255|unique:suppliers,email';
        } else {
            $rules['code'] = 'required|string|max:50|unique:suppliers,code,' . $this->supplier->id;
            $rules['email'] = 'required|email|max:255|unique:suppliers,email,' . $this->supplier->id;
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama supplier wajib diisi.',
            'code.required' => 'Kode supplier wajib diisi.',
            'code.unique' => 'Kode supplier sudah digunakan.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'contact_person.required' => 'Nama kontak wajib diisi.',
            'phone.required' => 'Telepon wajib diisi.',
            'address.required' => 'Alamat wajib diisi.',
            'city.required' => 'Kota wajib diisi.',
            'province.required' => 'Provinsi wajib diisi.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'is_active' => $this->has('is_active'),
        ]);
    }
}