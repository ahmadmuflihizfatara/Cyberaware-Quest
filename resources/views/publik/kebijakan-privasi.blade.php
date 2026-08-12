@extends('layouts.publik')
@section('judul', 'Kebijakan Privasi')

@section('isi')
<div class="mx-auto max-w-2xl">
    <p class="eyebrow">Kebijakan</p>
    <h1 class="text-3xl font-bold">Kebijakan Privasi Pendaftaran</h1>
    <p class="mt-2 text-sm text-slate-500">Versi 1.0</p>

    <div class="prose-ringkas mt-6 text-sm text-slate-600">
        <p>
            Data yang Anda isikan saat mendaftar kegiatan (nama, nomor HP, afiliasi sekolah/instansi,
            data demografi, kehadiran, jawaban pre-test/post-test, aktivitas, dan evaluasi) digunakan
            semata untuk keperluan penyelenggaraan dan pelaporan program Pengabdian kepada Masyarakat
            CyberAware Quest × PkM ImpactLab.
        </p>
        <p>
            Data tidak dibagikan ke pihak ketiga di luar kebutuhan pelaporan program. Hasil evaluasi
            penyelenggaraan dapat dilaporkan secara agregat; bila panitia menetapkan mode kuesioner
            anonim pada suatu kegiatan, identitas Anda tidak ditampilkan pada laporan evaluasi tersebut.
        </p>
        <p>
            Anda dapat menghubungi panitia untuk pertanyaan mengenai data yang tersimpan atas nama Anda.
        </p>
    </div>

    <a href="javascript:history.back()" class="btn btn-ghost mt-6">&larr; Kembali</a>
</div>
@endsection
