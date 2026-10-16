# صفحهٔ اصلی فروشگاه آوان چرم

تطبیق با کد: **۱۶ اکتبر ۲۰۲۶ / ۲۰۲۶-۱۰-۱۶**؛ نتایج تصویری ۱۴ اکتبر در انتهای سند، تاریخی‌اند.

[فهرست راهنماها](./README.fa.md) · [محتوای کیف و چرم](./LEATHER-CONTENT.fa.md) ·
[کارت‌ها و صفحات ووکامرس](./COMMERCE-DESIGN.fa.md) ·
[درخواست تماس](./INQUIRIES.fa.md) · [پایان تخفیف](./SALE-EXPIRY.fa.md)

## به‌روزرسانی آوان چرم — ۱۶ اکتبر ۲۰۲۶

برند عمومی **آوان چرم** و تلفن تأییدشدهٔ فروشگاه **09101719687** است. لوگوی برداری، برق آرام
با رعایت reduced motion و هویت مشترک در هدر/فوتر پیاده شده‌اند؛ `VoiceWeb` نام فنی قالب است.
ریشهٔ فعلی `C:/Users/baghe/Local Sites/woo-charm-main/app/public`، سایت
`http://woo-charm-main.local`، PHP CLI `8.3.30` و پورت دیتابیس `10113` است. فروشگاه با
`woocommerce_coming_soon=no` برای بازبینی محلی دیده می‌شود و `blog_public=0` دارد.

صفحهٔ اصلی به ۶ لندینگ دستهٔ محصول، هاب و ۲۰ مناسبت هدیه، درباره، تماس و `/blog/` پیوند دارد.
درباره و تماس اکنون با فیلدهای مستقل `vw_brand_*` مدیریت می‌شوند؛ بلوک معرفی خانه همچنان
`vw_front_about_*` دارد. مجلهٔ فعلی شاخهٔ چرمِ `home.php` است؛ شرح Flexible Content قدیمی فقط
برای fallback کاربرد دارد. رکوردهای قدیمی، از جمله نوشته‌های گردشگری، حذف نشده‌اند.

برای محتوای برند، راه‌های تماس و انتقال دیتابیس [AVAN-CHARM.fa.md](./AVAN-CHARM.fa.md)،
برای مدیریت محصولات مناسبت‌ها [GIFT-LANDINGS.fa.md](./GIFT-LANDINGS.fa.md) و برای رشد پس از
انتشار [SEO-ROADMAP.fa.md](./SEO-ROADMAP.fa.md) را بخوان. بررسی HTTP این مرحله جای بررسی
تصویری دوبارهٔ موبایل و دسکتاپ را نمی‌گیرد.

صفحهٔ اصلی اکنون فروشگاه قابل ویرایش با پس‌زمینهٔ کرم، سبز تیره، عکس محصول،
اسلایدر، کالکشن منتخب، دسته، پیشنهاد ویژه، داستان برند، تازه‌ها، درباره و مجله
است. حوزهٔ محتوای واردشده با تأیید کاربر **کیف و محصولات چرمی** است؛ نام‌ها،
عکس‌ها و محصولات از ACF و مدل بومی ووکامرس می‌آیند و برای فروشگاه دیگری هم
قابل تعویض‌اند.

## مسیر اجرا و فایل‌ها

