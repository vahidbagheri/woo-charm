# ابزارهای توسعه، محتوا و آزمون VoiceWeb

این پوشه ابزار CLI تبدیل کاتالوگ‌های PO فرانت‌اند والد/چایلد، مدیریت والد و
مدیریت ACF چایلد به فایل MO قابل‌استفاده در وردپرس را نگه می‌دارد.
آزمون‌های مستقل و مرورگر، و ابزار داده‌ورودی اولیه نیز در همین پوشه‌اند. فرمان‌ها از
ریشهٔ WordPress اجرا می‌شوند؛ محیط/پورت/Playwright را با [RUNBOOK](../Doc/RUNBOOK.fa.md)
تنظیم کن. ابزار fixture با داده‌ورودی دائمی فروشگاه تفاوت دارد.

مستند کامل معماری ترجمه، ترتیب fallback، مثال‌های والد/چایلد، ترجمهٔ جمع،
مدیریت و رفع خطا:

[راهنمای یکپارچهٔ ترجمه](../Doc/TRANSLATION-SYSTEM.fa.md)

## فرمان‌های سریع

فرمان‌ها را از ریشهٔ وردپرس اجرا کن:

```powershell
# همهٔ localeها: فرانت والد/چایلد + مدیریت والد + مدیریت ACF چایلد
php wp-content/themes/voiceweb/tools/compile-translations.php

# فقط فارسی
php wp-content/themes/voiceweb/tools/compile-translations.php fa_IR

# فقط انگلیسی
php wp-content/themes/voiceweb/tools/compile-translations.php en_US

# راهنمای داخلی
php wp-content/themes/voiceweb/tools/compile-translations.php --help
```

ابزار ابتدا یک MO موقت می‌سازد و آن را اعتبارسنجی می‌کند؛ فقط پس از موفقیت، فایل
MO مقصد جایگزین می‌شود. فایل PO را ویرایش کن و فایل MO را مستقیم تغییر نده.

پوشه‌ها به‌صورت بازگشتی خوانده می‌شوند؛ در نتیجه کاتالوگ‌های
`voiceweb-child/languages/admin/` با دامنهٔ `voiceweb-child-admin` نیز خودکار در
فرایند کامپایل قرار می‌گیرند. اجرای بدون locale هشت کاتالوگ و اجرای یک locale
چهار کاتالوگ همان زبان را می‌سازد.

## ابزارهای زیرساخت

```powershell
php wp-content/themes/voiceweb/tools/protect-directories.php
php wp-content/themes/voiceweb/tools/protect-directories.php --check
php wp-content/themes/voiceweb/tools/test-infrastructure.php
php wp-content/themes/voiceweb/tools/test-single-template.php
php wp-content/themes/voiceweb/tools/test-blog-components.php
php wp-content/themes/voiceweb/tools/check-user-tables.php
```

فرمان اول محافظ‌های مفقود پدر و چایلد را می‌سازد؛ `--check` فقط بررسی می‌کند.
تست مستقل دیتابیس را تغییر نمی‌دهد. ابزار جدول کاربران نیز فقط نام جدول‌های
مؤثر وردپرس را در دیتابیس کنترل می‌کند. [جزئیات](../Doc/INFRASTRUCTURE.fa.md)

## بررسی ووکامرس و آدرس‌ها

`test-catalog-browser.cjs` فیلتر بازهٔ قیمت، ترتیب قیمت، دسته‌بندی، برند، ویژگی عمومی،
محصول متغیر، موجودی، تخفیف، امتیاز، جستجو و صفحه‌بندی فروشگاه را بررسی می‌کند.
موبایل، اعمال/لغو انتخاب‌های موقت، برگشت مرورگر، خطای شبکه و فرم بدون JavaScript
هم پوشش داده می‌شوند. مشتری، محصولات، دسته‌ها، برندها و یک ویژگی آزمایشی ساخته و
در `finally` پاک می‌شوند. محافظ موقت `mu-plugins` فقط اندازهٔ صفحهٔ مشتری آزمایشی
را تغییر می‌دهد و در پایان حذف می‌شود؛ هیچ سفارش یا پرداختی ساخته نمی‌شود.

