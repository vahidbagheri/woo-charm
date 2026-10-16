/* Execute the production metadata updater with a small DOM test double; no browser automation. */
'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../assets/js/pages/woocommerce-catalog.js'), 'utf8');
const start = source.indexOf('  function syncHeadMetadata(');
const end = source.indexOf('  async function navigate(', start);
assert.ok(start >= 0 && end > start, 'Metadata updater must be available for the integration path.');
function head(values) {
  const result = {
    nodes: [],
    querySelectorAll(selector) { return this.nodes.filter(node => node.selector === selector); },
    append(node) { node.remove = () => { this.nodes = this.nodes.filter(item => item !== node); }; this.nodes.push(node); }
  };
  values.forEach(([selector, value]) => result.append({ selector, value }));
  return result;
}
const desc = 'meta[name="description"]', robots = 'meta[name="robots"]', canonical = 'link[rel="canonical"]';
const schema = 'script.vw-category-schema[type="application/ld+json"]';
const document = {
  head: head([[desc, 'Original'], [robots, 'index, follow'], [canonical, '/category/'], [canonical, '/duplicate/'], [schema, '{"url":"/category/"}'], ['link[rel="stylesheet"]', '/style.css'], ['script', 'keep-existing-code']]),
  importNode(node) { return { selector: node.selector, value: node.value }; }
};
const context = vm.createContext({ document });
vm.runInContext(source.slice(start, end), context);
const next = { head: head([[desc, 'Filtered result'], [robots, 'noindex, follow'], [canonical, '/category/'], [schema, '{"url":"/category/","name":"Quoted \\" text"}'], ['script', 'do-not-import-code']]) };
context.syncHeadMetadata(next);
assert.deepEqual(document.head.querySelectorAll(desc).map(node => node.value), ['Filtered result']);
assert.deepEqual(document.head.querySelectorAll(robots).map(node => node.value), ['noindex, follow']);
assert.deepEqual(document.head.querySelectorAll(canonical).map(node => node.value), ['/category/']);
assert.deepEqual(document.head.querySelectorAll(schema).map(node => node.value), [next.head.querySelectorAll(schema)[0].value]);
assert.equal(next.head.querySelectorAll(canonical).length, 1, 'Importing metadata must not consume the parsed response.');
assert.deepEqual(document.head.querySelectorAll('script').map(node => node.value), ['keep-existing-code']);
assert.equal(document.head.querySelectorAll('link[rel="stylesheet"]').length, 1);
context.syncHeadMetadata({ head: head([[canonical, '/shop/page/2/']]) });
assert.deepEqual(document.head.querySelectorAll(canonical).map(node => node.value), ['/shop/page/2/']);
assert.equal(document.head.querySelectorAll(desc).length, 0, 'Missing metadata must clear the previous page value.');
assert.equal(document.head.querySelectorAll(robots).length, 0, 'Returning to an indexable page must clear stale noindex.');
assert.equal(document.head.querySelectorAll(schema).length, 0, 'Moving to shop must remove old category schema.');
assert.match(source, /document\.title = documentResult\.title \|\| document\.title; syncHeadMetadata\(documentResult\);/, 'The live navigation path must invoke the updater.');
assert.match(source, /new URL\(response\.url \|\| url\.href\)\.origin !== location\.origin/, 'Cross-origin redirected responses must be rejected.');
console.log('PASS: catalogue metadata replacement, stale-tag removal, asset preservation and navigation wiring. No browser UI used.');
