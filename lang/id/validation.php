<?php

return [
    'required' => ':attribute wajib diisi.',
    'string' => ':attribute harus berupa teks.',
    'integer' => ':attribute harus berupa angka.',
    'numeric' => ':attribute harus berupa angka.',
    'boolean' => ':attribute harus benar atau salah.',
    'date' => ':attribute harus berupa tanggal yang valid.',
    'image' => ':attribute harus berupa gambar.',
    'max' => [
        'string' => ':attribute tidak boleh lebih dari :max karakter.',
        'file' => ':attribute tidak boleh lebih dari :max kilobita.',
        'numeric' => ':attribute tidak boleh lebih dari :max.',
    ],
    'min' => [
        'string' => ':attribute minimal harus :min karakter.',
        'numeric' => ':attribute minimal harus :min.',
    ],
    'email' => ':attribute harus berupa alamat email yang valid.',
    'unique' => ':attribute sudah terdaftar sebelumnya.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'in' => ':attribute yang dipilih tidak valid.',
    'exists' => ':attribute yang dipilih tidak ditemukan.',
    'accepted' => ':attribute harus disetujui.',
    'after' => ':attribute harus setelah :date.',
    'after_or_equal' => ':attribute harus setelah atau sama dengan :date.',

    'attributes' => [
        'email' => 'Email',
        'no_hp' => 'Nomor HP',
        'password' => 'Kata sandi',
        'nama_pengguna' => 'Nama pengguna',
        'nama_lengkap' => 'Nama lengkap',
        'npm' => 'NPM/NIS',
        'asal_sekolah' => 'Asal sekolah',
        'alamat_domisili' => 'Alamat domisili',
        'no_ktp' => 'Nomor KTP',
        'foto_profil' => 'Foto profil',
    ],
];
