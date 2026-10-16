# مرجع `inc/acf-field-groups.php`

[مشاهدهٔ فایل منبع](../../inc/acf-field-groups.php)

## مسئولیت

این فایل چهار Field Group را به‌صورت Local PHP ثبت می‌کند: ساختار سکشن‌های برگهٔ
نوشته‌ها، تنظیمات هدر/فوتر، آیکون دسته و صفحهٔ اصلی فروشگاه. ثبت کدنویسی‌شده باعث می‌شود schema همراه قالب
منتقل شود و برای راه‌اندازی به import یا sync دیتابیس نیاز نباشد.

فایل با guard مربوط به `ABSPATH` محافظت شده است. دو callback خوانا
`cn_register_acf_field_groups()` و `voiceweb_register_front_page_fields()` اگر
`acf_add_local_field_group()` وجود نداشته باشد، بدون خطا خارج می‌شوند. ثبت روی
`acf/include_fields` با اولویت `5`
است تا تعریف PHP پیش از Local JSON وارد شود.

گروه `group_cn_term_icon` فقط در صورت وجود `iconly_get_icon_html()` ثبت می‌شود؛ این نصب
افزونهٔ Iconly فعال دارد. نبود آن، صفحهٔ اصلی/مجله/تنظیمات هدر و فوتر را از کار نمی‌اندازد.

## Field Group صفحهٔ نوشته‌ها

| مشخصه | مقدار |
|---|---|
| کلید | `group_home_blog` |
| عنوان | `Blog homepage — Sections` |
| محل نمایش | برگه‌ای که به‌عنوان `posts_page` وردپرس انتخاب شده |
| فیلد اصلی | Flexible Content با نام `home_sections` |
| ویرایشگر کلاسیک | مخفی |

### layoutها

| layout | فیلدها و رفتار |
|---|---|
| `featured` | `category` اختیاری؛ `count` پیش‌فرض ۵، حداقل ۳ و حداکثر ۸ |
| `guide` | `category` الزامی؛ `count` پیش‌فرض ۴، بازهٔ ۲ تا ۱۲؛ `heading` اختیاری با fallback نام دسته؛ `more_link` اختیاری با fallback آرشیو دسته |
| `hero` | `image` الزامی با فرمت ID؛ `link` اختیاری |
| `categories` | `heading` با پیش‌فرض `Categories`؛ انتخاب چندگانهٔ `categories` الزامی؛ `more_link` اختیاری |
| `travelogue` | مانند `guide`: دسته الزامی، تعداد پیش‌فرض ۴ و بازهٔ ۲ تا ۱۲، عنوان و لینک بیشتر اختیاری |
| `news` | یک بخش خبر و یک بخش آخرین نوشته‌ها؛ جزئیات در جدول بعدی |

فیلدهای layout خبر:

| فیلد | مقدار/معنا |
|---|---|
| `news_category` | دستهٔ اختیاری خبرها |
| `news_count` | پیش‌فرض ۴، حداقل ۲، حداکثر ۱۲ |
| `news_heading` | عنوان اختیاری |
| `news_more_link` | لینک بیشتر اختیاری |
| `latest_category` | دستهٔ اختیاری آخرین نوشته‌ها |
| `latest_count` | پیش‌فرض ۷، حداقل ۲، حداکثر ۱۲ |
| `latest_heading` | پیش‌فرض `Latest posts` |

ترتیب ردیف‌های `home_sections` ترتیب خروجی سکشن‌ها را تعیین می‌کند. renderer
مربوط به این schema در فایل‌های template قرار دارد، نه در این فایل.

## Field Group تنظیمات هدر و فوتر

| مشخصه | مقدار |
|---|---|
| کلید | `group_cn_site_header_footer` |
| عنوان | `Site settings — Header and footer` |
| محل نمایش | Options Page با slug برابر `cn-site-settings-header-footer` |
| محل ذخیره | optionهای استاندارد ACF |

### Tab هدر

