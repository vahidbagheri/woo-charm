(function () {
  'use strict';
  function initialize() {
    if (!window.jalaliDatepicker) return;
    window.jalaliDatepicker.startWatch({
      selector: '[data-vw-jalali]',
      time: true,
      hasSecond: true,
      persianDigits: false,
      separatorChars: { date: '/', between: ' ', time: ':' },
      zIndex: 100000,
      showTodayBtn: true,
      showEmptyBtn: true,
      showCloseBtn: true
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize);
  else initialize();
})();
