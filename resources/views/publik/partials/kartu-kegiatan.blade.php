<a href="{{ route('kegiatan.show', $k) }}" class="card card-pad transition hover:border-cyan-500">
    <div class="flex items-center gap-2">
        <span class="chip chip-info">{{ ucfirst($k->mode_pelaksanaan) }}</span>
        <span class="chip {{ $k->status_kegiatan === 'berlangsung' ? 'chip-warn' : 'chip-off' }}">{{ $k->status_kegiatan }}</span>
    </div>
    <h3 class="mt-3 text-lg font-bold leading-snug">{{ $k->tema }}</h3>
    <p class="mt-1.5 text-sm text-slate-500">
        {{ $k->sekolah?->mitra?->nama_mitra }} · {{ $k->tanggal_mulai?->translatedFormat('d M Y') }}
    </p>
    <p class="mt-3 text-xs font-semibold text-slate-400">
        Kuota {{ $k->kapasitas }} kursi · Program {{ $k->program?->nama_program }}
    </p>
</a>
