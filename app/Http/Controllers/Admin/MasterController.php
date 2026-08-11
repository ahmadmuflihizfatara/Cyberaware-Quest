<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\Fasilitator;
use App\Models\Instrumen;
use App\Models\Lokasi;
use App\Models\Materi;
use App\Models\Mitra;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\ProgramPkm;
use App\Models\Reward;
use App\Models\Sekolah;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * CRUD data master.
 *
 * ponytail: sepuluh resource dengan bentuk halaman yang identik dikendalikan
 * satu definisi tabel + satu pasang view. Pecah jadi controller sendiri hanya
 * kalau sebuah resource butuh aturan yang tidak muat di definisi ini.
 */
class MasterController extends Controller
{
    /** @return array<string,array> */
    public static function definisi(): array
    {
        return [
            'pengguna' => [
                'judul' => 'Pengguna',
                'model' => Pengguna::class,
                'pk' => 'id_pengguna',
                'kolom' => ['nama_pengguna' => 'Nama', 'email' => 'Email', 'status_akun' => 'Status'],
                'field' => [
                    'nama_pengguna' => ['label' => 'Nama', 'tipe' => 'text', 'rules' => 'required|string|max:150'],
                    'email' => ['label' => 'Email', 'tipe' => 'text', 'rules' => 'required|email|max:150'],
                    'password' => ['label' => 'Kata sandi', 'tipe' => 'password', 'rules' => 'nullable|string|min:8', 'virtual' => true],
                    'status_akun' => ['label' => 'Status akun', 'tipe' => 'select', 'opsi' => ['aktif', 'nonaktif'], 'rules' => 'required|in:aktif,nonaktif'],
                    'peran' => ['label' => 'Peran', 'tipe' => 'multiselect', 'virtual' => true,
                        'sumber' => [Peran::class, 'id_peran', 'nama_peran'], 'rules' => 'array'],
                ],
            ],
            'mitra' => [
                'judul' => 'Mitra',
                'model' => Mitra::class,
                'pk' => 'id_mitra',
                'kolom' => ['nama_mitra' => 'Nama', 'jenis_mitra' => 'Jenis', 'kontak_email' => 'Email', 'status_mitra' => 'Status'],
                'field' => [
                    'nama_mitra' => ['label' => 'Nama mitra', 'tipe' => 'text', 'rules' => 'required|string|max:150'],
                    'jenis_mitra' => ['label' => 'Jenis', 'tipe' => 'select', 'opsi' => ['sekolah', 'instansi', 'komunitas', 'lainnya'], 'rules' => 'required'],
                    'kontak_email' => ['label' => 'Email kontak', 'tipe' => 'text', 'rules' => 'nullable|email|max:150'],
                    'kontak_telepon' => ['label' => 'Telepon', 'tipe' => 'text', 'rules' => 'nullable|string|max:30'],
                    'alamat' => ['label' => 'Alamat', 'tipe' => 'textarea', 'rules' => 'nullable|string|max:255'],
                    'status_mitra' => ['label' => 'Status', 'tipe' => 'select', 'opsi' => ['aktif', 'nonaktif'], 'rules' => 'required'],
                ],
            ],
            'sekolah' => [
                'judul' => 'Sekolah',
                'model' => Sekolah::class,
                'pk' => 'id_sekolah',
                'with' => ['mitra'],
                'kolom' => ['mitra.nama_mitra' => 'Mitra', 'npsn' => 'NPSN', 'jenjang' => 'Jenjang', 'kota' => 'Kota'],
                'field' => [
                    'id_mitra' => ['label' => 'Mitra', 'tipe' => 'select', 'sumber' => [Mitra::class, 'id_mitra', 'nama_mitra'], 'rules' => 'required|integer'],
                    'npsn' => ['label' => 'NPSN', 'tipe' => 'text', 'rules' => 'nullable|string|max:20'],
                    'jenjang' => ['label' => 'Jenjang', 'tipe' => 'select', 'opsi' => ['SD', 'SMP', 'SMA', 'SMK', 'lainnya'], 'rules' => 'nullable'],
                    'kota' => ['label' => 'Kota', 'tipe' => 'text', 'rules' => 'nullable|string|max:100'],
                ],
            ],
            'lokasi' => [
                'judul' => 'Lokasi / Venue',
                'model' => Lokasi::class,
                'pk' => 'id_lokasi',
                'with' => ['sekolah.mitra'],
                'kolom' => ['nama_lokasi' => 'Nama lokasi', 'sekolah.mitra.nama_mitra' => 'Sekolah', 'kapasitas_ruang' => 'Kapasitas'],
                'field' => [
                    'id_sekolah' => ['label' => 'Sekolah', 'tipe' => 'select', 'sumber' => [Sekolah::class, 'id_sekolah', 'nama'], 'rules' => 'required|integer'],
                    'nama_lokasi' => ['label' => 'Nama lokasi', 'tipe' => 'text', 'rules' => 'required|string|max:150'],
                    'kapasitas_ruang' => ['label' => 'Kapasitas', 'tipe' => 'number', 'rules' => 'nullable|integer|min:1'],
                ],
            ],
            'fasilitator' => [
                'judul' => 'Fasilitator',
                'model' => Fasilitator::class,
                'pk' => 'id_fasilitator',
                'with' => ['pengguna'],
                'kolom' => ['nama_fasilitator' => 'Nama', 'email' => 'Email', 'bidang_keahlian' => 'Bidang', 'pengguna.email' => 'Akun'],
                'field' => [
                    'nama_fasilitator' => ['label' => 'Nama', 'tipe' => 'text', 'rules' => 'required|string|max:150'],
                    'email' => ['label' => 'Email', 'tipe' => 'text', 'rules' => 'nullable|email|max:150'],
                    'bidang_keahlian' => ['label' => 'Bidang keahlian', 'tipe' => 'text', 'rules' => 'nullable|string|max:150'],
                    'id_pengguna' => ['label' => 'Akun pengguna', 'tipe' => 'select', 'kosong' => '— tanpa akun —',
                        'sumber' => [Pengguna::class, 'id_pengguna', 'email'], 'rules' => 'nullable|integer'],
                ],
            ],
            'materi' => [
                'judul' => 'Materi',
                'model' => Materi::class,
                'pk' => 'id_materi',
                'kolom' => ['judul_materi' => 'Judul', 'kategori' => 'Kategori'],
                'field' => [
                    'judul_materi' => ['label' => 'Judul', 'tipe' => 'text', 'rules' => 'required|string|max:150'],
                    'kategori' => ['label' => 'Kategori', 'tipe' => 'select', 'rules' => 'required',
                        'opsi' => ['phishing', 'kata_sandi', 'privasi_digital', 'keamanan_perangkat', 'etika_medsos', 'lainnya']],
                    'deskripsi' => ['label' => 'Deskripsi', 'tipe' => 'textarea', 'rules' => 'nullable|string'],
                ],
            ],
            'badge' => [
                'judul' => 'Badge',
                'model' => Badge::class,
                'pk' => 'id_badge',
                'kolom' => ['nama_badge' => 'Nama', 'kriteria' => 'Kriteria'],
                'field' => [
                    'nama_badge' => ['label' => 'Nama badge', 'tipe' => 'text', 'rules' => 'required|string|max:100'],
                    'deskripsi' => ['label' => 'Deskripsi', 'tipe' => 'textarea', 'rules' => 'nullable|string'],
                    'kriteria' => ['label' => 'Kriteria', 'tipe' => 'textarea', 'rules' => 'nullable|string'],
                ],
            ],
            'reward' => [
                'judul' => 'Reward',
                'model' => Reward::class,
                'pk' => 'id_reward',
                'kolom' => ['nama_reward' => 'Nama', 'biaya_poin' => 'Biaya poin', 'stok' => 'Stok', 'status_aktif' => 'Aktif'],
                'field' => [
                    'nama_reward' => ['label' => 'Nama reward', 'tipe' => 'text', 'rules' => 'required|string|max:150'],
                    'biaya_poin' => ['label' => 'Biaya poin', 'tipe' => 'number', 'rules' => 'required|integer|min:1'],
                    'stok' => ['label' => 'Stok', 'tipe' => 'number', 'rules' => 'required|integer|min:0'],
                    'status_aktif' => ['label' => 'Aktif', 'tipe' => 'checkbox', 'rules' => 'boolean'],
                ],
            ],
            'program' => [
                'judul' => 'Program PkM',
                'model' => ProgramPkm::class,
                'pk' => 'id_program',
                'kolom' => ['nama_program' => 'Nama', 'tanggal_mulai' => 'Mulai', 'tanggal_selesai' => 'Selesai', 'status_program' => 'Status'],
                'field' => [
                    'nama_program' => ['label' => 'Nama program', 'tipe' => 'text', 'rules' => 'required|string|max:150'],
                    'deskripsi' => ['label' => 'Deskripsi', 'tipe' => 'textarea', 'rules' => 'nullable|string'],
                    'tanggal_mulai' => ['label' => 'Tanggal mulai', 'tipe' => 'date', 'rules' => 'nullable|date'],
                    'tanggal_selesai' => ['label' => 'Tanggal selesai', 'tipe' => 'date', 'rules' => 'nullable|date|after_or_equal:tanggal_mulai'],
                    'status_program' => ['label' => 'Status', 'tipe' => 'select', 'opsi' => ['berjalan', 'selesai', 'dibatalkan'], 'rules' => 'required'],
                    'mitra' => ['label' => 'Mitra terlibat', 'tipe' => 'multiselect', 'virtual' => true,
                        'sumber' => [Mitra::class, 'id_mitra', 'nama_mitra'], 'rules' => 'array'],
                ],
            ],
            'instrumen' => [
                'judul' => 'Instrumen',
                'model' => Instrumen::class,
                'pk' => 'id_instrumen',
                'kolom' => ['kode_instrumen' => 'Kode', 'nama_instrumen' => 'Nama', 'tipe_instrumen' => 'Tipe'],
                'field' => [
                    'kode_instrumen' => ['label' => 'Kode', 'tipe' => 'text', 'rules' => 'required|string|max:30'],
                    'nama_instrumen' => ['label' => 'Nama', 'tipe' => 'text', 'rules' => 'required|string|max:150'],
                    'tipe_instrumen' => ['label' => 'Tipe', 'tipe' => 'select', 'opsi' => ['demografi', 'tes', 'kuesioner'], 'rules' => 'required'],
                    'deskripsi' => ['label' => 'Deskripsi', 'tipe' => 'textarea', 'rules' => 'nullable|string'],
                ],
                'tautan' => ['route' => 'admin.instrumen.versi', 'label' => 'Kelola versi'],
            ],
        ];
    }

