# مستندات یکپارچهٔ آوان چرم و قالب VoiceWeb

از این فایل شروع کنید. تمام راهنماهای اصلی قالب در همین پوشهٔ `Doc` و مرجع فایل‌های زیرساخت در `Doc/inc` نگهداری می‌شوند. **آوان چرم** نام فروشگاه است؛ `voiceweb` و `voiceweb-child` نام فنی قالب‌ها و پیشوندهای قدیمی بخشی از قرارداد کد و دیتابیس‌اند. آخرین یکپارچه‌سازی: **۱۶ اکتبر ۲۰۲۶**.

برای مدیریت روزمره، [راهنمای آوان چرم](AVAN-CHARM.fa.md)؛ برای اجرا و عیب‌یابی، [راهنمای اجرا](RUNBOOK.fa.md)؛ و برای ادامهٔ توسعه، [سند تحویل](HANDOFF.fa.md) را بخوانید. نمونه‌ها و نتایج تاریخی با تاریخ خودشان باقی مانده‌اند؛ وضعیت جاری نصب در ابتدای راهنمای اجرا آمده است. در اختلاف میان مثال تاریخی و پیاده‌سازی، کد فعلی مرجع است.

## مسیر مطالعه

### فروشگاه، محتوا و سئو

| موضوع | سند تخصصی |
|---|---|
| برند، لوگو، درباره، تماس، دسته‌ها، مجله و انتقال سایت | [AVAN-CHARM.fa.md](AVAN-CHARM.fa.md) |
| مدیریت ۲۰ لندینگ مناسبتی و انتخاب خودکار/دستی محصول | [GIFT-LANDINGS.fa.md](GIFT-LANDINGS.fa.md) |
| برنامهٔ پنج‌ماهه، نقشهٔ عبارت‌ها و سنجه‌های سئو | [SEO-ROADMAP.fa.md](SEO-ROADMAP.fa.md) |
| صفحهٔ اصلی، اسلایدر، ACF، پیشنهاد زمان‌دار و درباره | [HOMEPAGE.fa.md](./HOMEPAGE.fa.md) |
| داده‌گذاری محصولات، تنوع‌ها و عکس‌های کیف و چرم | [LEATHER-CONTENT.fa.md](./LEATHER-CONTENT.fa.md) |
| کارت مشترک محصول و ظاهر سبد/checkout/account | [COMMERCE-DESIGN.fa.md](./COMMERCE-DESIGN.fa.md) |

### اجرا، تحویل و Git

| موضوع | سند تخصصی |
|---|---|
| وضعیت فعلی محیط، فرمان‌های اجرا، آزمون و عیب‌یابی | [RUNBOOK.fa.md](RUNBOOK.fa.md) |
| ساختار ابزارها و اجرای امن فرمان‌های کمکی | [TOOLS.fa.md](TOOLS.fa.md) |
| خطای مسیر بلند Windows و Git در VS Code | [GIT-WINDOWS.fa.md](GIT-WINDOWS.fa.md) |
| تصمیم‌ها، تاریخچهٔ توسعه و تحویل پروژه | [HANDOFF.fa.md](HANDOFF.fa.md) |

### ووکامرس، ورود و پرداخت

| موضوع | سند تخصصی |
|---|---|
| فروشگاه، حساب کاربری و دفترچهٔ چندآدرسی | [WOOCOMMERCE.fa.md](WOOCOMMERCE.fa.md) |
| ورود، ثبت‌نام، OTP و سرویس پیامک | [AUTH.fa.md](AUTH.fa.md) |
| بله‌پی، کارت‌به‌کارت و بررسی رسید | [PAYMENTS.fa.md](PAYMENTS.fa.md) |
| گزینهٔ سراسری/محصولی درخواست تماس و پیگیری مدیریت | [INQUIRIES.fa.md](./INQUIRIES.fa.md) |
| پایان sale، قیمت variation و cache بومی | [SALE-EXPIRY.fa.md](./SALE-EXPIRY.fa.md) |

### توسعه، ACF و زیرساخت

