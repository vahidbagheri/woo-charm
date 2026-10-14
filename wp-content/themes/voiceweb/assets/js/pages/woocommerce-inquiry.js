(function () {
  'use strict';
  var config = window.VoiceWebInquiry;
  var dialog = document.getElementById('vw-inquiry-dialog');
  if (!config || !dialog || typeof dialog.showModal !== 'function') return;
  var form = dialog.querySelector('[data-vwi-form]');
  var submit = form.querySelector('[type="submit"]');
  var feedback = form.querySelector('[data-vwi-feedback]');
  var opener = null;
  var selection = null;
  var session = null;
  var sessionPromise = null;
  var busy = false;
  var savedOverflow = '';
  var requestNumber = 0;

  function digits(value) {
    return String(value).replace(/[۰-۹٠-٩]/g, function (digit) {
      var code = digit.charCodeAt(0);
      return String(code - (code >= 1776 ? 1776 : 1632));
    });
  }
  function message(text, error) {
    feedback.textContent = text || '';
    feedback.hidden = !text;
    feedback.classList.toggle('is-error', !!error);
  }
  async function api(path, data) {
    var controller = new AbortController();
    var timer = window.setTimeout(function () { controller.abort(); }, 20000);
    var headers = { 'Content-Type': 'application/json' };
    if (config.nonce) headers['X-WP-Nonce'] = config.nonce;
    if (session && session.csrf) headers['X-VW-Inquiry-CSRF'] = session.csrf;
    try {
      var response = await fetch(config.api + path, { method: 'POST', credentials: 'same-origin', cache: 'no-store', signal: controller.signal, headers: headers, body: JSON.stringify(data || {}) });
      var body;
      try { body = await response.json(); } catch (error) { throw new Error('پاسخ معتبر دریافت نشد. دوباره تلاش کنید.'); }
      if (!response.ok) throw new Error(body.message || 'ثبت درخواست ممکن نشد. دوباره تلاش کنید.');
      return body;
    } catch (error) {
      if (error.name === 'AbortError') throw new Error('ارتباط طول کشید. دوباره تلاش کنید.');
      throw error;
    } finally { window.clearTimeout(timer); }
  }
  function prepareSession() {
    session = null;
    sessionPromise = api('/session', {}).then(function (data) {
      session = data;
      return new Promise(function (resolve) { window.setTimeout(function () { resolve(data); }, 2200); });
    });
    // A rejection is shown on submit without creating an unhandled background rejection.
    sessionPromise.catch(function () {});
    return sessionPromise;
  }
  function selectedVariation(button) {
    var variationForm = button.closest('form.variations_form');
    if (!variationForm) return null;
    var field = variationForm.querySelector('input.variation_id');
    var id = field ? Number(field.value) : 0;
    if (!id || button.disabled) return null;
    var labels = Array.from(variationForm.querySelectorAll('select[name^="attribute_"]')).map(function (select) {
      return select.options[select.selectedIndex] && select.value ? select.options[select.selectedIndex].text : '';
    }).filter(Boolean);
    return { id: id, label: labels.join(' · ') };
  }
  function open(button) {
    var id = Number(button.dataset.vwInquiryProduct);
    if (!id || busy) return;
    var variation = button.dataset.vwInquiryVariable ? selectedVariation(button) : null;
    if (button.dataset.vwInquiryVariable && !variation) return;
    opener = button;
    selection = { product_id: id, variation_id: variation ? variation.id : 0 };
    requestNumber++;
    form.reset();
    form.classList.remove('is-complete');
    Array.from(form.elements).forEach(function (field) { field.disabled = false; });
    submit.textContent = 'ثبت درخواست تماس ←';
    message('');
    dialog.querySelector('[data-vwi-product-label]').textContent = (button.dataset.vwInquiryTitle || 'محصول انتخاب‌شده') + (variation && variation.label ? ' — ' + variation.label : '');
    savedOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    dialog.showModal();
    form.elements.phone.focus({ preventScroll: true });
    prepareSession();
  }
  function close() {
    if (busy) return;
    dialog.close();
  }
  dialog.addEventListener('close', function () {
    requestNumber++;
    document.body.style.overflow = savedOverflow;
    if (opener && opener.isConnected) opener.focus({ preventScroll: true });
    opener = null;
  });
  dialog.addEventListener('cancel', function (event) { if (busy) event.preventDefault(); });
  dialog.addEventListener('click', function (event) {
    if (event.target.closest('[data-vwi-close]')) close();
    if (event.target === dialog) {
      var rect = dialog.getBoundingClientRect();
      if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) close();
    }
  });
  document.addEventListener('click', function (event) {
    var button = event.target.closest('button[data-vw-inquiry-product]');
    if (!button || button.disabled) return;
    event.preventDefault();
    open(button);
  });
  document.addEventListener('submit', function (event) {
    if (!event.target.matches('form.variations_form')) return;
    var button = event.target.querySelector('[data-vw-inquiry-variable]');
    if (!button) return;
    // Enter in the native selection form must not submit a purchase POST.
    event.preventDefault();
    if (!button.disabled) open(button);
  });
  form.addEventListener('submit', async function (event) {
    event.preventDefault();
    if (busy || !selection || !form.reportValidity()) return;
    var phone = digits(form.elements.phone.value).replace(/[\s()\-]+/g, '');
    phone = phone.replace(/^\+98|^0098|^98(?=\d{10}$)/, '0');
    if (!/^09\d{9}$/.test(phone)) { message('شمارهٔ موبایل را به‌صورت ۰۹ وارد کنید.', true); form.elements.phone.focus(); return; }
    busy = true;
    submit.disabled = true;
    submit.textContent = 'در حال ثبت درخواست…';
    form.setAttribute('aria-busy', 'true');
    message('');
    var current = requestNumber;
    try {
      await sessionPromise;
      var data = await api('', { product_id: selection.product_id, variation_id: selection.variation_id, phone: phone, name: form.elements.name.value, note: form.elements.note.value, website: form.elements.website.value, consent: form.elements.consent.checked, proof: session.proof });
      if (current !== requestNumber) return;
      message(data.message);
      form.classList.add('is-complete');
      Array.from(form.elements).forEach(function (field) { field.disabled = true; });
      dialog.querySelector('[data-vwi-close]').focus();
    } catch (error) {
      message(error.message || 'ارتباط برقرار نشد. دوباره تلاش کنید.', true);
      // Fresh proof supports an expired tab or retry after a temporary network failure.
      prepareSession();
    } finally {
      busy = false;
      form.removeAttribute('aria-busy');
      if (!form.classList.contains('is-complete')) { submit.disabled = false; submit.textContent = 'ثبت درخواست تماس ←'; }
    }
  });

  // Native WooCommerce events are also used by woo-variation-swatches; no duplicate selection engine.
  if (window.jQuery) {
    window.jQuery(document).on('found_variation.vwInquiry', 'form.variations_form', function (event, variation) {
      var button = this.querySelector('[data-vw-inquiry-variable]');
      if (!button) return;
      var available = variation && variation.variation_id && variation.is_in_stock && variation.is_purchasable && variation.variation_is_active !== false;
      button.disabled = !available;
      button.textContent = available ? 'درخواست تماس برای این مدل' : 'این مدل موجود نیست';
    }).on('reset_data.vwInquiry hide_variation.vwInquiry', 'form.variations_form', function () {
      var button = this.querySelector('[data-vw-inquiry-variable]');
      if (button) { button.disabled = true; button.textContent = 'انتخاب مدل و درخواست تماس'; }
    });
    window.jQuery('form.variations_form').each(function () { window.jQuery(this).trigger('check_variations'); });
  }
}());
