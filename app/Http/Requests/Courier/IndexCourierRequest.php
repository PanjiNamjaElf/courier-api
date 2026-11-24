<?php

namespace App\Http\Requests\Courier;

use App\Models\Courier;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class IndexCourierRequest extends FormRequest
{
    public const int DEFAULT_PER_PAGE = 15;

    public const int MAX_PER_PAGE = 100;

    public const array SORTABLE_COLUMNS = ['name', 'registered_at'];

    /**
     * Daftar level dipisah koma, contoh "2,3" (level berupa satu digit 1-5).
     */
    private const string LEVEL_LIST_PATTERN = '/^\s*[1-5]\s*(,\s*[1-5]\s*)*$/';

    /**
     * Aturan validasi untuk parameter query daftar kurir.
     *
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100', 'regex:'.Courier::NAME_PATTERN],
            'level' => ['nullable', 'string', 'regex:'.self::LEVEL_LIST_PATTERN],
            'sort' => ['nullable', Rule::in(self::SORTABLE_COLUMNS)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'between:1,'.self::MAX_PER_PAGE],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Level dari "?level=2,3" sebagai bilangan bulat tanpa duplikat.
     *
     * @return array<int, int>
     */
    public function levels(): array
    {
        return Str::of($this->validated('level'))
            ->explode(',')
            ->map(fn (string $level) => (int) trim($level))
            ->unique()
            ->values()
            ->all();
    }

    public function sortColumn(): string
    {
        return $this->validated('sort') ?? 'name';
    }

    public function sortDirection(): string
    {
        return $this->validated('direction') ?? 'asc';
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? self::DEFAULT_PER_PAGE);
    }
}