| مسئولیت | فایل |
| --- | --- |
| ورودی صفحهٔ اصلی والد | [front-page.php](../front-page.php) |
| اتصال چایلد به ورودی والد | [front-page.php چایلد](../../voiceweb-child/front-page.php) |
| خواندن محتوا، queryها، زمان تخفیف و hookهای front | [inc/front-page.php](../inc/front-page.php) |
| schema فیلدها | [inc/acf-field-groups.php](../inc/acf-field-groups.php) |
| ترتیب بخش‌ها | [template-parts/front/content.php](../template-parts/front/content.php) |
| اسلایدر | [hero.php](../template-parts/front/hero.php) |
| دو کالکشن منتخب | [collections.php](../template-parts/front/collections.php) |
| دسته‌های محصول | [categories.php](../template-parts/front/categories.php) |
| مسیرهای هدیه و مناسبت‌ها | [gifts.php](../template-parts/front/gifts.php) و [inc/gift-landing.php](../inc/gift-landing.php) |
| پیشنهاد ویژه و تازه‌ها | [products.php](../template-parts/front/products.php) |
| داستان و بنر کالکشن | [story.php](../template-parts/front/story.php) |
| درباره و ارزش‌های برند | [about.php](../template-parts/front/about.php) |
| نوشته‌های مجله | [blog.php](../template-parts/front/blog.php) |
| پرسش‌های بازشونده | [faq.php](../template-parts/front/faq.php) |
| منبع ظاهر خانه و پایهٔ storefront | [pages/front.scss](../assets/scss/pages/front.scss) |
| خروجی مرورگر | [pages/front.css](../assets/css/pages/front.css) |
| رفتار اسلایدر و تایمر | [pages/front.js](../assets/js/pages/front.js) |
| هویت مشترک هدر، فوتر، نوار موبایل و ورود/پرداخت | [storefront.scss](../assets/scss/storefront.scss) و [storefront.css](../assets/css/storefront.css) |
| اندازه‌گیری ارتفاع هدر | [storefront.js](../assets/js/storefront.js) |
| قالب برگهٔ درباره | [page-templates/about.php](../page-templates/about.php) |
| قالب تماس و فیلدهای صفحات برند | [page-templates/contact.php](../page-templates/contact.php) و [inc/brand.php](../inc/brand.php) |
| استایل صفحات مستقل برند | [pages/brand-pages.scss](../assets/scss/pages/brand-pages.scss) و [CSS متناظر](../assets/css/pages/brand-pages.css) |
| محتوای لندینگ دسته‌های خرید | [inc/category-landing.php](../inc/category-landing.php) و [category-guide.php](../template-parts/woocommerce/category-guide.php) |
| مجلهٔ چرم، صفحه‌بندی و پیوندهای مرتبط | [inc/leather-magazine.php](../inc/leather-magazine.php) و [leather-magazine.php](../template-parts/blog/leather-magazine.php) |
| آماده‌سازی محتوا | [seed-leather-store.php](../tools/seed-leather-store.php) |
| آماده‌سازی برند و تنظیم محتوای سئو | [setup-avan-brand.php](../tools/setup-avan-brand.php)، [seed-gift-landings.php](../tools/seed-gift-landings.php) و [refine-avan-seo.php](../tools/refine-avan-seo.php) |
| آزمون خواندنی صفحهٔ آماده | [test-storefront-browser.cjs](../tools/test-storefront-browser.cjs) |

`functions.php` والد، `inc/front-page.php` را بعد از `inc/woocommerce.php`
require می‌کند. فایل ریشهٔ `front-page.php` فقط هدر، partial محتوای front و
فوتر را رندر می‌کند. با چایلد فعال، `voiceweb-child/front-page.php` همین ورودی
والد را require می‌کند؛ partialها همچنان با `get_template_part()` ابتدا در
چایلد و سپس والد پیدا می‌شوند. برای شخصی‌سازی یک بخش، همان مسیر
`template-parts/front/<name>.php` را در چایلد بساز.

## تنظیم صفحهٔ اصلی با بلاگ فرق دارد

در **تنظیمات ← خواندن**، حالت صفحهٔ نخست ثابت را انتخاب و برگهٔ فروشگاه
اصلی را به‌عنوان **Homepage** قرار بده. گروه `group_voiceweb_front_page`
با عنوان `Storefront homepage` و شرط `page_type == front_page` روی همان
برگه دیده می‌شود. داده‌ها روی post meta همان `page_on_front` ذخیره می‌شوند؛
`voiceweb_front_value('slides')` در واقع `get_field('vw_front_slides', $id)`
را می‌خواند.

برگهٔ مجله را جداگانه به‌عنوان **Posts page** انتخاب کن. روی این نصب، `home.php` با وجود
دستهٔ `leather-journal`، `template-parts/blog/leather-magazine.php` را رندر می‌کند و کوئری اصلی
وردپرس فقط مقاله‌های آن دسته و زیرمجموعه‌ها را می‌گیرد. همین کوئری مالک تعداد صفحه‌هاست؛
آرشیو خود دستهٔ `leather-journal` به شمارهٔ متناظر `/blog/` انتقال ۳۰۱ دارد. سایدبار مقاله‌های
چرمی نیز به همان موضوع محدود است؛ سایدبار نوشته‌های قدیمی تغییر موضوع نمی‌دهد.