| موضوع | سند تخصصی |
|---|---|
| معماری اجرای درخواست و نقاط توسعه | [SYSTEM.fa.md](SYSTEM.fa.md) |
| محافظ پوشه‌ها، ثبت رویداد و بررسی دیتابیس | [INFRASTRUCTURE.fa.md](INFRASTRUCTURE.fa.md) |
| قالب تکی هر نوع محتوا | [SINGLE-TEMPLATES.fa.md](SINGLE-TEMPLATES.fa.md) |
| بارگذاری `JavaScript`، `Sass` و `CSS` | [JS-SASS-LOADING.fa.md](JS-SASS-LOADING.fa.md) |
| تعریف گروه‌های ACF در کد | [ACF-CODE-FIRST.fa.md](ACF-CODE-FIRST.fa.md) |
| تنظیمات هدر و فوتر | [SITE-SETTINGS.fa.md](SITE-SETTINGS.fa.md) |
| آیکون‌های Iconly | [ICONLY-TERM-ICONS.fa.md](ICONLY-TERM-ICONS.fa.md) |
| کش مرورگر و نسخه‌گذاری فایل‌ها | [CACHE.fa.md](CACHE.fa.md) |
| مرجع فایل‌های PHP زیرساخت | [inc/README.fa.md](inc/README.fa.md) |

### فونت و ترجمه

| موضوع | سند تخصصی |
|---|---|
| فونت چندزبانه | [FONTS.fa.md](./FONTS.fa.md) |
| آموزش پله‌به‌پلهٔ افزودن فونت با شرح کد و عیب‌یابی | [FONT-ADDING-STEP-BY-STEP.fa.md](./FONT-ADDING-STEP-BY-STEP.fa.md) |
| استفاده از `Yekan Bakh Variable` | [YEKAN-BAKH-VARIABLE.fa.md](./YEKAN-BAKH-VARIABLE.fa.md) |
| ترجمهٔ فرانت‌اند و مدیریت | [TRANSLATION-SYSTEM.fa.md](./TRANSLATION-SYSTEM.fa.md) |
| ترجمهٔ رابط فیلدهای `ACF` | [ACF-TRANSLATION.fa.md](./ACF-TRANSLATION.fa.md) |

ورودی‌های کوتاه هر بخش:

- [راهنمای قالب والد](../README.md)
- [راهنمای قالب چایلد](../../voiceweb-child/README.md)
- [راهنمای متمرکز ابزارها](TOOLS.fa.md)

## معرفی پروژه

`VoiceWeb` یک پروژهٔ وردپرس چندزبانه شامل مجله، فروشگاه ووکامرس و حساب مشتری است.
مسئولیت‌ها در چهار لایهٔ روشن قرار دارند:

- قالب والد `voiceweb` منطق عمومی، تمپلیت‌های پایه، helperها و لودر asset را دارد.
- قالب چایلد `voiceweb-child` محل شخصی‌سازی همین سایت، overrideهای ظاهری و یکسان‌سازی رقم‌ها با زبان جاری است.
- افزونهٔ مستقل `voiceweb-auth` ورود پیامکی، ثبت‌نام، ورود با رمز و نوار موبایل را دارد.
- افزونهٔ مستقل `voiceweb-payments` دو درگاه پرداخت و رسیدهای کارت‌به‌کارت را دارد.

ظاهر و اتصال‌های فروشگاه در قالب والد و ساختار معمول `inc/woocommerce/`، Sass و
JavaScript هستند. ساختار قدیمی `inc/Modules` مبنای توسعه نیست.

صفحهٔ اصلی فروشگاه با `front-page.php`، فیلدهای `vw_front_*` و partialهای
`template-parts/front/` ساخته می‌شود. ظاهر فعلی برای کیف و چرم است و محتوا
از مدیریت قابل تعویض است. پیشنهادهای ویژه زمان پایان واقعی sale بومی دارند؛
کارت‌های صفحهٔ اصلی، فروشگاه و related از یک template مشترک استفاده می‌کنند.

صفحهٔ نوشته‌ها با `home.php` والد ساخته می‌شود. با وجود دستهٔ `leather-journal`، شاخهٔ مجلهٔ آوان چرم و query اصلی وردپرس فعال است؛ `inc/leather-magazine.php` آن را به مقاله‌های چرم محدود می‌کند. فیلد Flexible Content به نام `home_sections` برای حالت جایگزین، در نبود این دسته باقی است. کارت‌ها و بخش‌های مجله در `template-parts/blog/` قرار دارند.

## پیش‌نیازها

- وردپرس نسخهٔ `6.3` یا جدیدتر؛
- PHP نسخهٔ `7.4` یا جدیدتر، مطابق هدر فعلی `style.css`؛
- افزونهٔ `ACF PRO` برای Flexible Content و Options Page؛
- افزونهٔ `WPML` فقط در سایت چندزبانه؛
- افزونهٔ `ACF Iconly Font Picker` برای انتخاب و رندر آیکون‌های `Iconly`.