    public function index(Request $request, string $resource)
    {
        $def = $this->def($resource);
        $q = $def['model']::query();
        if (! empty($def['with'])) {
            $q->with($def['with']);
        }

        return view('admin.master', [
            'resource' => $resource,
            'def' => $def,
            'baris' => $q->orderBy($def['pk'])->paginate(20),
            'opsi' => $this->opsiRelasi($def),
            'edit' => $request->filled('edit')
                ? $def['model']::with($this->relasiVirtual($def))->find($request->integer('edit'))
                : null,
        ]);
    }

    public function store(Request $request, string $resource)
    {
        $def = $this->def($resource);
        $data = $this->validasi($request, $def);
        $model = $def['model']::create($this->kolomNyata($def, $data));
        $this->simpanVirtual($def, $model, $data);

        return redirect()->route('admin.master.index', $resource)->with('sukses', $def['judul'].' ditambahkan.');
    }

    public function update(Request $request, string $resource, int $id)
    {
        $def = $this->def($resource);
        $model = $def['model']::findOrFail($id);
        $data = $this->validasi($request, $def, $id);
        $model->update($this->kolomNyata($def, $data, $model));
        $this->simpanVirtual($def, $model, $data);

        return redirect()->route('admin.master.index', $resource)->with('sukses', $def['judul'].' diperbarui.');
    }

