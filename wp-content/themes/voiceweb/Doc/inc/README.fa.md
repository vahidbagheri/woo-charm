# مرجع فایل‌های ریشهٔ `inc`

تطبیق با `functions.php`: **۱۶ اکتبر ۲۰۲۶ / ۲۰۲۶-۱۰-۱۶**. برند عمومی **آوان چرم** است؛
`voiceweb` نام فنی قالب و `cn_*`/`vw_*` شناسه‌های سازگار کد و داده‌اند.
نصب جاری در `C:/Users/baghe/Local Sites/woo-charm-main/app/public` و
`http://woo-charm-main.local` است؛ PHP CLI `8.3.30`، پورت DB `10113`،
`woocommerce_coming_soon=no` و `blog_public=0`. تلفن عمومی تأییدشده `09101719687` است.
درباره، تماس، ۲۰ مناسبت، هاب هدایا، ۶ دستهٔ محتوایی و مجلهٔ چرم در
[راهنمای آوان چرم](../AVAN-CHARM.fa.md)، [هدایا](../GIFT-LANDINGS.fa.md)،
[سئو](../SEO-ROADMAP.fa.md) و [راهنمای اجرا](../RUNBOOK.fa.md) مستندند.

منبع نهایی حقیقت، کد فعلی قالب است. فایل‌های زیر مستقیماً از `functions.php` بارگذاری می‌شوند.

| فایل | مسئولیت اصلی | سند |
|---|---|---|
| `security.php` | سخت‌سازی امنیت، پیام ورود و حذف نسخهٔ وردپرس | [راهنما](./security.fa.md) |
| `fonts.php` | انتخاب و بارگذاری فونت بر اساس زبان | [راهنما](./fonts.fa.md) |
| `assets.php` | لودر CSS/JavaScript و بهینه‌سازی assetها | [راهنما](./assets.fa.md) |
| `acf-field-groups.php` | schema کدنویسی‌شدهٔ ACF: بلاگ fallback، تنظیمات سایت، خانه، هدیه/سئو، برند، دسته و noindex؛ آیکون term با guard Iconly | [راهنما](./acf-field-groups.fa.md) |
| `helpers.php` | helperهای تصویر، کارت، ترجمه، منو و Iconly | [راهنما](./helpers.fa.md) |
| `site-settings.php` | Options Page و resolve تنظیمات سایت | [راهنما](./site-settings.fa.md) |
| `search.php` | جست‌وجوی زنده و صفحهٔ نتایج | [راهنما](./search.fa.md) |
| `actions.php` | اکشن‌های عمومی قالب و کوئری آرشیو | [راهنما](../INFRASTRUCTURE.fa.md) |
| `filters.php` | فیلترهای عمومی بدنه و منو | [راهنما](../INFRASTRUCTURE.fa.md) |
| `ajax-callbacks.php` | اعتبارسنجی ورودی‌های AJAX | [راهنما](./ajax-callbacks.fa.md) |
| `log.php` | رویدادها و اتصال به voiceweb_log | [راهنما](../INFRASTRUCTURE.fa.md) |
| `front-page.php` | داده و query صفحهٔ اصلی، تصویر responsive، تاریخ پیشنهاد و asset context | [راهنما](../HOMEPAGE.fa.md) |
| `woocommerce.php` | ورودی حساب، آدرس، checkout، سفارش، علاقه‌مندی و کاتالوگ | [راهنما](../WOOCOMMERCE.fa.md) |
| `gift-landing.php` | فیلدهای مناسبت، محصولات منتخب دسته‌ای/دستی، هاب/پیوندهای مرتبط و متادیتای fallback هدایا/خانه؛ تشخیص مالکیت SEO plugin | [هدایا](../GIFT-LANDINGS.fa.md) |
| `brand.php` | فیلدهای مستقل درباره/تماس، هویت عمومی، favicon، متادیتای برند/محصول و کنترل noindex/sitemap محتوا | [آوان چرم](../AVAN-CHARM.fa.md) |
| `category-landing.php` | محتوای قابل ویرایش لندینگ دسته، راهنمای خرید، پیوندها و سئوی مجموعه/صفحه‌بندی | [آوان چرم](../AVAN-CHARM.fa.md) و [برنامهٔ سئو](../SEO-ROADMAP.fa.md) |
| `leather-magazine.php` | محدودکردن کوئری اصلی مجله به `leather-journal` و فرزندان، canonical/صفحه‌بندی و لینک‌های مرتبط؛ انتقال ۳۰۱ فقط آرشیو خود دسته به `/blog/` | [آوان چرم](../AVAN-CHARM.fa.md) و [اجرا](../RUNBOOK.fa.md) |

`helpers.php` با `get_theme_file_path()` resolve می‌شود تا چایلدتم بتواند پیاده‌سازی هم‌مسیر را جایگزین کند. فایل‌های معمول `inc/woocommerce/` از ورودی `woocommerce.php` خوانده می‌شوند و مسئولیت هرکدام در راهنمای ووکامرس مشخص است.

در `home.php`، وجود دستهٔ مجلهٔ چرم شاخهٔ `template-parts/blog/leather-magazine.php` را فعال
می‌کند؛ `home_sections` و Flexible Content قدیمی فقط fallback هستند. رکورد و نشانی نوشته‌های
قدیمی حفظ شده‌اند و سایدبارشان به‌صورت سراسری فیلتر نشده است. رندر درباره/تماس نیز به فیلدهای
`vw_brand_*` خود برگه متکی است و از بلوک کوتاه معرفی خانه مستقل است. برای شمار گروه‌های ACF
به ثبت‌های واقعی کد مراجعه کن و عدد ثابت مستندات قدیمی را مبنا قرار نده.

خروجی fallback سئو با `voiceweb_gift_has_seo_plugin()` به افزونهٔ سئوی فعال واگذار می‌شود.
برای تغییر مرتبط با داده، ابزارهای `tools/setup-avan-brand.php`، `seed-gift-landings.php` و
`refine-avan-seo.php` ابتدا dry-run دارند؛ فرمان‌ها و تفاوت آزمون خواندنی با fixture/نوشتن
در [RUNBOOK.fa.md](../RUNBOOK.fa.md) آمده است. نتایج قدیمی محیط/مرورگر را با تاریخ خودشان
بخوان؛ این مرجع فایل‌ها ادعای اجرای دوبارهٔ آن‌ها ندارد.

برای مرز قالب و افزونه‌های مستقل ورود/پرداخت، تصمیم‌های این گفتگو و وضعیت فعلی پروژه،
[سند تحویل](../HANDOFF.fa.md) را بخوان. ساختار قدیمی `inc/Modules` حذف شده است.