قالب بدون `WPML` نیز کار می‌کند و زبان را از locale وردپرس می‌گیرد. بدون
`ACF PRO` بخش‌های وابسته به Flexible Content و تنظیمات سایت در دسترس نیستند.
عدد PHP بالا فقط متادیتای فعلی قالب است؛ مجموعهٔ کامل با افزونه‌های ورود و پرداخت
به **PHP 8.0 یا جدیدتر** نیاز دارد. پرداخت به PHP 64-bit و OpenSSL، و پردازش رسید
به GD یا Imagick نیاز دارد. نسخه و مسیر اجرایی محیط فعلی و snapshotهای قدیمی در [راهنمای اجرا](./RUNBOOK.fa.md) تفکیک شده‌اند.

## قرارداد واژه‌ها و نگارش

برای خوانایی متن راست‌به‌چپ، اصطلاح فارسی در جمله می‌آید و نام فنی انگلیسی داخل
code span نوشته می‌شود. واژه‌های پرتکرار این سند:

| مفهوم فارسی | نام فنی در کد |
|---|---|
| قالب | `template` |
| جزء قالب | `template part` |
| نوع محتوا | `post type` |
| فایل استایل یا اسکریپت | `asset` |
| نقطهٔ اتصال | `hook` |
| پالایه | `filter` |
| زمینهٔ درخواست | `context` |
| پیدا کردن فایل مؤثر | `resolve` |
| مسیر جایگزین | `fallback` |
| قالب والد | `parent theme` |
| قالب چایلد | `child theme` |

نام فایل، تابع، hook، کلید آرایه و فرمان همیشه با شکل دقیق انگلیسی نوشته می‌شود؛
ترجمهٔ آزاد آن‌ها نباید داخل کد استفاده شود.

## راه‌اندازی صحیح

1. پوشه‌های `voiceweb` و `voiceweb-child` را در `wp-content/themes/` قرار بده.
2. قالب `voiceweb-child` را فعال کن. قالب والد باید نصب باشد، اما جداگانه فعال
   نمی‌شود.
3. `ACF PRO` را فعال کن.
4. Field Groupهای متعلق به قالب خودکار از PHP ثبت می‌شوند؛ هیچ فایل JSON را
   import یا Sync نکن.
5. برگهٔ فروشگاه اصلی را در **Settings → Reading → Homepage**، در حالت ثابت،
   انتخاب کن؛ محتوای گروه `Storefront homepage` همان برگه را تکمیل کن.
6. یک برگهٔ جدا برای بلاگ بساز و آن را در **Settings → Reading → Posts page** انتخاب
   کن. برای این برگه template دستی انتخاب نکن؛ وردپرس خودکار `home.php` را
   استفاده می‌کند.
7. مقاله‌های مجلهٔ آوان چرم را در دستهٔ `leather-journal` یا زیردسته‌های آن قرار بده. در نبود این دسته، فیلد `Home sections` برگهٔ بلاگ چیدمان جایگزین را می‌سازد؛ این فیلد کنترل صفحهٔ اصلی فروشگاه نیست.
8. فهرست‌های دسکتاپ، موبایل و فوتر را در **تنظیمات سایت → هدر و فوتر** انتخاب کن.
9. یک‌بار **Settings → Permalinks** را ذخیره کن تا rewriteها تازه شوند.

### ACF صفحهٔ اصلی فروشگاه

گروه `group_voiceweb_front_page` با شرط `front_page`، اسلایدها، دسته‌ها،
محصولات ویژه/تازه، داستان، درباره، مجله و FAQ را فراهم می‌کند. فیلدها
`vw_front_*` هستند و source همان `inc/acf-field-groups.php` والد است.
تاریخ پایان انتخابی تنها با مجوز مناسب روی sale موجود حداکثر ۱۲ محصول
انتخاب‌شده ذخیره می‌شود؛ [راهنمای صفحهٔ اصلی](./HOMEPAGE.fa.md) را ببین.

### ACF چیدمان جایگزین بلاگ

در نبود دستهٔ `leather-journal`، گروه `group_home_blog` در `inc/acf-field-groups.php` استفاده می‌شود و Field
Group یا JSON جداگانه‌ای در دیتابیس لازم ندارد. فیلد اصلی آن:

```text
home_sections
```

layoutهای شناخته‌شدهٔ فعلی عبارت‌اند از:

```text
hero
featured
guide
categories
travelogue
news
```

با آپدیت کد قالب، تعریف این layoutها نیز در درخواست بعدی ACF به‌روز می‌شود.

## نقشهٔ قالب‌ها