| نام فیلد | نوع | پیش‌فرض یا fallback |
|---|---|---|
| `cn_header_logo` | image، خروجی ID | بدون مقدار |
| `cn_header_menu` | select منو | انتخاب اجباری نیست |
| `cn_header_mobile_menu` | select منو | در مصرف‌کننده به منوی دسکتاپ fallback می‌کند |
| `cn_header_phone` | text | `021-43997` |
| `cn_header_phone_link` | text | `+982143997` |

### Tab فوتر

| نام فیلد | نوع | پیش‌فرض یا fallback |
|---|---|---|
| `cn_footer_logo` | image، خروجی ID | لوگوی اصلی در محل مصرف |
| `cn_footer_description` | textarea | خالی |
| `cn_footer_phone` / `cn_footer_phone_link` | text | شماره و لینک تلفن فوتر |
| `cn_footer_email` | email | ایمیل مدیر سایت در محل مصرف |
| `cn_footer_col1_menu` | select | منوی ستون اول |
| `cn_footer_col2_menu` | select | منوی ستون دوم |
| `cn_footer_copyright` | text | متن کپی‌رایت |

choiceهای ثابت داخل schema فقط مقدار اولیهٔ تعریف فیلدند. تابع
`cn_site_settings_load_menu_choices()` در `site-settings.php` هنگام بارگذاری
فیلد، آن‌ها را با منوهای واقعی و سازگار با زبان جاری جایگزین می‌کند.

## Field Group صفحهٔ اصلی فروشگاه

| مشخصه | مقدار |
|---|---|
| کلید | `group_voiceweb_front_page` |
| عنوان | `Storefront homepage`، ترجمهٔ فارسی «صفحهٔ اصلی فروشگاه» |
| محل نمایش | برگهٔ ثابت `front_page` در تنظیمات خواندن |
| تابع ثبت | `voiceweb_register_front_page_fields()`، hook استاندارد ACF در اولویت ۵ |
| key/name اصلی | `field_vw_front_*` / `vw_front_*` |
| محل ذخیره | post meta همان برگه؛ مستقل از options هدر/فوتر و برگهٔ مجله |
| REST | `show_in_rest=0` |

تابع‌های کوتاه `$field`، `$sub`، `$tab` و `$toggle` فقط آرایهٔ ACF می‌سازند؛ framework یا
loader دیگری ندارند. `$sub` با prefix کلید یکتا تولید می‌کند و نام کوتاه ذخیرهٔ زیر‌فیلد
را حفظ می‌کند. تب‌ها «اسلایدر اصلی»، «پیشنهادهای ویژه»، «دسته‌بندی محصولات»، «داستان برند
و دربارهٔ ما» و «بخش‌های تکمیلی» هستند.

| فیلد، با پیشوند `vw_front_` | نوع/حد و کاربرد |
|---|---|
| `header_note` | متن کوتاه اعلان هدر |
| `slides` | repeater حداکثر ۶ ردیف؛ `eyebrow/title/description`، تصاویر `image/mobile_image`، لینک‌های `primary/secondary`، `tone` و `floating_images` حداکثر ۳ تصویر |
| `autoplay` / `interval` | true/false پیش‌فرض خاموش؛ عدد ۵–۲۰ ثانیه با پیش‌فرض ۸ |
| `offers_enabled/title/description` | نمایش، عنوان و توضیح پیشنهاد ویژه |
| `offers_products` | relationship، محصول `publish`، ID، انتخاب حداکثر ۱۲؛ ترتیب با drag |
| `offers_deadline` | date/time، خروجی `Y-m-d H:i:s` و نمایش `Y/m/d H:i`؛ تنظیم پایان تخفیف بومی موجود |
| `categories_enabled/title` | نمایش و عنوان دسته‌ها |
| `categories` | repeater حداکثر ۱۲؛ `term` الزامی از `product_cat`، ID و بدون ساخت/save/load term؛ `label/description/image` اختیاری |
| `story_*` / `about_*` / `collection_*` | عنوان، توضیح، تصویر و لینک برای داستان، دربارهٔ ما و کالکشن جدید |
| `promos` | repeater حداکثر ۲ کالکشن منتخب؛ `title/description/image/link/tone` |
| `arrivals_enabled/title/products` | نمایش/عنوان تازه‌ها و relationship محصول، حداکثر ۱۲ ID |
| `blog_enabled/title/description/posts` | نمایش/متن مجله و حداکثر ۳ ID نوشتهٔ منتشرشده |
| `blog_link` | لینک اختیاری آرشیو مجله، array کامل؛ خالی به برگهٔ نوشته‌های وردپرس fallback می‌کند؛ محتوا می‌تواند آرشیو دستهٔ مجلهٔ چرم را انتخاب کند |
| `benefits` | repeater حداکثر ۴؛ `title/description/icon`، آیکون‌های `check/truck/shield/user` |
| `faq_title` / `faq` | عنوان و حداکثر ۶ ردیف `question/answer` |