گروه `group_home_blog` و Flexible Content به نام `home_sections` فقط fallback نصب بدون
دستهٔ مجلهٔ چرم است. این فیلد چیدمان صفحهٔ اصلی فروشگاه یا مجلهٔ فعلی آوان چرم را کنترل
نمی‌کند. «همهٔ نوشته‌ها» در خانه به permalink برگهٔ `page_for_posts` می‌رود.

## فیلدهای دقیق ACF

همهٔ نام‌های اصلی با `vw_front_` و keyهایشان با `field_vw_front_` شروع
می‌شوند. فیلدهای زیر توسط `voiceweb_register_front_page_fields()` روی
`acf/include_fields` با اولویت ۵ ثبت می‌شوند؛ `show_in_rest` گروه صفر است.
تعریف گروه از PHP می‌آید و import گروه هم‌کلید از JSON لازم نیست.

| بخش | نام اصلی فیلد | نوع و کاربرد |
| --- | --- | --- |
| هدر | `vw_front_header_note` | متن announcement بالای هدر دسکتاپ |
| سئو | `vw_front_seo_title`، `vw_front_seo_description` | عنوان و توضیح اختصاصی خانه در نبود افزونهٔ سئو |
| hero | `vw_front_slides` | repeater، حداکثر ۶ اسلاید |
| hero | `vw_front_autoplay` | true/false؛ پیش‌فرض خاموش |
| hero | `vw_front_interval` | number، ۵ تا ۲۰ ثانیه؛ پیش‌فرض ۸ |
| پیشنهاد | `vw_front_offers_enabled` | نمایش/پنهان‌کردن پیشنهادهای ویژه |
| پیشنهاد | `vw_front_offers_title`، `vw_front_offers_description` | عنوان و توضیح |
| پیشنهاد | `vw_front_offers_products` | relationship محصولات منتشرشده؛ حداکثر ۱۲ ID و ترتیب دستی |
| پیشنهاد | `vw_front_offers_deadline` | تاریخ/زمان با خروجی `Y-m-d H:i:s`؛ اعمال روی sale date واقعی محصولات انتخاب‌شده |
| دسته | `vw_front_categories_enabled`، `vw_front_categories_title` | نمایش و عنوان بخش |
| دسته | `vw_front_categories` | repeater کارت دسته؛ حداکثر ۱۲ |
| هدیه | `vw_front_gifts_enabled` | نمایش راهنماهای هدیه و مناسبت‌های منتشرشده |
| داستان | `vw_front_story_title`، `vw_front_story_description` | عنوان و متن داستان |
| داستان | `vw_front_story_image`، `vw_front_story_link` | attachment ID و link array |
| درباره | `vw_front_about_title`، `vw_front_about_description` | عنوان و متن بلوک معرفی خانه؛ صفحهٔ مستقل درباره از `vw_brand_*` می‌خواند |
| درباره | `vw_front_about_image`، `vw_front_about_link` | attachment ID و link array |
| بنر | `vw_front_collection_title`، `vw_front_collection_description` | عنوان و متن بنر کالکشن جدید |
| بنر | `vw_front_collection_image`، `vw_front_collection_link` | attachment ID و link array |
| کالکشن منتخب | `vw_front_promos` | repeater، حداکثر ۲ کارت تصویری |
| تازه‌ها | `vw_front_arrivals_enabled`، `vw_front_arrivals_title` | نمایش و عنوان بخش |
| تازه‌ها | `vw_front_arrivals_products` | relationship محصولات منتشرشده، حداکثر ۱۲ ID |
| مجله | `vw_front_blog_enabled`، `vw_front_blog_title`، `vw_front_blog_description` | نمایش، عنوان و توضیح |
| مجله | `vw_front_blog_link` | link array اختیاری برای «همهٔ نوشته‌ها»؛ انتخاب خالی به Posts page وردپرس برمی‌گردد |
| مجله | `vw_front_blog_posts` | relationship نوشته‌های منتشرشده، حداکثر ۳ ID |
| ارزش‌ها | `vw_front_benefits` | repeater، حداکثر ۴ مورد |
| پرسش‌ها | `vw_front_faq_title`، `vw_front_faq` | عنوان و repeater حداکثر ۶ پرسش |

tabهای رابط با نام‌های `vw_front_tab_hero`، `vw_front_tab_offers`،
`vw_front_tab_categories`، `vw_front_tab_story` و `vw_front_tab_more`
بخش‌های ویرایش را مرتب می‌کنند.

