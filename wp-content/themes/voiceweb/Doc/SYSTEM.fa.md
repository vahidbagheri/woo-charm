# معماری اجرایی قالب VoiceWeb

> راهنمای اصلی پروژه: [README.fa.md](./README.fa.md)

این سند فقط معماری اجرای کد را توضیح می‌دهد. نصب، مسیرهای روزمره و چک‌لیست تحویل
در سند اصلی آمده‌اند و اینجا تکرار نمی‌شوند.

## نمای کلی

```text
WordPress request
      │
      ├─ افزونه‌های فعال → plugins_loaded
      │    ├─ VoiceWeb Auth: هویت، پیامک و UI ورود
      │    └─ VoiceWeb Payments: درگاه، وبهوک و رسید
      │
      ├─ functions.php چایلد
      │    └─ hookها و قابلیت‌های اختصاصی سایت
      │
      ├─ functions.php والد
      │    ├─ setup قالب و ترجمه‌ها
      │    ├─ helperها و تنظیمات ACF
      │    ├─ فونت و assetها
      │    ├─ جست‌وجو و حریم خصوصی
      │    ├─ ووکامرس، دفترچهٔ آدرس و درخواست تماس
      │    ├─ helperهای صفحهٔ اصلی فروشگاه و زمان تخفیف
      │    └─ برند، مناسبت‌ها، سئوی دسته و مجلهٔ چرم
      │
      ├─ WordPress Template Hierarchy
      │    └─ template چایلد، سپس template والد
      │
      └─ get_header() → محتوای صفحه → get_footer()
```

## مرحلهٔ bootstrap

### چایلد

`voiceweb-child/functions.php` پیش از والد اجرا می‌شود. وظایف فعلی آن:

- یکسان‌سازی رقم‌های فارسی و لاتین با زبان فرانت‌اند.

در فایل فعلی چایلد، endpoint مجازی `/categories/` یا handler آرشیو دسته ثبت نشده است. مثال‌های این مسیر در یادداشت‌های قدیمی، پیاده‌سازی فعال این نسخه نیستند.

این فایل برای enqueue دستی asset استفاده نمی‌شود؛ لودر والد assetهای چایلد را
نیز resolve می‌کند.

### والد

`voiceweb/functions.php` پس از guard و مقدار پیش‌فرض `content_width`، فایل‌ها را با
ترتیب `require_once` زیر آماده می‌کند:

```text
helpers.php → security.php → log.php → fonts.php → assets.php
→ acf-field-groups.php → admin.php → site-settings.php → search.php
→ actions.php → filters.php → ajax-callbacks.php → woocommerce.php → front-page.php
→ gift-landing.php → brand.php → category-landing.php → leather-magazine.php
```

ثبت hook با اجرای callback آن یکسان نیست؛ callbackها در مرحلهٔ متناظر وردپرس اجرا می‌شوند.
برای نمونه، `cn_setup_theme()` در `inc/actions.php` تعریف و روی `after_setup_theme` ثبت است.
افزونه‌های فعال پیش از functions قالب بارگذاری شده‌اند. در حالت واردکردن یک‌خطی Auth
در functions نیز bootstrap آن وضعیت `plugins_loaded` را بررسی می‌کند تا بوت از دست نرود.

فایل `inc/cache.php` در این مرحله require نمی‌شود و غیرفعال است.

## resolve فایل‌ها

### template و template part

WordPress برای template کامل ابتدا چایلد را بررسی می‌کند. تابع
`get_template_part()` نیز همین اولویت را برای partialها دارد:

```php
get_template_part( 'template-parts/blog/card', 'grid', $args );
```

ترتیب resolve نمونهٔ بالا:

```text
voiceweb-child/template-parts/blog/card-grid.php
voiceweb/template-parts/blog/card-grid.php
```

### asset قابل جایگزینی

تابع `cn_asset()` از `get_theme_file_path()` و `get_theme_file_uri()` استفاده
می‌کند؛ پس فایل هم‌مسیر چایلد، نسخهٔ والد را shadow می‌کند.

### asset متعلق به والد