| درخواست وردپرس | فایل مؤثر با چایلد فعال | توضیح |
|---|---|---|
| صفحهٔ اصلی سایت | `voiceweb-child/front-page.php` → `voiceweb/front-page.php` | فروشگاه editable با `template-parts/front/` و `vw_front_*` |
| برگهٔ نوشته‌ها | `voiceweb/home.php` | مجلهٔ چرم با query اصلی؛ `home_sections` در حالت جایگزین |
| برگهٔ معمولی | `voiceweb/page.php` | عنوان، محتوا و صفحه‌بندی داخلی |
| درباره و تماس | `voiceweb/page-templates/about.php` و `contact.php` | متن و `vw_brand_*` مستقل روی هر برگه |
| لندینگ مناسبتی | `voiceweb/page-templates/gift-landing.php` | متن و محصول انتخابی از `vw_gift_*` |
| فهرست مناسبت‌ها | `voiceweb/page-templates/gift-hub.php` | فهرست برگه‌های مناسبتی منتشرشده |
| دستهٔ محصول | `voiceweb/template-parts/woocommerce/catalog.php` | آرشیو بومی و محتوای `vw_cat_*` |
| نوشتهٔ تکی | `voiceweb/single.php` | router برای `single-{post_type}.php` |
| نوشتهٔ بلاگ | `voiceweb/template-parts/blog/single-post.php` | محتوای کامل، فهرست مطالب و سایدبار |
| آرشیو یک دسته | `voiceweb/category.php` | بخش ویژه، محبوب، تازه و بارگذاری بیشتر |
| جست‌وجو | `voiceweb/search.php` | نتایج و فیلتر نوع محتوا |
| fallback نهایی | `voiceweb/index.php` | درخواست‌هایی که تمپلیت اختصاصی ندارند |

### قالب‌های تکی داینامیک

فایل ریشهٔ `single.php` post type جاری را تشخیص می‌دهد و این الگو را لود می‌کند:

```text
template-parts/{post_type}/single-{post_type}.php
```

برای نمونه، نوشتهٔ معمولی با post type برابر `post` به این فایل می‌رود:

```text
template-parts/blog/single-post.php
```

fallback عمومی داخل پوشهٔ `single/` وجود ندارد. اگر فایل اختصاصی یک post type
ساخته نشده باشد، محتوای آن سینگل رندر نمی‌شود. جزئیات و نمونهٔ ساخت فایل جدید در
[راهنمای قالب‌های سینگل](./SINGLE-TEMPLATES.fa.md) آمده است.

## ساختار واقعی پروژه

درخت زیر فایل‌های معماری را نشان می‌دهد و عمداً فایل‌های vendor، فونت‌ها و
خروجی‌های کامپایل‌شده را خلاصه کرده است.

```text
voiceweb/
├─ functions.php
├─ header.php
├─ footer.php
├─ front-page.php
├─ home.php
├─ page.php
├─ single.php
├─ category.php
├─ search.php
├─ index.php
├─ comments.php
├─ page-templates/
│  ├─ about.php
│  ├─ contact.php
│  ├─ gift-hub.php
│  └─ gift-landing.php
├─ config/
│  ├─ fonts.php
│  ├─ avan-brand-content.php
│  ├─ category-landings.php
│  └─ gift-occasions.php
├─ inc/
│  ├─ assets.php
│  ├─ fonts.php
│  ├─ helpers.php
│  ├─ acf-field-groups.php
│  ├─ front-page.php            # helperهای storefront و پایان پیشنهاد
│  ├─ brand.php                 # برند، درباره/تماس، متادیتا و noindex
│  ├─ gift-landing.php          # محصولات و متادیتای مناسبت‌ها
│  ├─ category-landing.php      # راهنمای دسته و سئوی کاتالوگ
│  ├─ leather-magazine.php      # مجلهٔ چرم و لینک‌های مرتبط
│  ├─ search.php
│  ├─ site-settings.php
│  ├─ security.php
│  ├─ cache.php                  # موجود، اما در وضعیت فعلی غیرفعال
│  ├─ woocommerce.php           # فایل ورودی و hookهای ووکامرس
│  └─ woocommerce/              # فایل‌های معمول PHP با مسئولیت مشخص
├─ template-parts/
│  ├─ blog/
│  ├─ front/                   # بخش‌های صفحهٔ اصلی و درباره
│  ├─ brand/                   # لوگوی مشترک
│  ├─ gifts/                   # کارت‌های راهنمای هدیه
│  ├─ woocommerce/             # کاتالوگ، محصول و راهنمای دسته
│  ├─ header/
│  ├─ shared/
├─ assets/
│  ├─ scss/
│  ├─ css/
│  ├─ js/
│  ├─ fonts/
│  └─ images/
├─ languages/
│  └─ admin/
├─ Doc/
│  ├─ README.fa.md
│  ├─ HANDOFF.fa.md
│  ├─ AUTH.fa.md
│  ├─ PAYMENTS.fa.md
│  ├─ RUNBOOK.fa.md
│  ├─ WOOCOMMERCE.fa.md
│  ├─ HOMEPAGE.fa.md
│  ├─ LEATHER-CONTENT.fa.md
│  ├─ COMMERCE-DESIGN.fa.md
│  ├─ INQUIRIES.fa.md
│  ├─ SALE-EXPIRY.fa.md
│  └─ inc/                     # مرجع فایل‌های PHP زیرساخت
└─ tools/

voiceweb-child/
├─ functions.php
├─ front-page.php
├─ template-parts/
│  └─ home/
├─ assets/
│  ├─ scss/
│  ├─ css/
│  └─ js/
└─ languages/
   └─ admin/
```