نام subfieldها داخل آرایهٔ repeater کوتاه است؛ prefix روی key آن‌ها اعمال
می‌شود و در دادهٔ یک ردیف، نام اصلی با `vw_front_` تکرار نمی‌شود:

| repeater | نام subfieldها |
| --- | --- |
| `vw_front_slides` | `eyebrow`، `title`، `description`، `image`، `mobile_image`، `primary`، `secondary`، `tone`، `floating_images` |
| `floating_images` داخل اسلاید | `image`، حداکثر ۳ تصویر |
| `vw_front_categories` | `term`، `label`، `description`، `image` |
| `vw_front_promos` | `title`، `description`، `image`، `link`، `tone` |
| `vw_front_benefits` | `title`، `description`، `icon` |
| `vw_front_faq` | `question`، `answer` |

تصاویر `return_format=id` و لینک‌ها `return_format=array` دارند. `term`
به taxonomy بومی `product_cat` اشاره می‌کند و انتخاب آن دسته‌ای جدید نمی‌سازد.
مقادیر icon ارزش‌ها `check`، `truck`، `shield` و `user` هستند. مقادیر
`tone` در schema برابر `ice`، `ink` و `rose` است. hero و کالکشن منتخب
با allowlist این سه مقدار، `data-tone` را در HTML می‌گذارند و CSS کرم،
سبز تیره یا رز روشن را همراه رنگ متن/دکمهٔ مناسب اعمال می‌کند؛ مقدار نامعتبر
به `ice` برمی‌گردد. عنوان اسلاید بدون متن از فهرست
اسلایدر حذف می‌شود؛ وقتی اسلاید معتبری نداریم، یک عنوان و لینک پیش‌فرض نمایش
داده می‌شود.

## ترتیب و انتخاب محتوا

ترتیب فعلی در `content.php` چنین است: hero، دو کالکشن منتخب، دسته‌ها، راهنماهای هدیه،
پیشنهاد ویژه، داستان/بنر کالکشن، تازه‌ها، درباره/ارزش‌ها، مجله و پرسش‌ها.
خاموش‌کردن toggleهای پیشنهاد، تازه‌ها، دسته و مجله همان بخش را پنهان
می‌کند. برای حذف داستان، بنر یا درباره عنوان مربوط را خالی کن؛ برای حذف
کالکشن منتخب یا پرسش‌ها، ردیف‌های repeater را بردار.

محصولات انتخاب‌شده طبق ترتیب relationship، با حداکثر ۱۲ ID خوانده می‌شوند.
بدون انتخاب دستی، شش محصول جدید public انتخاب می‌شوند؛ برای پیشنهادها اگر
محصول on-sale وجود داشته باشد، فهرست sale بومی مبنای انتخاب است. اگر sale
وجود نداشته باشد، fallback همان تازه‌هاست و شمارندهٔ تخفیف ساختگی ساخته
نمی‌شود. visibility ووکامرس و گزینهٔ پنهان‌کردن ناموجودها رعایت می‌شود.

دستهٔ بدون label سفارشی، نام واقعی taxonomy را نشان می‌دهد و تصویر خالی آن
از `thumbnail_id` دسته می‌آید. بدون انتخاب دسته، حداکثر شش دستهٔ دارای
محصول، به‌جز دستهٔ پیش‌فرض ووکامرس، استفاده می‌شوند. لینک‌ها از
`get_term_link()` می‌آیند. در موبایل این بخش با scroll-snap افقی، مستقل از
شبکهٔ دو ستونی محصولات است.

نوشته‌های مجله حداکثر سه نوشتهٔ منتشرشده‌اند؛ انتخاب دستی ترتیب را تعیین
می‌کند و انتخاب خالی تازه‌ترین نوشته‌ها را می‌آورد. query با
`suppress_filters=false` به فیلترهای زبان/وردپرس احترام می‌گذارد. عنوان،
excerpt، تاریخ و لینک از خود نوشته هستند. لینک آرشیو مجله را می‌توان با
`vw_front_blog_link` تنظیم کرد؛ در آوان چرم مقصد آن `/blog/` است تا یک ورودی اصلی برای مجله
بماند. انتخاب خالی همان لینک برگهٔ نوشته‌های وردپرس را استفاده می‌کند. سه نوشتهٔ منتخب این
نصب چرمی‌اند؛ اگر انتخاب دستی را خالی کردی، موضوع نوشته‌های fallback را نیز بررسی کن.
پرسش‌ها از عنصر بومی `details`
استفاده می‌کنند و بدون JavaScript هم باز می‌شوند.

