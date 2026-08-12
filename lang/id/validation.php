<?php

return [
    'required' => ':attribute wajib diisi.',
    'string' => ':attribute harus berupa teks.',
    'max' => [
        'string' => ':attribute tidak boleh lebih dari :max karakter.',
    ],
    'min' => [
        'string' => ':attribute minimal harus :min karakter.',
    ],
    'email' => ':attribute harus berupa alamat email yang valid.',
    'unique' => ':attribute sudah terdaftar sebelumnya.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',

    'attributes' => [
        'email' => 'Email',
        'no_hp' => 'Nomor HP',
        'password' => 'Kata sandi',
        'nama_lengkap' => 'Nama lengkap',
    ],
];
