<?php

/*
|--------------------------------------------------------------------------
| Pesan Validasi (Bahasa Indonesia)
|--------------------------------------------------------------------------
|
| Hanya aturan yang dipakai API ini yang diterjemahkan. Aturan lain memakai
| pesan bahasa Inggris dari fallback locale.
|
*/

return [
    'before_or_equal' => ':Attribute harus berupa tanggal sebelum atau sama dengan :date.',
    'between' => [
        'numeric' => ':Attribute harus bernilai antara :min sampai :max.',
    ],
    'date_format' => ':Attribute harus sesuai format :format.',
    'email' => ':Attribute harus berupa alamat email yang valid.',
    'in' => ':Attribute yang dipilih tidak valid. Pilihan yang tersedia: :values.',
    'integer' => ':Attribute harus berupa bilangan bulat.',
    'max' => [
        'string' => ':Attribute maksimal berisi :max karakter.',
    ],
    'min' => [
        'numeric' => ':Attribute minimal bernilai :min.',
        'string' => ':Attribute minimal berisi :min karakter.',
    ],
    'regex' => 'Format :attribute tidak valid.',
    'required' => ':Attribute wajib diisi.',
    'string' => ':Attribute harus berupa teks.',
    'unique' => ':Attribute sudah digunakan.',

    'custom' => [
        'name' => [
            'regex' => ':Attribute hanya boleh berisi huruf, spasi, titik, apostrof, dan tanda hubung.',
        ],
        'phone' => [
            'regex' => ':Attribute harus berupa nomor HP Indonesia, contoh 081234567890 atau +6281234567890.',
        ],
        'search' => [
            'regex' => ':Attribute hanya boleh berisi huruf, spasi, titik, apostrof, dan tanda hubung.',
        ],
        'level' => [
            'regex' => ':Attribute harus berupa angka 1 sampai 5 yang dipisahkan koma, contoh 2,3.',
        ],
    ],

    'values' => [
        'registered_at' => [
            'today' => 'hari ini',
        ],
    ],

    'attributes' => [
        'name' => 'nama',
        'phone' => 'nomor HP',
        'email' => 'email',
        'level' => 'level',
        'registered_at' => 'tanggal pendaftaran',
        'search' => 'kata kunci pencarian',
        'sort' => 'kolom urutan',
        'direction' => 'arah urutan',
        'per_page' => 'jumlah data per halaman',
        'page' => 'halaman',
    ],
];