## اسلایدر و بارگذاری تصویر

اولین اسلاید eager و `fetchpriority=high` دارد. `voiceweb_front_preload_image()`
روی `wp_head` با اولویت ۲ تصویر نخست را با `srcset/sizes` preload می‌کند؛
اگر تصویر موبایل مستقل باشد media شرطی، دانلود هم‌زمان تصویر نامناسب را
کم می‌کند. `<picture>` زیر ۷۶۸px نسخهٔ mobile را برمی‌گزیند.

اسلایدهای بعدی با `hidden`، `inert` و `aria-hidden=true` شروع می‌شوند.
`src/srcset` تصویر و `source` آن‌ها در `data-vw-src/data-vw-srcset` نگه
داشته می‌شود؛ `src` اولیه فقط GIF کوچک داخلی است. هنگام نمایش، JavaScript
URL واقعی را فعال می‌کند. عکس‌های غیرhero lazy هستند؛ اندازهٔ واقعی و
`sizes` روی خروجی WordPress وجود دارد. عکس‌های تزئینی در این partialها
alt خالی دارند و متن/عنوان کنارشان محتوای بخش را بیان می‌کند؛ عکس native
محصول از قرارداد رسانهٔ ووکامرس استفاده می‌کند.

در صورت چند اسلاید، کنترل بعدی، قبلی، نقطه‌ها و اعلام وضعیت به فناوری کمکی
وجود دارد. کلیدهای `ArrowLeft`/`ArrowRight` با جهت RTL/LTR هماهنگ‌اند؛
`Home`/`End` نخستین/آخرین اسلاید را انتخاب می‌کند. swipe لمسی بیش از ۵۰px
و عمدتاً افقی، با اسکرول عمودی تداخل کمتری دارد و چرخش خودکار را متوقف
می‌کند. روی لینک یا دکمه، gesture شروع نمی‌شود.

Autoplay پیش‌فرض خاموش است. در صورت فعال‌کردن، روی hover، فوکوس داخل
اسلایدر، مخفی‌شدن صفحه، دکمهٔ توقف و `prefers-reduced-motion: reduce`
تایمر چرخش مکث می‌کند. تغییرات visibility و media و بازگشت صفحه دوباره
شرایط را بررسی می‌کنند. اسلاید inactive قابل Tab نیست؛ جابه‌جایی خودکار
اعلان aria-live مکرر نمی‌سازد. بدون JavaScript نخستین اسلاید و لینک اصلی
قابل استفاده‌اند و کنترل‌هایی که کار نمی‌کنند پنهان می‌مانند.

## زمان پایان پیشنهاد ویژه

`voiceweb_front_offer_deadline()` تاریخ را از `get_date_on_sale_to()` واقعی
محصول ساده و variationهای قابل نمایش، منتشرشده و موجود می‌خواند. فقط
`is_on_sale()` فعال با پایان آینده لحاظ می‌شود؛ زودترین پایان بین آن‌ها
زمان شمارندهٔ مشترک بخش است. محصول بدون پایان زمان‌دار، تخفیف یا زمان
ساختگی نمی‌گیرد. تفاوت پایان variationها به معنی زودترین پایان در بخش است؛
تایمر صفحهٔ محصول برای گزینهٔ انتخاب‌شده جداگانه کار می‌کند.

فیلد `vw_front_offers_deadline` یک متن نمایشی مستقل نیست. hook
`voiceweb_front_save_offer_deadline` روی `acf/save_post` با اولویت ۳۰، پس
از ذخیرهٔ ACF، فقط این شرایط را می‌پذیرد:

- post ID عددی همان `page_on_front` و مجوز `edit_post` برای برگه؛
- تاریخ قابل parse در timezone خود WordPress و در آینده؛
- حداکثر ۱۲ ID یکتای محصول انتخاب‌شده؛
- محصول منتشرشده و مجوز `edit_post` برای همان محصول؛
- محصول/variation دارای sale price موجود.

