import QRCode from 'qrcode';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

// Setiap elemen <canvas data-qr="isi"> dirender menjadi QR code.
document.querySelectorAll('canvas[data-qr]').forEach((el) => {
    QRCode.toCanvas(el, el.dataset.qr, { width: 240, margin: 1 }, (err) => {
        if (err) console.error(err);
    });
});

// Pemindai QR check-in. Memakai BarcodeDetector bawaan browser (Chrome/Edge di
// localhost) supaya tidak perlu pustaka pemindai tambahan; bila tidak tersedia,
// peserta tetap bisa mengetik token secara manual.
const tombolPindai = document.getElementById('mulai-pindai');
if (tombolPindai) {
    const video = document.getElementById('pratinjau-kamera');
    const status = document.getElementById('status-pindai');
    const input = document.getElementById('token');

    tombolPindai.addEventListener('click', async () => {
        if (!('BarcodeDetector' in window)) {
            status.textContent = 'Browser ini tidak mendukung pemindai bawaan. Ketik token secara manual.';
            return;
        }

        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
            video.srcObject = stream;
            video.classList.remove('hidden');
            await video.play();
            status.textContent = 'Arahkan kamera ke QR sesi…';

            const detector = new BarcodeDetector({ formats: ['qr_code'] });
            const timer = setInterval(async () => {
                const kode = await detector.detect(video);
                if (!kode.length) return;

                clearInterval(timer);
                stream.getTracks().forEach((t) => t.stop());
                video.classList.add('hidden');

                const nilai = kode[0].rawValue;
                // QR boleh berisi token polos atau URL check-in dengan ?token=
                input.value = nilai.includes('token=') ? new URL(nilai).searchParams.get('token') : nilai;
                status.textContent = 'Token terbaca. Tekan "Check-in sekarang".';
            }, 400);
        } catch (e) {
            status.textContent = 'Kamera tidak dapat diakses: ' + e.message;
        }
    });
}