`test-account-orders-browser.cjs` با سفارش‌ها و مشتریان موقت، یکسان‌بودن شمارنده و فهرست،
نمایش انتظار پرداخت، پنهان‌ماندن پیش‌نویس خالی، مالکیت سفارش و رفتار حساب بدون ایمیل را
بررسی می‌کند. پیام تأیید ایمیل باید بعد از جدول باشد و هیچ سفارش مهمان بدون تأیید به
حساب اضافه نشود. تنظیمات مرورگر همان تست‌های دیگر این پوشه است.

`test-account-menu-browser.cjs` منوی حساب در هدر و علاقه‌مندی‌های بومی ووکامرس را با
مشتریان و محصولات موقت بررسی می‌کند. باز و بسته‌شدن، صفحه‌کلید، ورود مهمان، ذخیره/حذف
محصول ساده و متغیر، موبایل و جداسازی فهرست مشتریان پوشش داده می‌شوند. از تنظیمات
Playwright تست ووکامرس استفاده کن و قابلیت Wishlists را پیش از اجرا فعال کن.

`test-woocommerce.php` با مشتریان موقت، مالکیت آدرس، اعتبارسنجی، پیش‌فرض و انتقال
به اطلاعات سفارش را بررسی می‌کند. `test-woocommerce-browser.cjs` جریان واقعی مودال،
حساب، فروشگاه، سبد و انتخاب آدرس در checkout بلوکی را در دسکتاپ و موبایل اجرا می‌کند.
برای مسیر `order-pay` یک سفارش پرداخت‌نشدهٔ موقت ساخته می‌شود. داده‌های آزمایشی در پایان
پاک می‌شوند و پیامک، پرداخت و ثبت سفارش نهایی انجام نمی‌شود.

برای تنظیم پورت دیتابیس و Playwright، [فرمان‌ها و جزئیات تست](../Doc/WOOCOMMERCE.fa.md)
را ببین.

## صفحهٔ اصلی، طرح مشترک و درخواست تماس

```powershell
php -d mysqli.default_port=10046 wp-content/themes/voiceweb/tools/test-inquiries.php
php -d mysqli.default_port=10046 wp-content/themes/voiceweb/tools/test-sale-expiry.php
node wp-content/themes/voiceweb/tools/test-storefront-browser.cjs
node wp-content/themes/voiceweb/tools/test-product-design-browser.cjs
node wp-content/themes/voiceweb/tools/test-product-price-expiry-browser.cjs
node wp-content/themes/voiceweb/tools/test-inquiries-browser.cjs
node wp-content/themes/voiceweb/tools/test-leather-content-browser.cjs
```

| ابزار | دامنه و اثر |
|---|---|
| `test-storefront-browser.cjs` | صفحهٔ اصلی تنظیم‌شده، اسلایدر/کیبورد، deferred تصاویر، ساعت شمارنده/بازگشت مرورگر، فوتر، reduced motion، بدون JS و responsive؛ فقط خواندنی، بدون کاربر/lead/سفارش جدید |
| `test-product-design-browser.cjs` | fixture مشتری/محصول/variation/دسته؛ کارت مشترک، درصد صحیح، چند قلب، native AJAX/Blocks، محصول متغیر/تایمر و responsive؛ cleanup در finally |
| `test-product-price-expiry-browser.cjs` | مرز واقعی تخفیف شش‌ثانیه‌ای، ساعت دستگاه جلوتر، قیمت DOM و JSON/cache متغیر، حفظ تعداد/انتخاب، فقط یک GET؛ fixture و guard در finally پاک می‌شوند |
| `test-inquiries.php` | fixture محصول/مشتری/lead/counter؛ حالت سراسری/محصول، origin/CSRF/proof، phone/consent، حریم خصوصی، variation و guard خرید؛ بدون سفارش مالی |
| `test-inquiries-browser.cjs` | modal، فوکوس/Escape، عرض ۳۲۰/۳۹۰/۷۶۸، ثبت خصوصی fixture و swatch/reset بومی؛ MU guard تنها IP مشتری خودش را جدا و در finally حذف می‌کند |
| `test-sale-expiry.php` | دو محصول/variation fixture، prime cache و گذر واقعی تخفیف پنج‌ثانیه‌ای با cron خاموش؛ native مبلغ/range/badge، `_price`/lookup، idempotency و lease؛ رویداد/محصول fixture پاک می‌شود |
| `test-leather-content-browser.cjs` | ممیزی داده/Media فعلی و تمام ۸ ترکیب دو محصول متغیر چرمی؛ مبلغ/تصویر/قابلیت خرید بومی؛ محصول فقط خواندنی و مشتری موقت با cleanup؛ بدون افزودن سبد، lead یا سفارش |