این hook تاریخ پایان sale موجود را ذخیره می‌کند و برای متغیر از
`WC_Product_Variable::sync()` استفاده می‌کند؛ مقدار regular/sale جدید
اختراع نمی‌کند. بدون انتخاب دستی، فیلد زمان روی محصولات خودکار اعمال
نمی‌شود. خالی‌کردن فیلد، تاریخ قبلی محصولات را پاک نمی‌کند؛ برای حذف یا
ویرایش مستقل برنامهٔ تخفیف از تنظیم همان محصول/variation در ووکامرس استفاده
کن. قیمت‌ها و برنامه‌های تخفیف پیش از استفادهٔ واقعی فروشگاه بررسی شوند.

مقدار `data-vw-offer-deadline` در HTML میلی‌ثانیه است. شمارنده با
`Intl.NumberFormat` و چهار واحد روز/ساعت/دقیقه/ثانیه رندر می‌شود. یک HEAD
خواندنی با timeout پنج‌ثانیه‌ای Date سرور را برای اختلاف ساعت دستگاه
می‌خواند؛ تصمیم پایان تا دریافت ساعت سرور به تعویق می‌افتد. در پایان، پیام پایان
نمایش داده می‌شود و پس از عبور ثانیهٔ پایانی بومی Woo، یک GET بدون cache برای تازه‌کردن کارت‌های همان بخش
انجام می‌شود. این درخواست timeout دوازده‌ثانیه‌ای دارد و دادهٔ خرید یا
پرداختی ارسال نمی‌کند. event `voiceweb:catalog-updated` اتصال مجدد دکمه‌ها
را فراهم می‌کند. هنگام `pagehide` interval متوقف و در بازگشت bfcache
دوباره آغاز می‌شود.

درخواست ساعت و تازه‌سازی از URL عمومی canonical و فقط queryهای نمایش استفاده می‌کنند؛
پارامترهای عملیاتی مثل `add-to-cart` هرگز دوباره درخواست نمی‌شوند. پاسخ HTML منقضی
از cache جای کارت تازه قرار نمی‌گیرد.

قیمت سمت سرور همچنان native WooCommerce است. برای برنامهٔ دیر اجراشده و
cache قیمت variation، [sale-expiry.php](../inc/woocommerce/sale-expiry.php)
از handler بومی Woo استفاده می‌کند؛ شرح lease، محدودیت ۲۵ محصول و hash
مرحلهٔ زمانی در [راهنمای پایان تخفیف](./SALE-EXPIRY.fa.md) آمده است.

## هدر، فوتر و درباره

`storefront.scss` یک component ریشه است و خروجی CSS و JS هم‌نام، با لودر
موجود روی صفحات کشف می‌شوند. این فایل هدر، منوی حساب، پنل موبایل، فوتر،
نوار چهارگانهٔ موبایل، مودال ورود و پنل پرداخت را با طرح فروشگاه هماهنگ
می‌کند. منوی حساب، cart badge، accordion فوتر و modal ورود همان رفتارهای
قبلی خود را دارند؛ route یا API موازی ساخته نشده است.

`storefront.js` ارتفاع واقعی `.cn-site-header` را اندازه می‌گیرد و با
`ResizeObserver`، یا fallback رویداد resize، متغیر `--vw-header-height`
را به‌روز می‌کند. scroll-padding، scroll-margin و فضای هدر ثابت موبایل
از آن استفاده می‌کنند تا عنوان و مقصد لینک زیر هدر جدید نرود. فونت فارسی
سه WOFF2 موجود Dana با وزن‌های ۴۰۰/۵۰۰/۷۰۰ است؛ [مرجع فونت](./FONTS.fa.md)
را ببین.

صفحهٔ مستقل درباره از قالب **دربارهٔ برند** در `page-templates/about.php` و تماس از
`page-templates/contact.php` استفاده می‌کند. عنوان از برگه و متن داستان از editor همان
برگه می‌آید؛ تصویر، مقدمه، مراحل تولید، انواع چرم و پرسش‌های درباره در `vw_brand_*` همان
برگه ذخیره‌اند. این فیلدها با معرفی کوتاه خانه مشترک نیستند. محتوای فعلی درباره، ساخت
صفر تا صد در تیم و انتخاب چرم طبیعی گاوی یا بزی متناسب با محصول را توضیح می‌دهد.
اطلاعات تماس فقط با دادهٔ تأییدشده نمایش داده می‌شود؛ تلفن فعلی **09101719687** است.
هر دو قالب کلیدهای asset `front` و `brand-pages` دارند و پیوندهای خرید، دسته و تماس واقعی
می‌سازند. [راهنمای مدیریت و محتوای برند](./AVAN-CHARM.fa.md)