تصویرهای این گروه ID و لینک‌ها array کامل هستند. `mobile_image` خالی، تصویر دسکتاپ
را مصرف می‌کند. choices ترکیب رنگ `ice/ink/rose` با labelهای «کرم روشن/سبز جنگلی/بژ ملایم»
نمایش داده می‌شوند؛ مقادیر داخلی ثابت‌اند. toggleهای نمایش پیش‌فرض روشن و autoplay خاموش است.
label/instruction/choiceهای انگلیسی از `voiceweb-acf` به کاتالوگ مدیریت والد ترجمه می‌شوند.
نام و key فیلد یا قیمت/موجودی Woo با ترجمه تغییر نمی‌کند.

renderer در `front-page.php`، `inc/front-page.php` و `template-parts/front/` است.
قیمت و سفارش از محصول بومی Woo می‌آیند؛ ACF فقط محتوا/انتخاب سکشن را کنترل می‌کند.
ذخیرهٔ deadline آینده، قیمت تخفیف جدید نمی‌سازد؛ پایان قیمت تخفیف موجود محصولات انتخاب‌شدهٔ
مجاز را تنظیم می‌کند. برنامهٔ تخفیف و cache قیمت در [راهنمای صفحهٔ اصلی](../HOMEPAGE.fa.md)
توضیح داده شده است.

## جریان اجرا

1. وردپرس `functions.php` را اجرا و این فایل را require می‌کند.
2. ACF در hook `acf/include_fields` تابع ثبت را فراخوانی می‌کند.
3. در نبود ACF هیچ group ثبت نمی‌شود و فرانت‌اند باید fallback داشته باشد.
4. رشته‌های انگلیسی رابط از مسیر ترجمهٔ تعریف‌شده در `acf-field-groups.php`
   عبور می‌کنند.
5. مقادیر هدر و فوتر با helperهای `site-settings.php` خوانده می‌شوند.

## وابستگی‌ها و نکات نگهداری

- وابستگی الزامی برای نمایش پنل مدیریت: ACF PRO یا نسخه‌ای با APIهای Options
  Page، Flexible Content و Repeater؛ WooCommerce برای انتخاب دسته/محصول فروشگاه.
- کلید group، key فیلد و name فیلد نقش شناسهٔ پایدار دارند؛ تغییر آن‌ها بدون
  migration می‌تواند داده‌های ذخیره‌شده را قطع کند.
- label و instruction را می‌توان ترجمه کرد، اما name/key نباید ترجمه شوند.
- هنگام افزودن layout، template متناظر و assetهای آن نیز باید ایجاد و مستند شود.
- پوشهٔ قدیمی `Doc/acf-import` حذف شده است؛ گروه‌های اصلی از PHP والد ثبت می‌شوند.

## عیب‌یابی

- نبودن صفحهٔ تنظیمات: فعال‌بودن ACF و اجرای `site-settings.php` را بررسی کنید.
- خالی‌بودن انتخاب منو: زبان منو در WPML و فیلترهای `acf/load_field` را بررسی کنید.
- دیده‌نشدن فیلدها: تنظیم صحیح Reading Settings و انتخاب برگهٔ ثابت `front_page` برای
  فروشگاه یا `posts_page` برای مجله را کنترل کنید؛ این دو location مستقل هستند.
- بازگشت schema قدیمی: object cache را پاک و وجود group هم‌کلید در JSON/دیتابیس
  را بررسی کنید؛ تعریف PHP با اولویت ۵ باید منبع مؤثر باشد.