تابع `cn_parent_asset()` فقط مسیر والد را می‌خواند. Bootstrap با این روش لود
می‌شود تا نسخهٔ vendor تصادفی با فایل چایلد جایگزین نشود.

### فایل‌های PHP قالب

بیشتر فایل‌های `inc/` با `get_template_directory()` require می‌شوند و فقط متعلق
به والد هستند. فقط helper زیر با `get_theme_file_path()` بارگذاری می‌شود:

```text
inc/helpers.php
```

بنابراین فقط این فایل قابلیت override کامل در چایلد دارد؛ برای تغییر کوچک از hook
استفاده کن. `inc/acf-field-groups.php` از والد می‌آید.

## انتخاب template

### صفحهٔ اصلی فروشگاه و درباره

برگهٔ ثابت `page_on_front` با `front-page.php` رندر می‌شود. فایل چایلد فعلی
ورودی والد را require می‌کند؛ والد به `template-parts/front/content.php`
می‌رود و آن partial ترتیب hero، کالکشن، دسته، پیشنهاد ویژه، داستان، تازه‌ها،
درباره، مجله و FAQ را تعیین می‌کند. دادهٔ صفحهٔ اصلی از `vw_front_*` روی
همان برگه و helperهای `inc/front-page.php` می‌آید. `home_sections` متعلق
به صفحهٔ نوشته‌هاست.

قالب‌های `page-templates/about.php` و `contact.php` متن اصلی و فیلدهای `vw_brand_*` خود برگه را می‌خوانند و کلیدهای `front` و `brand-pages` می‌گیرند. لندینگ‌های مناسبتی از `vw_gift_*` و راهنمای دسته از `vw_cat_*` استفاده می‌کنند. [راهنمای آوان چرم](AVAN-CHARM.fa.md) قرارداد محتوای این صفحات را نگه می‌دارد. `assets/{css,js}/storefront.*` componentهای
سراسری هویت هدر/فوتر هستند؛ JS ارتفاع واقعی هدر را در
`--vw-header-height` قرار می‌دهد. [شرح صفحهٔ اصلی](./HOMEPAGE.fa.md)

### صفحهٔ نوشته‌ها

برگه‌ای که در **Settings → Reading → Posts page** انتخاب شده است با `home.php`
رندر می‌شود. چایلد فعلی `home.php` ندارد و فایل والد فعال است.

با وجود دستهٔ `leather-journal`، query اصلی وردپرس به مقاله‌های چرم و زیردسته‌های آن محدود می‌شود و `template-parts/blog/leather-magazine.php` فهرست را با صفحه‌بندی بومی می‌سازد. نشانی اصلی `/blog/` است و فهرست قدیمی همین دسته با ۳۰۱ به آن می‌رود. مقاله‌ها و نشانی آن‌ها حفظ شده‌اند.

در نبود این دسته، والد دادهٔ `home_sections` را با `get_field()` به آرایه تبدیل می‌کند و هر سکشن را در `$args` به `template-parts/blog/section-{layout}.php` می‌فرستد. این حالت جایگزین است و در مجلهٔ فعال آوان چرم استفاده نمی‌شود.

### سینگل post type

`single.php` یک router مشترک است:

```php
$post_type = sanitize_key( (string) get_post_type() );
$scope = 'post' === $post_type ? 'blog' : $post_type;
get_template_part( "template-parts/{$scope}/single", $post_type );
```

برای `post` فایل `single-post.php` بارگذاری می‌شود. fallback عمومی وجود ندارد.

### برگهٔ معمولی

`page.php` Loop استاندارد، `h1`، `the_content()` و `wp_link_pages()` را رندر
می‌کند. asset اختصاصی `page` فقط در صورت وجود فایل روی دیسک enqueue می‌شود.

## مسیر assetها

```text
wp_enqueue_scripts
      │
      ├─ Bootstrap والد
      ├─ فونت زبان جاری
      ├─ global.css و global.js
      ├─ componentهای ریشهٔ assets/{css,js}
      ├─ pages/{page-key}.{css,js}
      └─ افزونه‌های *-child.js چایلد
```

### asset سراسری

این دو مسیر همیشه بررسی می‌شوند:

```text
assets/css/global.css
assets/js/global.js
```

اگر چایلد فایل هم‌مسیر داشته باشد، همان فایل جایگزین والد می‌شود.

### component سراسری

فایل‌های ریشهٔ `assets/css/` و `assets/js/` به‌صورت خودکار کشف می‌شوند. موارد
رزروشده یا vendor مانند `global`، `fonts` و `bootstrap` از discovery کنار گذاشته
می‌شوند.

نمونه‌های فعلی:

```text
header.css
footer.css
header.js
footer.js
```

### asset صفحه

تابع `cn_page_keys()` context را تعیین می‌کند. برای هر کلید، این دو مسیر بررسی
می‌شوند:

```text
assets/css/pages/{key}.css
assets/js/pages/{key}.js
```

نبود فایل خطا نیست؛ enqueue آن asset صرفاً رد می‌شود.

### JavaScript مکمل چایلد

فایل `<name>-child.js` فقط زمانی enqueue می‌شود که اسکریپت والد متناظر قبلاً
enqueue شده باشد. نمونه:

```text
assets/js/header-child.js
assets/js/pages/home-child.js
```

اسکریپت مکمل به handle والد dependency دارد و پس از آن اجرا می‌شود.

## فونت

```text
config/fonts.php
      │
      ├─ cn/font-config
      ├─ تشخیص زبان WPML یا locale
      ├─ resolve فایل با اولویت چایلد
      ├─ تولید @font-face در صورت نیاز
      └─ preload فایل‌های علامت‌خورده
```

متغیر `--cn-font-family` روی صفحه تنظیم می‌شود و استایل‌ها باید تا حد ممکن فونت
را از `body` به ارث ببرند.

## ACF

چهار گروه قالب از `inc/acf-field-groups.php` روی `acf/include_fields` با
اولویت ۵ ثبت می‌شوند. سه گروه اولیه در `cn_register_acf_field_groups()` و
گروه storefront در `voiceweb_register_front_page_fields()` هستند:

| group key | محل نمایش | قرارداد داده |
| --- | --- | --- |
| `group_home_blog` | `page_type == posts_page` | `home_sections` و layoutهای مجله |
| `group_cn_site_header_footer` | `options_page == cn-site-settings-header-footer` | گزینه‌های سایت و فهرست‌ها |
| `group_cn_term_icon` | `taxonomy == all` | آیکون Iconly term |
| `group_voiceweb_front_page` | `page_type == front_page` | `vw_front_*` و repeaterهای فروشگاه |

این گروه‌ها `show_in_rest=0` دارند و schema از Local PHP می‌آید. keyهای
قدیمی `cn_*` برای سازگاری داده حفظ شده‌اند؛ rebrand نام نمایشی، دلیل
تغییر کلیدهای ذخیره‌شده نیست.

### Flexible Content خانهٔ بلاگ

کد به فیلد `home_sections` وابسته است. گروه `group_home_blog` روی hook
`acf/include_fields` از `inc/acf-field-groups.php` ثبت می‌شود و همراه آپدیت قالب
به‌روز می‌گردد.

### Local JSON

filter به نام `acf/settings/load_json` مسیرهای زیر را به ACF معرفی می‌کند:

```text
voiceweb/acf-json
voiceweb-child/acf-json
```

این مسیرها برای گروه‌های مستقل آینده و توسعهٔ چایلد حفظ شده‌اند؛ گروه‌های اصلی
قالب از Local PHP می‌آیند و به Local JSON وابسته نیستند.

### Options Page

`inc/site-settings.php` صفحهٔ تنظیمات را ثبت می‌کند و
`inc/acf-field-groups.php` Field Group آن را با PHP فراهم می‌کند. import دستی
لازم نیست.

### آیکون دسته

`inc/acf-field-groups.php` Field Group کوچک آیکون دسته را با PHP ثبت می‌کند. این
بخش به Local JSON وابسته نیست.

### صفحهٔ اصلی فروشگاه