## قرارداد قالب والد و چایلد

### ترتیب اجرا

وردپرس ابتدا `functions.php` چایلد و سپس `functions.php` والد را اجرا می‌کند.
بااین‌حال فایل‌های template و template part ابتدا از چایلد resolve می‌شوند.

### چه چیزهایی قابل override هستند؟

| هدف | روش درست |
|---|---|
| یک template کامل | ساخت فایل هم‌مسیر در چایلد |
| یک template part | ساخت فایل هم‌مسیر در چایلد |
| جایگزینی کامل `CSS` یا `JavaScript` | ساخت asset با همان مسیر نسبی |
| افزودن `JavaScript` بعد از نسخهٔ والد | ساخت فایل `<name>-child.js` |
| تغییر رفتار PHP | استفاده از hook یا filter در `functions.php` چایلد |
| تغییر فونت | filter به نام `cn/font-config` |
| تغییر ترجمه | کاتالوگ‌های `languages/` چایلد |

فایل‌های عمومی `inc/*.php` والد معمولاً با `get_template_directory()` بارگذاری
می‌شوند و با کپی هم‌مسیر در چایلد جایگزین نمی‌شوند. استثنای فعلی `inc/helpers.php`
است که از `get_theme_file_path()` استفاده می‌کند. گروه‌های ACF از فایل والد
بارگذاری می‌شوند؛ صرف ساختن `inc/acf-field-groups.php` در چایلد آن‌ها را جایگزین نمی‌کند.

## سیستم asset

لودر مرکزی در `inc/assets.php` قرار دارد و ترتیب کلی آن چنین است:

1. vendor والد مانند Bootstrap؛
2. فونت زبان جاری؛
3. `assets/css/global.css` و `assets/js/global.js`؛
4. assetهای کامپوننت در ریشهٔ `assets/css/` و `assets/js/`؛
5. assetهای مخصوص context جاری در `assets/{css,js}/pages/`؛
6. فایل‌های مکمل چایلد با پسوند `-child.js`.

### قواعد اصلی

- Bootstrap از والد لود می‌شود و نسخهٔ `RTL` یا `LTR` با `is_rtl()` انتخاب
  می‌شود.
- asset هم‌مسیر در چایلد، نسخهٔ والد را جایگزین می‌کند.
- نام asset باید فقط شامل حروف کوچک لاتین، عدد و خط تیره باشد.
- نسخهٔ URL از `filemtime()` ساخته می‌شود؛ تغییر فایل، cache key را عوض می‌کند.
- اسکریپت‌های قالب در فوتر و با راهبرد `defer` ثبت می‌شوند.
- فایل `SCSS` مستقیماً در مرورگر استفاده نمی‌شود و باید به `CSS` کامپایل شود.

### کلیدهای صفحه

تابع `cn_page_keys()` کلیدهای زیر را در والد تولید می‌کند:

```text
home
single-post
category
front
page
archive
search
notfound
woocommerce
woocommerce-account
woocommerce-addresses
woocommerce-wishlist
woocommerce-catalog
woocommerce-inquiry
```

چایلد برای آرشیو مجازی `/categories/` کلید `categories` را اضافه می‌کند. وجود
یک کلید به‌معنی وجود قطعی فایل نیست؛ لودر فقط assetهایی را enqueue می‌کند که
واقعاً روی دیسک وجود داشته باشند. برای نمونه، اکنون فایل `pages/page.css` وجود
ندارد و `page.php` از استایل پایهٔ قالب استفاده می‌کند.

### کامپایل Sass

نمونه از ریشهٔ وردپرس:

```powershell
npx sass wp-content/themes/voiceweb-child/assets/scss/global.scss wp-content/themes/voiceweb-child/assets/css/global.css --no-source-map --style=compressed
```

