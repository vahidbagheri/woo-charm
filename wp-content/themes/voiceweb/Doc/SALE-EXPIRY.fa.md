# پایان واقعی پیشنهاد ویژه

[ووکامرس](./WOOCOMMERCE.fa.md) · [تحویل پروژه](./HANDOFF.fa.md)

شمارندهٔ پیشنهاد ویژه از `date_on_sale_to` واقعی محصولات/variationهای ووکامرس می‌آید.
مقدار نمایشی شمارنده به‌تنهایی مبنای مبلغ خرید نیست؛ مبلغ را ووکامرس تعیین می‌کند.
فایل [`inc/woocommerce/sale-expiry.php`](../inc/woocommerce/sale-expiry.php) برای فاصلهٔ کوتاهی
که cron/Action Scheduler دیر اجرا می‌شود، cache نمایش و lookup محصول را با همان تاریخ
هماهنگ می‌کند. سفارش قبلی، مبلغ سفارش، موجودی، پرداخت و گزارش مالی بازنویسی نمی‌شوند.

## رفتار بومی بررسی‌شده در WooCommerce 11.2.1

محصول ساده، سرویس بومی `ScheduledSalePriceReconciler` دارد؛ در `get_price('view')` تاریخ
تخفیف را دوباره بررسی و هنگام تأخیر runner قیمت عادی را برمی‌گرداند. `edit` و `_price`
دیتابیس برای handler بومی باقی می‌مانند. این سرویس از Woo 11.1 وجود دارد.

variation تازه‌خوانده‌شده نیز قیمت را بر اساس تاریخ استخراج می‌کند، اما در این نصب
`product_instance_caching` فعال است. یک object متغیر که هنگام تخفیف cache شده می‌تواند
بعد از پایان زمان قیمت قبلی را داشته باشد؛ خواندن همان instance معادل خواندن تازه از
datastore نیست. range/نشان تخفیف والد هم از `wc_var_prices_{id}` می‌آیند و کلید بومی
به‌تنهایی sale-window ندارد. این رفتار با عبور واقعی ساعت و بدون تغییر meta در تست
بازسازی شد؛ صرف تغییر تاریخ در دیتابیس تست کافی برای تشخیص آن نبود.

## هماهنگی قیمت و cache

همان callback بومی `ScheduledSalePriceReconciler::reconcile_price` در priority ۹۹ به
`woocommerce_product_variation_get_price` متصل می‌شود. الگوریتم مالی دیگری نوشته نشده است:
محافظت بومی از قیمت تغییر‌یافته توسط افزونهٔ ثالث و `edit` همچنان برقرار است.
اگر کلاس بومی موجود نباشد این اتصال انجام نمی‌شود؛ اعتبارسنجی این راهکار روی Woo 11.2.1
انجام شده و روی نسخهٔ قدیمی‌تر باید دوباره تست شود.

در `woocommerce_get_variation_prices_hash` تاریخ شروع/پایان و phase هر child تخفیف‌دار
اضافه می‌شود: `future`، `active` یا `ended`. hash بین مرزهای واقعی ثابت است؛ clock bucket
دقیقه‌ای، پاک‌کردن همهٔ cacheها و درخواست periodical برای قیمت‌ها وجود ندارد.
وقتی child cached با phase واقعی و یکی از قیمت‌های بومی خودش اختلاف داشته باشد، فقط همان
child از `ProductCache` بومی حذف و از datastore مجدداً خوانده می‌شود. سپس محاسبهٔ range،
badge و مبلغ با مسیر بومی Woo انجام می‌شود. قیمت خام یا مالی از روی DOM تعیین نمی‌شود.

## ترمیم محدود دیتابیس

در `init` اولویت ۹، حداکثر **۲۵ محصول ساده/variation منقضی** که `_price` آن‌ها هنوز با قیمت
عادی تفاوت دارد، بررسی می‌شوند. lease در options با INSERT/UPDATE شرطی اتمیک ادعا می‌شود؛
فقط یک batch در هر **۱۵ ثانیه** اجرا می‌شود. parentهای متغیر/گروهی و نوع‌های سفارشی/خارجی
در query وارد نمی‌شوند تا قیمت derived با قیمت مستقل اشتباه نشود و batch روی آن‌ها گیر نکند.

برای ساده، `wc_handle_product_end_scheduled_sale()` بومی اجرا می‌شود. برای variation
منقضی، helper مشترک بومی `wc_apply_sale_state_for_product($variation,'end')` اجرا می‌شود:
handler scheduled در variation تازه‌خوانده‌شده ممکن است به خاطر برابر بودن قیمت مشتق‌شدهٔ
`edit` با قیمت عادی زود return کند، در حالی که `_price` ذخیره‌شده هنوز قدیمی است.
helper مشترک بومی `_price`، lookup، transient و sync والد را ترمیم می‌کند.
در پایان batch، cache فهرست sale بومی نیز پاک می‌شود. شروع/پایان و runner اصلی Woo همچنان
فعال‌اند و این fallback جایگزین scheduler نیست.

نمایش range و مبلغ variation در مرز تاریخ تغییر می‌کنند؛ ترمیم SQL lookup در اولین درخواست
مجاز بعدی انجام می‌شود. بنابراین بدون runner در فروشگاه بسیار بزرگ، batchهای بعدی و query
مرتب‌سازی/بازهٔ قیمت تا فرصت batch بعدی می‌توانند با lookup قدیمی کار کنند. پردازش انبوه روی
هر درخواست انجام نمی‌شود. scheduler سالم در production همچنان برای ترمیم منظم داده لازم است.
HTML قدیمی یک تب یا cache تمام‌صفحه خودکار از دیتابیس عوض نمی‌شود؛ refresh بخش پیشنهاد/قیمت
در script صفحه باید HTML عمومی تازه بگیرد و cache صفحهٔ production با تاریخ پایان هماهنگ شود.

## قرارداد فایل و تست

`sale-expiry` یک بار به includeهای `inc/woocommerce.php` افزوده می‌شود؛ خودش hookها را
ثبت می‌کند. تابع `voiceweb_wc_reconcile_expired_sales($force=false, $only_ids=[])` در اجرای
عادی batch عمومی محدود دارد. آرگومان IDs فقط برای تست/ابزار PHP مجاز است و endpoint عمومی
یا query-string برای force ندارد. تست با IDs fixture به هیچ محصول واقعی دیگر دست نمی‌زند.

```powershell
php -l wp-content/themes/voiceweb/inc/woocommerce/sale-expiry.php
php -d mysqli.default_port=10046 wp-content/themes/voiceweb/tools/test-sale-expiry.php
```

[`tools/test-sale-expiry.php`](../tools/test-sale-expiry.php) **۱۵ بررسی موفق** داشت. دو محصول و
یک variation آزمایشی با sale پنج‌ثانیه‌ای ساخته، cache خام/نمایشی prime و با cron غیرفعال
واقعاً از مرز زمان عبور داده می‌شوند؛ بین دو phase هیچ تاریخ/meta/save تغییر نمی‌کند.
تست مبلغ ساده و variation cache‌شده، range/badge والد، ترمیم `_price` و SQL lookup ساده/والد،
idempotency، lease، عدم تغییر موجودی و عدم ایجاد سفارش را بررسی می‌کند.
mail و HTTP بیرونی مسدود و fixtureها/رویدادهای scheduled آن‌ها در `finally` پاک می‌شوند.
مقدار lease قبلی برگردانده می‌شود؛ تست فقط local/staging است.
