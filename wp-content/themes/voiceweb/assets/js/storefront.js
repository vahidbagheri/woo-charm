(function () {
  'use strict';
  const header = document.querySelector('.cn-site-header');
  if (!header) return;
  function measure() {
    const height = Math.ceil(header.getBoundingClientRect().height);
    document.documentElement.style.setProperty('--vw-header-height', `${height}px`);
  }
  measure();
  if ('ResizeObserver' in window) new ResizeObserver(measure).observe(header);
  else window.addEventListener('resize', measure);
})();