راهنمای کامل جایگزینی، توسعه و dependencyها در
[JS-SASS-LOADING.fa.md](./JS-SASS-LOADING.fa.md) است.

## ACF و مدل داده

گروه‌های ACF قالب در `inc/acf-field-groups.php` ثبت می‌شوند. دسته‌های اصلی داده:

| کاربرد | منبع تعریف |
|---|---|
| سکشن‌های صفحهٔ نوشته‌ها | ثبت Local PHP در `inc/acf-field-groups.php` |
| صفحهٔ اصلی فروشگاه، hero و محتوای برند | ثبت `group_voiceweb_front_page` در همان فایل؛ post meta برگهٔ `front_page` |
| تنظیمات هدر و فوتر | ثبت Local PHP در `inc/acf-field-groups.php` |
| آیکون دسته | ثبت با PHP در `inc/acf-field-groups.php` |
| لندینگ‌ها و متادیتای مناسبت | گروه‌های `group_voiceweb_gift_*`؛ post meta با پیشوند `vw_gift_*` |
| درباره، تماس، تولید و جنس چرم | گروه‌های `group_voiceweb_brand_*`؛ post meta با پیشوند `vw_brand_*` |
| راهنمای خرید دسته | `group_voiceweb_category_landing`؛ term meta با پیشوند `vw_cat_*` |
| محتوای آزمایشی و منع ایندکس | `group_voiceweb_seo_visibility`؛ فیلد `vw_brand_noindex` |

تعریف Local PHP در runtime بر رکورد هم‌کلید دیتابیس مقدم است؛ بنابراین schema
همراه آپدیت قالب تغییر می‌کند و import یا migration لازم نیست. صفحهٔ Field
Groups بدون قفل سفارشی باقی مانده است. جزئیات در
[راهنمای code-first ACF](./ACF-CODE-FIRST.fa.md) آمده است.

## تنظیمات هدر و فوتر

`inc/site-settings.php` یک Options Page اصلی و یک زیرصفحه می‌سازد:

```text
تنظیمات سایت
└─ هدر و فوتر
```

مقادیر اصلی شامل لوگو، تلفن، ایمیل، متن فوتر و چهار انتخاب فهرست هستند. فهرست‌ها
با زبان جاری محدود می‌شوند و در صورت فعال‌بودن `WPML`، ID ترجمه‌شده resolve
می‌شود. جزئیات فیلدها و نگهداری schema در
[SITE-SETTINGS.fa.md](./SITE-SETTINGS.fa.md) آمده است.

## فونت و زبان

تنظیم مرکزی فونت در `config/fonts.php` است. وضعیت فعلی:

| زبان | تنظیم فعال |
|---|---|
| فارسی | خانوادهٔ `Dana`، سه WOFF2 موجود Regular/Medium/Bold با وزن‌های `400`/`500`/`700`؛ فقط Regular preload |
| انگلیسی | stack سیستمی `Arial, Helvetica, sans-serif` |
| سایر زبان‌ها | stack پیش‌فرض سیستم |

سیستم ابتدا زبان `WPML` و سپس locale وردپرس را بررسی می‌کند. فونت زبان جاری
به‌صورت inline تعریف و در صورت نیاز preload می‌شود. برای تغییر پروژه‌ای از
`cn/font-config` در چایلد استفاده کن و فایل والد را تغییر نده.

## ترجمه

چهار text domain واقعی در پروژه وجود دارد:

| محدوده | text domain | مسیر |
|---|---|---|
| فرانت‌اند والد | `voiceweb` | `voiceweb/languages/` |
| فرانت‌اند چایلد | `voiceweb-child` | `voiceweb-child/languages/` |
| مدیریت والد | `voiceweb-admin` | `voiceweb/languages/admin/` |
| مدیریت چایلد | `voiceweb-child-admin` | `voiceweb-child/languages/admin/` |

دامنهٔ مجازی `voiceweb-acf` درخواست ترجمهٔ رابط ACF را به helper مدیریت هدایت
می‌کند. اولویت ترجمهٔ مدیریت، والد و سپس چایلد است؛ چایلد فقط رشته‌ای را تکمیل
می‌کند که در والد ترجمه نشده باشد.

پس از تغییر فایل `PO`، فایل `MO` را بساز:

```powershell
php wp-content/themes/voiceweb/tools/compile-translations.php
```

یا فقط یک locale:

```powershell
php wp-content/themes/voiceweb/tools/compile-translations.php fa_IR
```

## جست‌وجو و کارت‌ها

