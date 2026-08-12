import QRCode from 'qrcode';
import jsQR from 'jsqr';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

// Setiap elemen <canvas data-qr="isi"> dirender menjadi QR code.
document.querySelectorAll('canvas[data-qr]').forEach((el) => {
    QRCode.toCanvas(el, el.dataset.qr, { width: 240, margin: 1 }, (err) => {
        if (err) console.error(err);
    });
});

// Pemindai QR check-in. Memakai jsQR (murni JS, jalan di semua browser modern
// dengan akses kamera) — tidak bergantung pada BarcodeDetector bawaan yang
// cuma didukung Chrome/Edge. Kanvas sampling dibuat di memori, tidak perlu
// elemen tambahan di halaman.
const tombolPindai = document.getElementById('mulai-pindai');
if (tombolPindai) {
    const video = document.getElementById('pratinjau-kamera');
    const status = document.getElementById('status-pindai');
    const dot = document.getElementById('status-dot');
    const input = document.getElementById('token');
    const tombolBatal = document.getElementById('batal-pindai');

    const kanvas = document.createElement('canvas');
    const ctx = kanvas.getContext('2d', { willReadFrequently: true });

    let stream = null;
    let frameId = null;
    let memindai = false;

    const warnaDot = { idle: 'bg-slate-300', aktif: 'bg-cyan-500 animate-pulse', ok: 'bg-emerald-500', gagal: 'bg-red-500' };
    const setStatus = (teks, warna) => {
        status.textContent = teks;
        dot.className = 'h-2 w-2 shrink-0 rounded-full transition-colors ' + warnaDot[warna];
    };

    const terapkanHasil = (nilai) => {
        // QR boleh berisi token polos atau URL check-in dengan ?token=
        input.value = (nilai.includes('token=') ? new URL(nilai).searchParams.get('token') : nilai).toUpperCase();
        setStatus('Token terbaca. Tekan "Check-in sekarang".', 'ok');
    };

    const berhenti = () => {
        if (frameId) cancelAnimationFrame(frameId);
        stream?.getTracks().forEach((t) => t.stop());
        video.classList.add('hidden');
        tombolBatal.classList.add('hidden');
        memindai = false;
    };

    const pindaiFrame = () => {
        if (!memindai) return;

        if (video.readyState === video.HAVE_ENOUGH_DATA) {
            kanvas.width = video.videoWidth;
            kanvas.height = video.videoHeight;
            ctx.drawImage(video, 0, 0, kanvas.width, kanvas.height);

            const gambar = ctx.getImageData(0, 0, kanvas.width, kanvas.height);
            const hasil = jsQR(gambar.data, gambar.width, gambar.height);

            if (hasil?.data) {
                berhenti();
                terapkanHasil(hasil.data);
                return;
            }
        }

        frameId = requestAnimationFrame(pindaiFrame);
    };

    tombolBatal.addEventListener('click', () => {
        berhenti();
        setStatus('Pemindaian dibatalkan. Ketik token manual atau coba lagi.', 'idle');
    });

    tombolPindai.addEventListener('click', async () => {
        if (memindai) return;

        if (!navigator.mediaDevices?.getUserMedia) {
            setStatus('Browser ini tidak mendukung akses kamera. Ketik token secara manual.', 'gagal');
            return;
        }

        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
            memindai = true;
            video.srcObject = stream;
            video.classList.remove('hidden');
            tombolBatal.classList.remove('hidden');
            await video.play();
            setStatus('Arahkan kamera ke QR sesi…', 'aktif');

            frameId = requestAnimationFrame(pindaiFrame);
        } catch (e) {
            memindai = false;
            setStatus('Kamera tidak dapat diakses: ' + e.message, 'gagal');
        }
    });

    // Tempel gambar/screenshot berisi QR (Ctrl+V) — dibaca lewat clipboard,
    // didekode dengan kanvas+jsQR yang sama, tanpa unggah ke server.
    document.addEventListener('paste', async (e) => {
        const item = [...(e.clipboardData?.items ?? [])].find((i) => i.type.startsWith('image/'));
        if (!item) return;

        e.preventDefault();
        setStatus('Membaca gambar dari clipboard…', 'aktif');

        try {
            const bitmap = await createImageBitmap(item.getAsFile());
            kanvas.width = bitmap.width;
            kanvas.height = bitmap.height;
            ctx.drawImage(bitmap, 0, 0);

            const gambar = ctx.getImageData(0, 0, kanvas.width, kanvas.height);
            const hasil = jsQR(gambar.data, gambar.width, gambar.height);

            if (hasil?.data) {
                terapkanHasil(hasil.data);
            } else {
                setStatus('QR tidak terbaca dari gambar yang ditempel. Coba screenshot lain.', 'gagal');
            }
        } catch (err) {
            setStatus('Gagal membaca gambar dari clipboard: ' + err.message, 'gagal');
        }
    });
}
