<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 28px 32px; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #0f172a; }
    h1 { font-size: 16px; margin: 0 0 2px; }
    p.sub { margin: 0 0 14px; color: #475569; font-size: 9px; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #0b1b3a; color: #fff; text-align: left; padding: 6px 8px; font-size: 8.5px; text-transform: uppercase; letter-spacing: .03em; }
    td { padding: 5px 8px; border-bottom: 1px solid #e2e8f0; }
    tr:nth-child(even) td { background: #f8fafc; }
    .kosong { padding: 16px 8px; color: #64748b; text-align: center; }
    .footer { margin-top: 10px; color: #94a3b8; font-size: 8px; }
</style>
</head>
<body>
    <h1>{{ $def['judul'] }}</h1>
    <p class="sub">
        {{ $namaKegiatan ? 'Kegiatan: '.$namaKegiatan : 'Seluruh kegiatan' }} &middot;
        Dibuat {{ $dibuatPada->translatedFormat('d F Y, H:i') }} WIB &middot;
        {{ $baris->count() }} baris &middot; CyberAware Quest PkM ImpactLab
    </p>

    <table>
        <thead>
            <tr>@foreach ($def['kolom'] as $label)<th>{{ $label }}</th>@endforeach</tr>
        </thead>
        <tbody>
        @forelse ($baris as $b)
            <tr>
                @foreach ($def['kolom'] as $kolom => $label)
                    <td>{{ $b->{$kolom} ?? '-' }}</td>
                @endforeach
            </tr>
        @empty
            <tr><td class="kosong" colspan="{{ count($def['kolom']) }}">Tidak ada data untuk filter ini.</td></tr>
        @endforelse
        </tbody>
    </table>

    <p class="footer">Data diambil langsung dari basis data sesuai filter yang dipilih saat pembuatan berkas ini.</p>
</body>
</html>