- جست‌وجوی هدر از `admin-ajax.php` استفاده می‌کند.
- حداقل طول عبارت جست‌وجو دو نویسه است.
- post typeهای عمومی و قابل جست‌وجو به‌صورت داینامیک کشف می‌شوند.
- صفحهٔ `search.php` می‌تواند نتایج را بر اساس post type فیلتر کند.
- helper به نام `cn_card()` کارت را از
  `template-parts/blog/card-{name}.php` رندر می‌کند.
- کوئری‌های کارت و آرشیو با filter به نام `cn/query/args` قابل تغییرند.

## ووکامرس

قالب، پوستهٔ فروشگاه، محصول، سبد، checkout و حساب را با فایل‌های سادهٔ
`inc/woocommerce/` آماده می‌کند. دفترچهٔ چندآدرسی، مودال ثبت/ویرایش، کشور ایران و
انتخاب آدرس هنگام خرید در همین پوشه قرار دارند. Sass و JavaScript با لودر موجود
و کلیدهای مخصوص ووکامرس بارگذاری می‌شوند. [راهنمای کامل](./WOOCOMMERCE.fa.md)

این بخش همچنین شامل منوی حساب دسکتاپ، سبد زندهٔ هدر و موبایل، علاقه‌مندی بومی،
تاریخچهٔ سفارش‌های پرداخت‌نشده و فیلترهای فروشگاه است. فرم خرید جدید با پرداخت
سفارش موجود (`order-pay`) دو مسیر جدا هستند. جزئیات ورود در [AUTH.fa.md](./AUTH.fa.md)
و چرخهٔ پرداخت در [PAYMENTS.fa.md](./PAYMENTS.fa.md) آمده‌اند؛ تنظیمات آن‌ها در ACF نیست.

## حریم خصوصی و بهینه‌سازی

`inc/security.php` برای کاربران ناشناس این محدودیت‌ها را اعمال می‌کند و پیام خطای ورود و generator نسخهٔ وردپرس را نیز یکسان‌سازی/حذف می‌کند:

- endpointهای `/wp/v2/users` را مخفی می‌کند؛
- آرشیو عمومی نویسنده را به پاسخ `404` تبدیل می‌کند؛
- provider کاربران را از sitemap وردپرس حذف می‌کند.

بهینه‌سازی‌های فعال دیگر:

- حذف `jQuery Migrate` در فرانت‌اند؛
- بارگذاری deferred برای اسکریپت‌های قالب؛ jQuery ترتیب بومی وردپرس را برای سازگاری
  با کدهای inline ووکامرس فارسی حفظ می‌کند؛
- حذف emojiهای وردپرس؛
- حذف استایل بلوک در صفحه‌ای که block ندارد؛
- امکان async کردن انتخابی stylesheetها با `cn/async-styles`؛
- نسخه‌گذاری `CSS` و `JavaScript` با `filemtime()`.

## وضعیت کش

قوانین `.htaccess` فایل‌های استاتیک را یک سال cache می‌کنند و assetهای قالب با
`filemtime()` نسخه‌گذاری می‌شوند. فایل `inc/cache.php` برای افزودن نسخه به URL
تصاویر وجود دارد، اما require آن در `functions.php` فعلاً کامنت است؛ پس
نسخه‌گذاری تصویر توسط این ماژول فعال نیست. جزئیات دقیق در
[CACHE.fa.md](./CACHE.fa.md) ثبت شده است.

## hookهای پرکاربرد

| hook | کاربرد |
|---|---|
| `cn/page-keys` | افزودن context جدید برای asset صفحه |
| `cn/asset/script-deps` | تغییر dependency اسکریپت‌ها |
| `cn/default-resources/css` | کنترل vendorهای CSS مانند Bootstrap |
| `cn/default-resources/js` | کنترل vendorهای JavaScript مانند Bootstrap |
| `cn/async-styles` | انتخاب stylesheet برای بارگذاری non-blocking |
| `cn/font-config` | تغییر تنظیم فونت زبان‌ها |
| `cn/query/args` | تغییر آرگومان کوئری‌های قالب |
| `cn_loop_post_data` | تغییر دادهٔ آماده‌شده برای کارت |
| `cn/card/name` و `cn/card/args` | تغییر renderer یا دادهٔ کارت |
| `cn/card/before` و `cn/card/after` | تزریق markup اطراف کارت |
| `cn/home/section-slug` | تغییر template part یک layout خانه |
| `cn/home/before_sections` و `cn/home/after_sections` | تزریق محتوا اطراف تمام سکشن‌ها |
| `cn/home/before_section` و `cn/home/after_section` | تزریق محتوا اطراف یک سکشن |
| `cn/category/post-counts` | تنظیم تعداد پست‌های بخش‌های دسته |
| `cn/search/post_types` | تغییر post typeهای قابل جست‌وجو |
| `cn/search/query_args` | تغییر کوئری AJAX جست‌وجوی هدر |
| `cn/site-settings/language` | تغییر زبان تنظیمات سایت |
| `cn/footer/menu-columns` | تغییر ستون‌های فهرست فوتر |

