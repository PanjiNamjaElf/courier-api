<?php

namespace App\Http\Requests\Courier;

use App\Models\Courier;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Unique;

/**
 * Update parsial: semua field opsional, tetapi field yang dikirim harus valid.
 * Dipakai untuk PUT dan PATCH.
 */
class UpdateCourierRequest extends StoreCourierRequest
{
    /**
     * Aturan validasi untuk mengubah kurir.
     *
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'min:3', 'max:100', 'regex:'.Courier::NAME_PATTERN],
            'phone' => ['sometimes', 'required', 'string', 'regex:'.Courier::PHONE_PATTERN, $this->uniqueRule('phone')],
            'email' => ['sometimes', 'nullable', 'string', 'email:rfc', 'max:255', $this->uniqueRule('email')],
            'level' => ['sometimes', 'required', 'integer', 'between:'.Courier::MIN_LEVEL.','.Courier::MAX_LEVEL],
            'registered_at' => ['sometimes', 'required', 'date_format:Y-m-d', 'before_or_equal:today'],
        ];
    }

    /**
     * Kurir yang sedang diubah boleh tetap memakai nomor HP dan email miliknya sendiri.
     */
    protected function uniqueRule(string $column): Unique
    {
        return parent::uniqueRule($column)->ignore($this->route('courier'));
    }
}
