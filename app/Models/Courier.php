<?php

namespace App\Models;

use Database\Factories\CourierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['name', 'phone', 'email', 'level', 'registered_at'])]
class Courier extends Model
{
    /** @use HasFactory<CourierFactory> */
    use HasFactory;

    public const int MIN_LEVEL = 1;

    public const int MAX_LEVEL = 5;

    /**
     * Huruf (aksara apa pun), tanda diakritik, spasi, apostrof, titik, dan tanda hubung.
     */
    public const string NAME_PATTERN = '/^[\pL\pM\s\'.\-]+$/u';

    /**
     * Nomor HP Indonesia yang diawali 08, 628, atau +628.
     */
    public const string PHONE_PATTERN = '/^(\+62|62|0)8[0-9]{7,12}$/';

    /**
     * Atribut yang dikonversi tipenya.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'registered_at' => 'date',
        ];
    }

    /**
     * Cari kurir yang namanya mengandung setiap kata pada kata kunci, dengan urutan bebas.
     *
     * "budi agung" cocok dengan "Budiono Hadi Agung".
     */
    #[Scope]
    protected function searchName(Builder $query, string $search): void
    {
        Str::of($search)
            ->squish()
            ->explode(' ')
            ->filter()
            ->unique()
            ->each(fn (string $keyword) => $query->whereLike('name', "%{$keyword}%"));
    }

    /**
     * Batasi hasil hanya pada level yang diberikan.
     *
     * @param  array<int, int>  $levels
     */
    #[Scope]
    protected function levels(Builder $query, array $levels): void
    {
        $query->whereIn('level', $levels);
    }
}
