<?php

namespace App\Http\Requests\Courier;

use App\Models\Courier;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class StoreCourierRequest extends FormRequest
{
    /**
     * Aturan validasi untuk menambah kurir.
     *
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:100', 'regex:'.Courier::NAME_PATTERN],
            'phone' => ['required', 'string', 'regex:'.Courier::PHONE_PATTERN, $this->uniqueRule('phone')],
            'email' => ['nullable', 'string', 'email:rfc', 'max:255', $this->uniqueRule('email')],
            'level' => ['required', 'integer', 'between:'.Courier::MIN_LEVEL.','.Courier::MAX_LEVEL],
            'registered_at' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ];
    }

    /**
     * Rapikan input sebelum divalidasi (spasi di awal/akhir teks sudah dibuang oleh middleware).
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => Str::squish($this->input('name'))]);
        }

        if (is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower($this->input('email'))]);
        }
    }

    /**
     * Aturan unik untuk kolom kurir, dipakai juga oleh request update.
     */
    protected function uniqueRule(string $column): Unique
    {
        return Rule::unique(Courier::class, $column);
    }
}