گروه `group_voiceweb_front_page` مستقل از `group_home_blog` است. فیلدها
روی post meta برگهٔ ثابت ذخیره می‌شوند؛ query محصول/نوشته از مدل بومی
خوانده می‌شود. `acf/save_post` با اولویت ۳۰ فقط برای همان برگه، با بررسی
مجوز و حداکثر ۱۲ محصول انتخاب‌شده، تاریخ پایان آینده را به sale موجود
می‌نویسد. شمارنده از تاریخ واقعی sale/variation قابل نمایش می‌آید؛
[HOMEPAGE.fa.md](./HOMEPAGE.fa.md) جزئیات فیلدها و این مسیر را دارد.

## کارت و query

`cn_card()` یک ورودی یکپارچه برای کارت‌هاست:

```php
cn_card( 'grid', array( 'post_id' => $post_id ) );
```

جریان رندر:

```text
cn/card/name
→ cn/card/args
→ cn/card/before
→ template-parts/blog/card-{name}.php
→ cn/card/after
```

تابع `cn_query_post_ids()` کوئری‌های تکراری را یکدست می‌کند و پیش از اجرا
`cn/query/args` را اعمال می‌کند. `suppress_filters` در کوئری‌های محتوایی false
است تا فیلتر زبان `WPML` فعال بماند.

## جست‌وجو

جست‌وجوی هدر و صفحهٔ جست‌وجو هر دو post typeهای عمومی را از وردپرس می‌گیرند.
جریان AJAX هدر:

```text
عبارت کاربر
→ nonce و حداقل دو نویسه
→ تشخیص زبان
→ WP_Query
→ آماده‌سازی عنوان، نوع، URL و thumbnail
→ پاسخ JSON
```

فیلترهای اصلی این بخش:

```text
cn/search/post_types
cn/search/query_args
```

## ترجمه

### فرانت‌اند

helper `cn_translate()` ابتدا ترجمهٔ چایلد و سپس والد را بررسی می‌کند. اگر هیچ
ترجمه‌ای موجود نباشد، متن مبدأ را برمی‌گرداند.

### مدیریت

helper `cn_translate_admin()` اول دامنهٔ `voiceweb-admin` و بعد
`voiceweb-child-admin` را بررسی می‌کند. این ترتیب عمداً اجازه نمی‌دهد ترجمهٔ
موجود والد به‌صورت ناخواسته در چایلد عوض شود.

### ACF

دامنهٔ مجازی `voiceweb-acf` labelها، instructions، choices و description گروه را
به زنجیرهٔ ترجمهٔ مدیریت می‌فرستد. در صفحهٔ ساخت و ابزارهای Field Group، ترجمه
خاموش می‌شود تا منبع انگلیسی گروه‌های ثالث دست‌نخورده بماند. گروه‌های خود قالب
فقط از PHP تغییر می‌کنند.

## حریم خصوصی

برای کاربر ناشناس:

```text
/wp/v2/users*       → 404
author archive      → 404
users sitemap       → حذف provider
```

کاربران واردشده و ابزارهای مدیریت همچنان به API استاندارد دسترسی دارند.

## قابلیت‌های موجود اما غیرفعال

### نسخه‌گذاری URL تصویر

`inc/cache.php` فیلترهای لازم را دارد، اما require آن در `functions.php` کامنت
است. فعال‌کردن این فایل یک تغییر رفتاری جداگانه است و باید پس از تست تصاویر
محتوا، `srcset` و CDN انجام شود.

## ووکامرس فعال و افزونه‌های مستقل

ووکامرس از فایل ورودی `inc/woocommerce.php` و توابع معمول فایل‌های
`inc/woocommerce/` استفاده می‌کند. فایل‌های ظاهری با کلیدهای مخصوص صفحه وارد لودر موجود
می‌شوند و مسیر حساب، دفترچهٔ آدرس و checkout در [راهنمای ووکامرس](./WOOCOMMERCE.fa.md) آمده است.

`inc/woocommerce.php` این ۱۳ فایل عادی را به ترتیب بارگذاری می‌کند:

```text
address-store → address-api → assets → account → checkout → templates
→ mobile-navigation → wishlist → orders → catalog
→ inquiries → product-display → sale-expiry
```