## شرح کامل کد به‌ترتیب فایل

### خطوط ۱ تا ۱۸: منبع حقیقت، guard و بررسی ACF

DocBlock اعلام می‌کند arrayهای این فایل schema زنده‌اند و تغییرشان در request
بعدی اعمال می‌شود. guard `ABSPATH` اجرای مستقیم را قطع می‌کند. تابع ثبت در
اولین خط `acf_add_local_field_group()` را با `function_exists()` بررسی می‌کند؛
پس غیرفعال‌بودن ACF fatal ایجاد نمی‌کند و کل تابع بدون اثر تمام می‌شود.

### قرارداد propertyهای تکراری ACF

برای خواندن ساختارهای آرایه‌ای باید propertyهای مشترک را یک‌بار شناخت:

| property | معنی در این فایل |
|---|---|
| `key` | شناسهٔ پایدار داخلی ACF؛ اتصال داده و field را حفظ می‌کند |
| `name` | کلید واقعی ذخیره/خواندن مقدار؛ در Tab خالی است چون مقدار ندارد |
| `label` | متن رابط مدیریت و قابل ترجمه |
| `aria-label` | همه‌جا خالی؛ ACF از label اصلی استفاده می‌کند |
| `required` | صفر/false اختیاری و یک الزامی |
| `conditional_logic` | همه false/0؛ هیچ field به field دیگری وابسته نیست |
| `wrapper` | عرض/کلاس اختیاری فیلد؛ برخی فیلدهای بلاگ wrapper درصدی و CSS مدیریت دارند |
| `instructions` | راهنمای editor؛ رشتهٔ خالی یعنی راهنمای اضافی ندارد |
| `min/max` خالی روی layout | تعداد تکرار آن layout محدود نشده |
| `prepend/append/placeholder/maxlength` خالی | رفتار پیش‌فرض ACF حفظ شده |

تفاوت `false` و `0` در این arrayها از export/تعریف ACF آمده و هر دو برای
تنظیمات boolean خاموش عمل می‌کنند.

### خطوط ۲۰ تا ۴۲: group صفحهٔ بلاگ و Flexible Content

اولین call یک group با key `group_home_blog` می‌سازد. تنها field آن
`field_home_sections` از type `flexible_content` و name
`home_sections` است. editor می‌تواند layoutها را اضافه، حذف و drag کند؛
`button_label` متن دکمه را تعیین می‌کند. min/max خود Flexible Content خالی است،
پس تعداد کل سکشن‌ها محدودیت schema ندارد. `layouts` شش تعریف زیر را نگه می‌دارد.

### خطوط ۴۴ تا ۹۹: layout `featured`

layout با نمایش block دو subfield دارد:

- `field_feat_category`: taxonomy category، select تک‌انتخابی، خروجی ID،
  null مجاز و اختیاری. ساخت term، save/load خودکار term و bidirectional خاموش‌اند.
  خالی‌بودن عمداً معنی «جدیدترین نوشته‌های همهٔ دسته‌ها» دارد.
- `field_feat_count`: number اختیاری با default پنج و اعتبار بازهٔ سه تا هشت.
  step خالی یعنی step پیش‌فرض مرورگر/ACF.

layout min/max خالی است؛ editor می‌تواند این نوع را بیش از یک بار اضافه کند.

### خطوط ۱۰۱ تا ۱۹۲: layout `guide`

- category از taxonomy دسته، select تکی، خروجی ID، الزامی و null ممنوع است؛
  add/save/load term غیرفعال‌اند.
- count number اختیاری، default چهار و بازهٔ دو تا دوازده است.
- heading text اختیاری و بدون محدودیت طول است؛ instruction خالی‌بودن را fallback
  به نام دسته تعریف می‌کند.
- more_link از type link و خروجی کامل array است؛ خالی به archive دسته fallback می‌کند.

تمام fieldها wrapper و conditional logic خالی دارند و layout block است.

### خطوط ۱۹۴ تا ۲۴۳: layout `hero`