نتایج تازهٔ ۱۴ اکتبر ۲۰۲۶: آدرس ۳۹، inquiry ۳۴ و sale expiry ۱۵ بررسی موفق؛ خانه در
۳۲۰/۳۹۰/۷۶۸/۱۲۸۰/۱۴۴۰ و محصول/account menu/catalog موفق بودند. خطای JavaScript، پیامک
و پرداخت واقعی در تست طرح صفر بود. ممیزی تمام ۸ ترکیب چرم، تصویر فعال و مبلغ بومی
موفق و دادهٔ محصول قبل/بعد یکسان بود؛ مشتری موقت پاک شد. جزئیات در
[راهنمای محتوا](../Doc/LEATHER-CONTENT.fa.md) ثبت شده است.

۸ کاتالوگ MO با ابزار همین پوشه ساخته و PO/MO تطبیق داده شد؛ پس از لینک مجله، ۷۰۴ entry دارند. تست‌های مستقل
infrastructure/blog اکنون compat بومی وردپرس را پس از `ABSPATH`/`WPINC` بارگذاری می‌کنند
تا POMO جدید در PHP 8.4 کار کند. انتظار بلاگِ صفحهٔ اصلی قدیمی حذف شد و تست فروشگاهی
جداگانه جای آن را پوشش می‌دهد. ۳۷ بررسی infrastructure و ۸۵ بررسی اجزای بلاگ موفق شدند؛
router سینگل و بررسی محافظ پوشه‌ها نیز موفق بودند.

مرورگرها به `VOICEWEB_PLAYWRIGHT_PATH` و در صورت نیاز `VOICEWEB_BROWSER_EXECUTABLE`
نیاز دارند؛ `VOICEWEB_PHP` مسیر PHP، `VOICEWEB_WC_MYSQL_PORT` پورت و `VOICEWEB_WC_SITE`
آدرس سایت است. screenshot اختیاری با `VOICEWEB_WC_SCREENSHOTS` در مسیر انتخابی توسعه‌دهنده
ذخیره می‌شود. fixtureهای چند تست را روی یک نصب همزمان اجرا نکن. هیچ فرمان آزمون، اجازهٔ
پرداخت یا پیامک واقعی به مشتری نیست.

## داده‌ورودی اولیهٔ چرم

```powershell
# فقط نمایش برنامه و وضعیت موجود؛ چیزی وارد نمی‌کند:
php -d mysqli.default_port=10046 wp-content/themes/voiceweb/tools/seed-leather-store.php
# اجرای نوشتنی برای نصب مناسب local/staging با فایل‌های تصویر حاضر:
php -d mysqli.default_port=10046 wp-content/themes/voiceweb/tools/seed-leather-store.php --apply
```

`seed-leather-store.php` یک ابزار نوشتن محتوای اولیه است، نه آزمون fixture: ۶ محصول چرمی
(۲ متغیر/۸ variation)، ۶ دسته، رسانه‌های WebP، مجله، دربارهٔ ما و ACF خانه را به‌صورت
رکوردهای قابل ویرایش وارد می‌کند. مالکیت meta/mapping خودش را حفظ و اجرای بعدی، ویرایش
مدیر را overwrite یا deadline را restart نمی‌کند. محصولات کاربر و سفارش‌های قبلی حذف
نمی‌شوند؛ حالت سراسری خرید، Coming soon، درگاه، ارسال و شمارهٔ تماس واقعی حفظ می‌شوند.
بدنهٔ دربارهٔ ما فقط وقتی خالی بوده تکمیل می‌شود.

موجودی/قیمت/ابعاد/تصویر اولیه باید قبل از انتشار با کالای واقعی تطبیق شوند. انتقال Git
دیتابیس یا Media را منتقل نمی‌کند؛ برای مهاجرت باید رکوردها و فایل‌های uploads نیز همراه
کد منتقل شوند. [محتوای چرم](../Doc/LEATHER-CONTENT.fa.md)، [صفحهٔ اصلی](../Doc/HOMEPAGE.fa.md)،
[طرح ووکامرس](../Doc/COMMERCE-DESIGN.fa.md)، [تماس](../Doc/INQUIRIES.fa.md) و
[پایان تخفیف](../Doc/SALE-EXPIRY.fa.md) جزئیات را نگه می‌دارند.