hookهای عمومی در فایل ورودی ثبت می‌شوند؛ فایل‌های `inquiries.php`،
`product-display.php` و `sale-expiry.php` hookهای قابلیت خود را نیز ثبت
می‌کنند. درخواست تماس، ظاهر محصول و پایان تخفیف در همان ساختار سادهٔ
توابع قرار دارند. چارچوب
`inc/Modules` و کلاس‌های Tour حذف شده‌اند و نباید به‌عنوان زیرساخت این بخش برگردند.

Auth و Payments در `wp-content/plugins/` می‌مانند و دارایی‌های خود را جدا بارگذاری می‌کنند.
قالب، UI حساب/فروشگاه را دارد؛ Auth مالک هویت تأییدشده و Payments مالک تأیید پرداخت است.
ایجاد سفارش، مالیات، حمل، وضعیت و موجودی همچنان با API بومی ووکامرس انجام می‌شوند.
جزئیات در [ورود](./AUTH.fa.md)، [پرداخت](./PAYMENTS.fa.md) و
[سند تحویل](./HANDOFF.fa.md) آمده است.

کارت home، shop و related به یک partial مشترک وصل است. تاریخ پایان از
sale native گرفته می‌شود و fallback پایان تخفیف از handler بومی Woo با
lease و batch محدود استفاده می‌کند. شرح لایه‌ها در
[طراحی فروشگاه](./COMMERCE-DESIGN.fa.md)، [درخواست تماس](./INQUIRIES.fa.md)
و [پایان تخفیف](./SALE-EXPIRY.fa.md) آمده است.

## جریان نمونهٔ یک نوشتهٔ بلاگ

```text
درخواست permalink نوشته
→ WordPress تشخیص is_singular('post')
→ کلید asset برابر single-post
→ single.php والد
→ the_post()
→ template-parts/blog/single-post.php
→ the_content + cn_table_of_contents
→ cn_query_post_ids برای آخرین نوشته‌ها
→ cn_card('latest') برای سایدبار
→ comments.php در صورت بازبودن کامنت
→ footer.php
```

## جریان نمونهٔ صفحهٔ اصلی فروشگاه

```text
برگهٔ ثابت Homepage در Settings → Reading
→ front-page.php چایلد → front-page.php والد
→ template-parts/front/content.php
→ voiceweb_front_value برای ACF برگهٔ اصلی
→ voiceweb_front_products / categories / posts
→ partialهای front و product-card مشترک
→ کلید front + دارایی Woo و Wishlist مورد نیاز
→ front.js برای اسلایدر و شمارنده، storefront.js برای ارتفاع هدر
→ footer.php
```

## جریان نمونهٔ صفحهٔ نوشته‌ها

```text
برگهٔ انتخاب‌شده در Settings → Reading
→ home.php چایلد
→ have_rows('home_sections')
→ layout جاری
→ template-parts/blog/section-{layout}.php
→ helperهای query، image و card
→ footer.php
```

## مرز مسئولیت فایل‌ها

| نوع تغییر | فایل یا لایه |
|---|---|
| ترتیب و ساختار template | فایل‌های ریشهٔ قالب |
| markup قابل استفادهٔ مجدد | `template-parts/` |
| query، تصویر، ترجمه و کارت | `inc/helpers.php` |
| asset و context صفحه | `inc/assets.php` |
| فونت | `config/fonts.php` و `inc/fonts.php` |
| تنظیمات سایت | `inc/site-settings.php` |
| محتوای صفحهٔ اصلی و برنامهٔ پیشنهادی تخفیف | `inc/front-page.php` و `template-parts/front/` |
| دفترچه، سفارش‌های حساب، علاقه‌مندی و فیلتر فروشگاه | `inc/woocommerce/` |
| هویت و سرویس پیامک | `wp-content/plugins/voiceweb-auth/` |
| پرداخت و رسید | `wp-content/plugins/voiceweb-payments/` |
| رفتار اختصاصی سایت | `voiceweb-child/functions.php` |
| style منبع | `assets/scss/` |
| style قابل استفادهٔ مرورگر | `assets/css/` |

برای قواعد اجرایی روزمره و چک‌لیست نهایی به
[راهنمای یکپارچه](./README.fa.md) برگرد.