برای hookهای تخصصی، امضای دقیق `apply_filters()` یا `do_action()` را در محل
استفاده بررسی کن.

## مسیر سریع تغییرات

| نیاز | مسیر پیشنهادی |
|---|---|
| محتوای صفحهٔ اصلی فروشگاه | ACF برگهٔ Homepage؛ schema در `inc/acf-field-groups.php` |
| ترتیب/markup بخش‌های صفحهٔ اصلی | `template-parts/front/` و `inc/front-page.php`؛ override partial در چایلد |
| چیدمان مجله | `voiceweb/home.php`، `template-parts/blog/leather-magazine.php` و `inc/leather-magazine.php` |
| متن و محصولات مناسبت‌ها | برگهٔ مناسبت و گروه ACF `vw_gift_*`؛ [راهنما](GIFT-LANDINGS.fa.md) |
| درباره، تماس، لوگو و دسته‌ها | [مدیریت آوان چرم](AVAN-CHARM.fa.md) |
| استایل سراسری سایت | `voiceweb-child/assets/scss/global.scss` |
| استایل یک صفحه | `voiceweb-child/assets/scss/pages/{key}.scss` |
| خروجی CSS صفحه | `voiceweb-child/assets/css/pages/{key}.css` |
| JavaScript مکمل والد | `voiceweb-child/assets/js/{name}-child.js` |
| JavaScript مکمل صفحه | `voiceweb-child/assets/js/pages/{key}-child.js` |
| override یک template | همان مسیر نسبی والد در چایلد |
| override یک template part | همان مسیر نسبی والد در چایلد |
| سینگل یک post type | `template-parts/{post_type}/single-{post_type}.php` |
| تغییر رفتار PHP | filter یا action در `voiceweb-child/functions.php` |
| ترجمهٔ فرانت‌اند | `voiceweb-child/languages/{locale}.po` |
| ترجمهٔ مدیریت | `voiceweb-child/languages/admin/{locale}.po` |
| تنظیم فونت | `cn/font-config` در `voiceweb-child/functions.php` |

## چک‌لیست قبل از تحویل

1. تغییرات پروژه‌ای در چایلد باشند، مگر اینکه عمداً قابلیت پایهٔ والد را توسعه
   داده باشی.
2. تمام فایل‌های PHP تغییرکرده با `php -l` بررسی شوند.
3. هر فایل `SCSS` تغییرکرده به `CSS` متناظر کامپایل شود.
4. فایل‌های `PO` تغییرکرده به `MO` تبدیل شوند.
5. مسیر فایل‌ها و لینک‌های داخلی مستندات معتبر باشند.
6. صفحهٔ اصلی، برگهٔ نوشته‌ها، نوشتهٔ تکی، برگهٔ معمولی، دسته، جست‌وجو و `404`
   بررسی شوند.
7. نسخه‌های فارسی و انگلیسی و جهت‌های `RTL` و `LTR` بررسی شوند.
8. endpointهای AJAX و REST مرتبط با تغییر تست شوند.
9. بعد از تغییر rewrite، یک‌بار Permalinkها ذخیره شوند.
10. کش مرورگر پاک و Hard Refresh انجام شود.

## قرارداد نگهداری مستندات

- این فایل نمای کلی و وضعیت جاری پروژه را نگه می‌دارد.
- هر موضوع عمیق فقط در سند تخصصی خودش توضیح داده می‌شود.
- README والد و چایلد باید کوتاه بمانند و به این سند لینک بدهند.
- اصطلاحات فنی انگلیسی، مسیرها، نام توابع و کلیدها داخل code span نوشته شوند تا
  متن فارسی و انگلیسی در نمایش راست‌به‌چپ به‌هم نریزد.
- متن عادی فارسی است؛ جمله‌های کامل انگلیسی فقط داخل block کد یا نمونهٔ واقعی
  نگه داشته می‌شوند.
- اطلاعاتی که هنوز در کد فعال نیستند باید با برچسب روشن «غیرفعال» یا «نمونه»
  مستند شوند.
- هر تغییر رفتاری کد باید در همان تغییر، سند مرتبط را نیز به‌روزرسانی کند.

- [زیرساخت، محافظ پوشه‌ها، ثبت رویداد و بررسی دیتابیس](./INFRASTRUCTURE.fa.md)