image الزامی، خروجی attachment ID، preview بزرگ و library همهٔ رسانه‌هاست.
تمام min/max مربوط به عرض، ارتفاع و حجم صفر و mime types خالی‌اند؛ پس schema
محدودیت فایل اضافی تحمیل نمی‌کند. link اختیاری و خروجی ACF Link array است. خود
layout تعداد تکرار محدود ندارد.

### خطوط ۲۴۵ تا ۳۱۴: layout `categories`

heading text اختیاری با default انگلیسی `Categories` است. field
`categories` taxonomy چندانتخابی، الزامی، null ممنوع و خروجی آرایهٔ IDهاست؛
`multiple=1` و `field_type=multi_select` هر دو حالت UI/داده را صریح می‌کنند.
termها فقط انتخاب می‌شوند و add/save/load/bidirectional فعال نیست. instruction
می‌گوید تصویر هر کارت از جدیدترین نوشتهٔ همان دسته می‌آید. more_link اختیاری و
array است.

### خطوط ۳۱۶ تا ۴۰۷: layout `travelogue`

ساختار عمداً با guide هم‌شکل است ولی keyهای مستقل `field_trv_*` دارد تا دادهٔ
دو layout تداخل نکند: category الزامی و ID، count پیش‌فرض چهار در بازهٔ ۲–۱۲،
heading اختیاری با fallback نام دسته و more_link array اختیاری با fallback archive.

### خطوط ۴۰۹ تا ۵۶۹: layout `news`

این layout دو query مستقل را پیکربندی می‌کند:

1. بخش خبر:
   - `news_category`: category تکی اختیاری، ID و null مجاز؛
   - `news_count`: default چهار، بازهٔ ۲–۱۲؛
   - `news_heading`: text خالی با fallback نام دسته یا «Tourism news»؛
   - `news_more_link`: Link array اختیاری.
2. بخش آخرین نوشته‌ها:
   - `latest_category`: category تکی اختیاری و خالی به همه دسته‌ها؛
   - `latest_count`: default هفت، بازهٔ ۲–۱۲؛
   - `latest_heading`: text اختیاری با default `Latest posts`.

taxonomy fieldها add/save/load term و bidirectional ندارند. layout block و
تعداد استفاده‌اش نامحدود است.

### خطوط ۵۷۱ تا ۵۹۱: location و تنظیمات نهایی group بلاگ

location سه‌لایهٔ ACF شرط `page_type == posts_page` را می‌سازد. menu order صفر،
position normal، style default و label بالا هستند. instruction زیر label می‌آید.
فقط `the_content` مخفی می‌شود تا editor کلاسیک با Flexible Content رقابت نکند.
group فعال، REST خاموش و description صرفاً توضیح مدیریتی است.

### خطوط ۵۹۳ تا ۶۱۴: شروع group تنظیمات و Tab هدر

call دوم key `group_cn_site_header_footer` دارد. اولین field Tab بدون name است،
چون داده ذخیره نمی‌کند. placement بالا، endpoint خاموش و به‌صورت پیش‌فرض selected
نیست؛ فقط fieldهای بعدی را در UI گروه‌بندی می‌کند.

### خطوط ۶۱۶ تا ۶۳۹: لوگوی اصلی

image اختیاری، خروجی ID، library کامل و preview متوسط است. صفر بودن تمام
محدودیت‌های ابعاد/حجم و mime خالی یعنی هر تصویر library مجاز است. مصرف‌کننده آن
را هم در desktop و هم mobile نمایش می‌دهد.

### خطوط ۶۴۱ تا ۶۹۲: منوهای هدر

هر دو field select تک‌انتخابی، nullپذیر، UI پیشرفته روشن، AJAX خاموش و خروجی value
هستند. choices شامل IDهای نمونهٔ ۲ و ۶۸ است، اما filter runtime در
`site-settings.php` choices را بازسازی می‌کند.

- desktop key تاریخی `field_6a897869fde06` را حفظ کرده و
  `allow_in_bindings=1` دارد.
- mobile key خوانا دارد و اختیاری است؛ instruction fallback به desktop را تعریف می‌کند.
- default هر دو false و placeholder فارسی است.

### تلفن هدر