    public function destroy(string $resource, int $id)
    {
        $def = $this->def($resource);

        try {
            $def['model']::findOrFail($id)->delete();
        } catch (QueryException $e) {
            return back()->withErrors(['hapus' => 'Data tidak dapat dihapus karena masih dipakai catatan lain (aksi referensial RESTRICT).']);
        }

        return back()->with('sukses', $def['judul'].' dihapus.');
    }

    // ---------------------------------------------------------------- private

    private function def(string $resource): array
    {
        $semua = self::definisi();
        abort_unless(isset($semua[$resource]), 404);

        return $semua[$resource];
    }

    private function validasi(Request $request, array $def, ?int $id = null): array
    {
        $rules = [];
        foreach ($def['field'] as $nama => $f) {
            $rules[$nama] = $f['rules'] ?? 'nullable';
        }

        if (isset($rules['password']) && $id === null) {
            $rules['password'] = 'required|string|min:8';
        }

        // Unik pada kolom yang punya UNIQUE di basis data, tetap dijaga ganda.
        foreach (['email' => 'email', 'kode_instrumen' => 'kode_instrumen', 'nama_badge' => 'nama_badge', 'npsn' => 'npsn'] as $kolom => $_) {
            if (isset($rules[$kolom])) {
                $tabel = (new $def['model'])->getTable();
                $rules[$kolom] .= '|unique:'.$tabel.','.$kolom.($id ? ','.$id.','.$def['pk'] : '');
            }
        }

        return $request->validate($rules);
    }

