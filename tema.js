// Mode terang/gelap. Pilihan disimpan di browser (localStorage).
(function () {
  var simpan = null;
  try { simpan = localStorage.getItem('tema'); } catch (e) {}
  var awal = simpan || (window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
  document.documentElement.setAttribute('data-tema', awal);

  document.addEventListener('DOMContentLoaded', function () {
    var tombol = document.getElementById('tema');
    if (!tombol) return;
    function label() {
      tombol.textContent = document.documentElement.getAttribute('data-tema') === 'dark' ? 'Mode terang' : 'Mode gelap';
    }
    label();
    tombol.addEventListener('click', function () {
      var baru = document.documentElement.getAttribute('data-tema') === 'dark' ? 'light' : 'dark';
      document.documentElement.setAttribute('data-tema', baru);
      try { localStorage.setItem('tema', baru); } catch (e) {}
      label();
    });
  });
})();