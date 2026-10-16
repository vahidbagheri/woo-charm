(function () {
  'use strict';
  var config = window.VoiceWebWishlist;
  if (!config) return;
  var controls = [], items = [], loaded = false, busy = false, loading = null;
  function selected(id) { return items.find(function (item) { return Number(item.id) === id; }); }
  function render() {
    controls = controls.filter(function (control) { return control.button.isConnected; });
    controls.forEach(function (control) {
      var button = control.button;
      var saved = !!selected(control.id);
      var text = busy ? config.strings.saving : (!control.id ? config.strings.options : (saved ? config.strings.remove : config.strings.add));
      button.disabled = busy || !control.id;
      button.setAttribute('aria-pressed', saved ? 'true' : 'false');
      button.setAttribute('aria-label', text);
      button.title = text;
      var label = button.querySelector('span');
      if (label) label.textContent = text;
    });
  }
  async function api(method, path, data) {
    var controller = new AbortController();
    var timer = window.setTimeout(function () { controller.abort(); }, 20000);
    try {
      var response = await fetch(config.api + (path || ''), {
        method: method, credentials: 'same-origin', cache: 'no-store', signal: controller.signal,
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.wpNonce, Nonce: config.nonce },
        ...(data ? { body: JSON.stringify(data) } : {})
      });
      var nonce = response.headers.get('Nonce'); if (nonce) config.nonce = nonce;
      if (response.status === 204) return null;
      var result = await response.json();
      if (!response.ok) throw new Error(result.message || config.strings.error);
      return result;
    } finally { window.clearTimeout(timer); }
  }
  function message(control, text, error) {
    if (!control.feedback) return;
    control.feedback.hidden = !text;
    control.feedback.textContent = text;
    control.feedback.classList.toggle('is-error', !!error);
    if (control.button.classList.contains('vw-product-card__favorite')) control.feedback.classList.toggle('screen-reader-text', !error);
  }
  async function load() {
    if (loaded) return;
    if (!loading) loading = api('GET').then(function (result) {
      items = Array.isArray(result) ? result : []; loaded = true;
    }).finally(function () { loading = null; });
    return loading;
  }
  function connect() {
    document.querySelectorAll('[data-vw-wishlist-product]').forEach(function (button) {
      if (button.dataset.vwWishlistReady) return;
      button.dataset.vwWishlistReady = 'true';
      var variable = button.dataset.vwWishlistVariable === 'true';
      var control = { button: button, id: variable ? 0 : Number(button.dataset.vwWishlistProduct), variation: [], feedback: button.parentElement.querySelector('.vw-wishlist-feedback') };
      controls.push(control);
      button.addEventListener('click', async function () {
        if (busy || !control.id) return;
        busy = true; render(); message(control, '');
        var currentId = control.id, currentVariation = control.variation.slice();
        try {
          await load();
          var item = selected(currentId);
          if (item) {
            await api('DELETE', '/' + encodeURIComponent(item.key));
            items = items.filter(function (value) { return value.key !== item.key; });
            message(control, config.strings.removed);
          } else {
            var saved = await api('POST', '', { product_id: currentId, variation: currentVariation, quantity: 1 });
            items.push(saved); message(control, config.strings.saved);
          }
        } catch (error) {
          message(control, error.message === 'Failed to fetch' || ['AbortError', 'SyntaxError'].includes(error.name) ? config.strings.error : (error.message || config.strings.error), true);
        } finally { busy = false; render(); }
      });
      if (variable && window.jQuery) {
        var form = window.jQuery(button.closest('.summary') || document).find('form.variations_form').first();
        function readVariation() {
          control.variation = [];
          form.find('select[name^="attribute_"]').each(function () { control.variation.push({ attribute: this.name, value: this.value }); });
        }
        form.on('found_variation', function (event, chosen) { control.id = Number(chosen.variation_id); readVariation(); render(); });
        form.on('reset_data hide_variation', function () { control.id = 0; control.variation = []; render(); });
        var current = Number(form.find('input.variation_id').val());
        if (current) { control.id = current; readVariation(); }
      }
    });
    render();
  }
  connect();
  if (controls.length) {
    busy = true; render();
    load().catch(function () {
      controls.filter(function (control) { return !control.button.classList.contains('vw-product-card__favorite'); }).forEach(function (control) { message(control, config.strings.error, true); });
    }).finally(function () { busy = false; render(); });
  }
  document.addEventListener('voiceweb:catalog-updated', connect);
})();