    private function kolomNyata(array $def, array $data, $model = null): array
    {
        $out = [];
        foreach ($def['field'] as $nama => $f) {
            if (! empty($f['virtual'])) {
                continue;
            }
            $nilai = $data[$nama] ?? null;
            if ($f['tipe'] === 'checkbox') {
                $nilai = (bool) $nilai;
            }
            if ($f['tipe'] === 'select' && $nilai === '') {
                $nilai = null;
            }
            $out[$nama] = $nilai;
        }

        if (isset($def['field']['password'])) {
            if (! empty($data['password'])) {
                $out['kata_sandi_hash'] = Hash::make($data['password']);
            }
        }

        return $out;
    }

    private function simpanVirtual(array $def, $model, array $data): void
    {
        if (isset($def['field']['peran'])) {
            $model->peran()->sync($data['peran'] ?? []);
        }
        if (isset($def['field']['mitra'])) {
            $model->mitra()->sync($data['mitra'] ?? []);
        }
    }

    private function relasiVirtual(array $def): array
    {
        return array_values(array_intersect(['peran', 'mitra'], array_keys($def['field'])));
    }

    /** @return array<string,array<int,object>> */
    private function opsiRelasi(array $def): array
    {
        $out = [];
        foreach ($def['field'] as $nama => $f) {
            if (empty($f['sumber'])) {
                continue;
            }
            [$kelas, $key, $label] = $f['sumber'];
            $out[$nama] = $kelas::all()->map(fn ($m) => (object) ['id' => $m->{$key}, 'label' => $m->{$label}]);
        }

        return $out;
    }

    public static function nilaiKolom($baris, string $jalur)
    {
        foreach (explode('.', $jalur) as $bagian) {
            $baris = $baris?->{$bagian};
        }

        return $baris;
    }
}
