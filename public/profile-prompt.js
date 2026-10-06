(function () {
  var prompt = document.getElementById('profile-prompt');
  if (!prompt || prompt.dataset.profileComplete === 'true') return;
  var opener;
  function show() {
    opener = document.activeElement;
    if (!prompt.open) prompt.showModal();
  }
  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    var path = new URL(form.action, location.href).pathname;
    if (/^\/cart\/add\/[^/]+\/?$/.test(path) || /^\/wishlist\/[^/]+\/?$/.test(path) || /^\/orders\/[^/]+\/reorder\/?$/.test(path) || path === '/checkout') {
      event.preventDefault();
      show();
    }
  });
  document.addEventListener('click', function (event) {
    var link = event.target.closest('a');
    if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    var url = new URL(link.href, location.href);
    if (url.origin === location.origin && (url.pathname === '/checkout' || url.pathname === '/wishlist')) {
      event.preventDefault();
      show();
    }
  });
  prompt.querySelectorAll('[data-close-profile]').forEach(function (button) {
    button.addEventListener('click', function () { prompt.close(); });
  });
  prompt.addEventListener('close', function () { if (opener && opener.isConnected) opener.focus(); });
})();
