// Timer dihitung dari waktu mulai di database, jadi tidak reset saat halaman di-refresh.
const grid = document.querySelector('.grid');
const selisih = Number(grid.dataset.sekarang) * 1000 - Date.now(); // selisih jam server & browser

let audio = null;       // suara aktif setelah tombol diklik (aturan browser)
let bunyiTerakhir = 0;

function dua(n) { return String(n).padStart(2, '0'); }
function format(detik) {
  return dua(Math.floor(detik / 3600)) + ':' + dua(Math.floor((detik % 3600) / 60)) + ':' + dua(detik % 60);
}

function bunyi() {
  if (!audio) return;
  const o = audio.createOscillator(), g = audio.createGain();
  o.frequency.value = 880; g.gain.value = 0.2;
  o.connect(g); g.connect(audio.destination);
  o.start(); o.stop(audio.currentTime + 0.5);
}

document.getElementById('btn-suara').onclick = function () {
  audio = new (window.AudioContext || window.webkitAudioContext)();
  bunyi();
  this.textContent = 'Alarm aktif';
  this.classList.add('aktif');
};

function update() {
  const sekarang = Date.now() + selisih;
  let adaHabis = false;

  document.querySelectorAll('.timer[data-mulai]').forEach(function (el) {
    const detik = Math.max(0, Math.floor((sekarang - el.dataset.mulai * 1000) / 1000));
    const paket = Number(el.dataset.paket);   // menit; 0 = bebas
    const tarif = Number(el.dataset.tarif);
    const diskon = Number(el.dataset.diskon) || 0;   // persen, 0 = umum
    const kartu = el.closest('.kartu');
    const menitPakai = Math.max(1, Math.ceil(detik / 60));
    let biaya;

    if (paket > 0) {
      const sisa = paket * 60 - detik;
      biaya = Math.ceil(paket * tarif / 60);
      el.classList.remove('warning', 'habis-teks');
      kartu.classList.remove('habis');
      if (sisa > 0) {
        el.textContent = format(sisa);                    // hitung mundur
        if (sisa <= 300) el.classList.add('warning');      // 5 menit terakhir
      } else {
        el.textContent = 'HABIS +' + format(-sisa);        // waktu lewat
        el.classList.add('habis-teks');
        kartu.classList.add('habis');
        adaHabis = true;
        biaya += Math.ceil(Math.max(0, menitPakai - paket) * tarif / 60);
      }
    } else {
      el.textContent = format(detik);                     // bebas: hitung maju
      biaya = Math.ceil(menitPakai * tarif / 60);
    }
    biaya = Math.ceil(biaya * (100 - diskon) / 100);
    el.nextElementSibling.textContent = 'Rp ' + biaya.toLocaleString('id-ID');
  });

  // Alarm berulang tiap 4 detik selama ada unit yang waktunya habis
  if (adaHabis && Date.now() - bunyiTerakhir > 4000) { bunyi(); bunyiTerakhir = Date.now(); }
}
update();
setInterval(update, 1000);