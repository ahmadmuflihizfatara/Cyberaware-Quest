<div class="card mt-3 tbl-wrap">
    <table class="tbl">
        <thead><tr><th>Peringkat</th><th>Nama</th><th class="text-right">Poin diperoleh</th></tr></thead>
        <tbody>
        @forelse ($leaderboard as $i => $b)
            <tr class="{{ (int) $b->id_pendaftaran === $p->id_pendaftaran ? 'bg-cyan-50/60' : '' }}">
                <td class="font-display font-bold">#{{ $i + 1 }}</td>
                <td>
                    {{ $b->nama_peserta }}
                    @if ((int) $b->id_pendaftaran === $p->id_pendaftaran)
                        <span class="chip chip-info ml-1">Kamu</span>
                    @endif
                </td>
                <td class="text-right font-semibold">{{ $b->total_poin_diperoleh }}</td>
            </tr>
        @empty
            <tr><td colspan="3" class="text-sm text-slate-500">Belum ada perolehan poin.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<p class="mt-2 text-xs text-slate-400">
    Sumber: view <code>v_leaderboard</code> (poin perolehan + koreksi, penukaran reward tidak menurunkan peringkat).
</p>