دو field text تلفن نمایش و لینک جدا هستند. اولی default/placeholder
`021-43997` و دومی `+982143997` است. maxlength، prepend و append محدودیتی
نمی‌گذارند؛ normalize لینک هنگام مصرف در `site-settings.php` انجام می‌شود.

### خطوط ۷۵۴ تا ۷۷۰: Tab فوتر

مانند Tab هدر name خالی و بدون ذخیره‌سازی است. placement بالا، endpoint و selected
خاموش‌اند و تمام fieldهای بعدی را در تب دوم نشان می‌دهد.

### خطوط ۷۷۲ تا ۸۱۵: لوگو و توضیح فوتر

لوگو دقیقاً image اختیاری با ID، library کامل و preview متوسط است؛ instruction
fallback به لوگوی اصلی را به renderer واگذار می‌کند. textarea توضیح چهار ردیف،
default خالی، بدون maxlength/placeholder و `new_lines` خالی است؛ ACF تبدیل
خودکار newline به `br/p` انجام نمی‌دهد.

### خطوط ۸۱۷ تا ۸۷۴: تلفن و ایمیل فوتر

دو text تلفن همان default/placeholder هدر را دارند و مقدار نمایشی از tel-link
جداست. email از type اختصاصی email، اختیاری و خالی است؛ instruction fallback
ایمیل مدیریت وردپرس را تعریف می‌کند، ولی خود fallback در renderer/helper انجام
می‌شود، نه این array.

### خطوط ۸۷۶ تا ۹۲۶: دو select منوی فوتر

هر دو تک‌انتخابی، nullپذیر، UI روشن، AJAX خاموش، value-return و default false
هستند. choices نمونه و placeholder مانند هدرند و هنگام load داینامیک جایگزین
می‌شوند. key/name مستقل باعث ذخیرهٔ جدا برای ستون اول و دوم می‌شود. instruction
هر field resolve خودکار ترجمهٔ WPML را به لایهٔ مصرف‌کننده نسبت می‌دهد.

### خطوط ۹۲۸ تا ۹۴۶: متن copyright

text اختیاری، default خالی و بدون محدودیت طول است. instruction مشخص می‌کند
renderer سال و نام سایت را جداگانه قبل از این جمله می‌آورد؛ field فقط suffix
قابل‌ویرایش را ذخیره می‌کند.

### خطوط ۹۴۸ تا ۹۶۷: location و تنظیمات نهایی Options Group

location شرط `options_page == cn-site-settings-header-footer` است و باید با slug
ثبت‌شده در `site-settings.php` دقیقاً برابر بماند. سایر تنظیمات UI مانند group
قبلی normal/default/top/label هستند، چیزی مخفی نمی‌شود، group فعال و REST خاموش
است. description می‌گوید داده در options record استاندارد و sitewide ذخیره می‌شود.

### ثبت گروه آیکون و صفحهٔ فروشگاه، سپس hookهای ترجمه

پس از گروه تنظیمات، گروه آیکون با guard API افزونهٔ Iconly ثبت می‌شود.
`voiceweb_register_front_page_fields()` گروه چهارم را با آرایه‌های معمولی می‌سازد.
هر دو action ثبت `acf/include_fields` با priority پنج قبل از loader پیش‌فرض Local JSON
اجرا می‌شوند؛ JSON هم‌کلید نمی‌تواند schema مالک قالب را shadow کند.
فیلتر `cn_acf_textdomain()` صفحهٔ ویرایش ساختار Field Group/Tools را از ترجمهٔ زنده جدا
می‌کند تا ذخیرهٔ گروه، label ترجمه‌شده را به منبع انگلیسی تبدیل نکند. رابط ورود داده از
کاتالوگ مدیریت ترجمه می‌شود و `cn_translate_acf_description()` توضیح گروه را نیز پوشش می‌دهد.

## اسناد مرتبط

- [مرجع تنظیمات سایت](./site-settings.fa.md)
- [راهنمای Code-first ACF](../ACF-CODE-FIRST.fa.md)
- [سیستم ترجمهٔ ACF](../ACF-TRANSLATION.fa.md)
- [صفحهٔ اصلی فروشگاه](../HOMEPAGE.fa.md)