## محتوا، build و آزمون

برای محصولات، تنوع‌ها، عکس‌های تولیدشده و روش داده‌گذاری به
[LEATHER-CONTENT.fa.md](./LEATHER-CONTENT.fa.md) مراجعه کن. ابزار seed،
CLI-only است و بدون `--apply` گزارش می‌دهد. روی نصب دیگر به شناسه‌های این
نصب تکیه نکن؛ backup دیتابیس و uploads، همراه کد لازم است. ویرایش‌های
بعدی مشتری باید از مدیریت WordPress/ACF/WooCommerce انجام شوند.

دو entry منبع را پس از تغییر Sass به CSS کامپایل کن:

```powershell
npx sass wp-content/themes/voiceweb/assets/scss/pages/front.scss wp-content/themes/voiceweb/assets/css/pages/front.css --no-source-map --style=compressed
npx sass wp-content/themes/voiceweb/assets/scss/storefront.scss wp-content/themes/voiceweb/assets/css/storefront.css --no-source-map --style=compressed
```

کارت صفحهٔ اصلی همان [product-card.php](../template-parts/woocommerce/product-card.php)
است؛ از قیمت native، رنگ‌های Variation Swatches، wishlist و CTA بومی استفاده
می‌کند. برای قرارداد کارت‌ها [COMMERCE-DESIGN.fa.md](./COMMERCE-DESIGN.fa.md)
و برای جایگزینی خرید با شماره تماس [INQUIRIES.fa.md](./INQUIRIES.fa.md) را
بخوان. تنظیمات ورود/پیامک/درگاه در فیلدهای این گروه قرار ندارند.

آزمون [test-storefront-browser.cjs](../tools/test-storefront-browser.cjs)
صفحهٔ آماده و ACF موجود را می‌خواند؛ محتوا، defer تصاویر، کنترل کیبورد،
تایمر و بازگشت bfcache، accordion فوتر، reduced motion، درباره، حالت
بدون JavaScript و عرض‌های ۳۲۰/۳۹۰/۷۶۸/۱۲۸۰/۱۴۴۰ را بررسی می‌کند. شرط
آماده‌بودن محتوا، حداقل دو اسلاید و پیشنهاد با پایان آینده است. این آزمون
کاربر، محصول، درخواست تماس، سفارش، پیامک یا پرداخت جدیدی ثبت نمی‌کند؛
POSTهای حساس و درخواست‌های خارجی در مرورگر مسدود می‌شوند.

نمونهٔ اجرای محلی از ریشهٔ فعلی WordPress؛ این فرمان ابزار موجود است و نتیجهٔ تاریخی پایین
را به اجرای جدید تبدیل نمی‌کند. در جلسه‌های دارای محدودیت کنترل مرورگر، بررسی UI فقط از
مسیر CUA مجاز همان جلسه انجام می‌شود:

```powershell
$env:VOICEWEB_WC_SITE = 'http://woo-charm-main.local'
$env:VOICEWEB_WC_MYSQL_PORT = '10113'
$env:VOICEWEB_PHP = (Get-Command php).Source
# در صورت نصب خارج از پروژه، مسیر Playwright و مرورگر را در همین session تنظیم کن.
# $env:VOICEWEB_PLAYWRIGHT_PATH = '.../node_modules/playwright'
# $env:VOICEWEB_BROWSER_EXECUTABLE = '.../msedge.exe'
node wp-content/themes/voiceweb/tools/test-storefront-browser.cjs
```

اختیاری: `VOICEWEB_WC_SCREENSHOTS` مسیر عکس‌های QA را تعیین می‌کند.

نتیجهٔ تاریخی ثبت‌شدهٔ اجرای **۱۴ اکتبر ۲۰۲۶** در محیط پیشین: **PASS** روی محتوای تنظیم‌شده، با دو
اسلاید، شش دسته، چهار پیشنهاد، شش تازه و سه نوشته. defer hero، کیبورد،
شمارنده و بازگشت bfcache، accordion فوتر، reduced motion، بدون JavaScript
و پنج عرض یادشده موفق بودند. خطای JavaScript، سفارش، پیامک و پرداخت صفر
بود. این تعدادها snapshot محتوای آن اجرا هستند و الزام ثابت قالب نیستند؛
با تغییر محتوای سایت می‌توانند تغییر کنند.
