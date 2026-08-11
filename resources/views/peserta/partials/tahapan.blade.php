@php
    $tautan = [
        'persetujuan' => ['peserta.persetujuan', [$p]],
        'pretest' => ['peserta.instrumen', [$p, 'pretest']],
        'kehadiran' => ['peserta.checkin', [$p]],
        'posttest' => ['peserta.instrumen', [$p, 'posttest']],
        'kuesioner' => ['peserta.instrumen', [$p, 'kuesioner']],
        'sertifikat' => ['peserta.sertifikat', [$p]],
    ];
@endphp

<ol class="langkah mt-3">
    @foreach ($tahapan as $i => $t)
        @php [$rute, $param] = $tautan[$t['kunci']]; @endphp
        <li class="langkah-item langkah-{{ $t['status'] }}">
            <p class="text-[0.6rem] font-bold uppercase tracking-[0.12em] text-slate-400">Tahap {{ $i + 1 }}</p>
            <p class="mt-1 text-sm font-semibold leading-snug">{{ $t['label'] }}</p>

            @if ($t['status'] === 'terkunci')
                <p class="mt-1.5 text-xs text-slate-400">{{ $t['alasan'] }}</p>
                <span class="chip chip-off mt-2">Terkunci</span>
            @else
                <a href="{{ route($rute, $param) }}" class="btn btn-sm {{ $t['status'] === 'selesai' ? 'btn-ghost' : 'btn-cyan' }} mt-2">
                    {{ $t['status'] === 'selesai' ? 'Lihat' : 'Kerjakan' }}
                </a>
            @endif
        </li>
    @endforeach
</ol>
