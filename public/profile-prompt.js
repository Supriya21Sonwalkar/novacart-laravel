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
      var matched = path.match(/^\/(cart\/add|wishlist)\/(\d+)\/?$/);
      if (matched) {
        var fields = new FormData(form);
        var body = new URLSearchParams();
        body.set('product_id', matched[2]);
        body.set('kind', matched[1] === 'wishlist' ? 'wishlist' : 'cart');
        body.set('quantity', fields.get('quantity') || '1');
        if (fields.get('variant')) body.set('variant', fields.get('variant'));
        fetch('/shopping/intent', {method:'POST',credentials:'same-origin',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json'},body:body}).catch(function(){ /* Original product remains available through the browser back button. */ });
      }
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
