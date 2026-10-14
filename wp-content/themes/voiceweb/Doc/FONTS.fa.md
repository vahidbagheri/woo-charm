# سیستم فونت چندزبانهٔ VoiceWeb

> راهنمای اصلی پروژه: [README.fa.md](./README.fa.md)
>
> آموزش عملی از صفر، با توضیح کد و آزمون مرورگر:
> [FONT-ADDING-STEP-BY-STEP.fa.md](./FONT-ADDING-STEP-BY-STEP.fa.md)
>
> راهنمای جایگزینی با Yekan Bakh:
> [YEKAN-BAKH-VARIABLE.fa.md](./YEKAN-BAKH-VARIABLE.fa.md)

تنظیم فونت‌ها در این فایل متمرکز است:

```text
voiceweb/config/fonts.php
```

منطق resolve، ساخت `@font-face` و preload در `inc/fonts.php` قرار دارد. برای
بارگذاری فونت جدید نباید enqueue دستی به `functions.php` اضافه شود.

## وضعیت فعلی

| زبان | خانواده | منبع |
|---|---|---|
| فارسی | `Dana`، وزن‌های واقعی `400`، `500` و `700` | سه فایل موجود `DanaFaNum-*.woff2` در `assets/fonts/dana/` |
| انگلیسی | `Arial, Helvetica, sans-serif` | فونت سیستم |
| سایر زبان‌ها | stack پیش‌فرض سیستم | فونت سیستم |

تنظیم واقعی فارسی:

```php
'fa' => array(
	'family'      => '"Dana", Tahoma, sans-serif',
	'stylesheets' => array(),
	'font_faces'  => array(
		array(
			'family'  => 'Dana',
			'path'    => 'assets/fonts/dana/DanaFaNum-Regular.woff2',
			'format'  => 'woff2',
			'weight'  => '400',
			'style'   => 'normal',
			'display' => 'swap',
			'preload' => true,
		),
		array(
			'family'  => 'Dana',
			'path'    => 'assets/fonts/dana/DanaFaNum-Medium.woff2',
			'format'  => 'woff2',
			'weight'  => '500',
			'style'   => 'normal',
			'display' => 'swap',
			'preload' => false,
		),
		array(
			'family'  => 'Dana',
			'path'    => 'assets/fonts/dana/DanaFaNum-Bold.woff2',
			'format'  => 'woff2',
			'weight'  => '700',
			'style'   => 'normal',
			'display' => 'swap',
			'preload' => false,
		),
	),
	'preload'     => array(),
),
```

سه فایل WOFF2 موجود بدون تبدیل یا دانلود فونت جدید استفاده می‌شوند. فقط
Regular با `as="font" type="font/woff2" crossorigin` preload می‌شود؛ Medium
و Bold هنگام استفادهٔ صفحه از وزن مربوط دانلود می‌شوند. همهٔ faceها
`font-display: swap` دارند. fallback قبلی `Tahoma, sans-serif` حفظ شده است.

`assets/fonts/DanaVF.ttf` و `assets/css/fonts.css` همچنان روی دیسک وجود دارند،
اما تنظیم فعال به آن‌ها ارجاع نمی‌دهد. فایل CSS قدیمی که تمام وزن‌ها را تعریف
می‌کرد enqueue نمی‌شود. وزن متغیر اعلام نمی‌شود؛ درخواست‌های وزن دیگر CSS
به نزدیک‌ترین face ثبت‌شده نگاشت می‌شوند. برای مثال عنوان‌های `800`/`900`
در این تنظیم فایل ExtraBold/Black جداگانه دانلود نمی‌کنند.

حجم فایل‌های واقعی: Regular برابر ۴۱٬۱۷۲، Medium برابر ۴۰٬۹۸۴ و Bold برابر
۴۱٬۸۶۰ بایت است؛ جمع سه فایل ۱۲۴٬۰۱۶ بایت، در برابر فایل TTF غیرفعال قبلی
با حجم ۱۹۱٬۹۴۸ بایت. این اعداد حجم روی دیسک‌اند؛ مرورگر فقط وزن‌های مورد
استفاده را دریافت می‌کند. کنترل فونت همان لودر موجود `inc/fonts.php` است و
به‌خاطر این تغییر، Sass یا استایل inline دیگری اضافه نشده است.

## کلیدهای تنظیم

| کلید | کاربرد |
|---|---|
| `family` | مقدار `font-family` زبان جاری |
| `stylesheets` | stylesheetهای دارای تعریف `@font-face` |
| `font_faces` | تعریف مستقیم فونت ثابت یا متغیر |
| `preload` | فایل‌های اضافه‌ای که باید preload شوند |

گزینه‌های هر عضو `font_faces`:

| کلید | کاربرد |
|---|---|
| `family` | نام داخلی خانواده |
| `path` | مسیر نسبت به ریشهٔ قالب |
| `format` | `woff2`، `woff`، `ttf` یا `otf` |
| `weight` | یک وزن یا بازه‌ای مانند `100 900` |
| `style` | `normal`، `italic` یا `oblique` |
| `display` | معمولاً `swap` |
| `stretch` | مقدار یا بازهٔ اختیاری کشیدگی |
| `preload` | preload خودکار همان فایل |

## تشخیص زبان

ترتیب تشخیص:

1. زبان جاری `WPML`؛
2. locale جاری وردپرس؛
3. تنظیم `default`.

ورودی دقیق مانند `fa_IR` بر ورودی عمومی `fa` اولویت دارد. مسیرهای stylesheet،
preload و font face ابتدا در چایلد و سپس در والد resolve می‌شوند.

## استفاده در CSS و Sass

اجزای رابط باید فونت را از `body` به ارث ببرند. اگر تعریف صریح لازم بود از
متغیر زیر استفاده کن:

```scss
font-family: var(--cn-font-family);
```

نام خانوادهٔ فعال را در چند فایل `SCSS` تکرار نکن. این کار تغییر زبان و تغییر
فونت پروژه را دشوار می‌کند.

## تغییر فونت از چایلد

از filter زیر در `voiceweb-child/functions.php` استفاده کن:

```php
add_filter(
	'cn/font-config',
	function ( $fonts ) {
		$fonts['fa'] = array(
			'family'      => '"My Persian Font", Tahoma, sans-serif',
			'stylesheets' => array(),
			'font_faces'  => array(
				array(
					'family'  => 'My Persian Font',
					'path'    => 'assets/fonts/my-persian-font.woff2',
					'format'  => 'woff2',
					'weight'  => '100 900',
					'style'   => 'normal',
					'display' => 'swap',
					'preload' => true,
				),
			),
			'preload'     => array(),
		);

		return $fonts;
	}
);
```

فایل نمونه باید در این مسیر باشد:

```text
voiceweb-child/assets/fonts/my-persian-font.woff2
```

اگر فقط stack انگلیسی تغییر می‌کند، همان مقدار را ویرایش کن و تنظیم فارسی را
دوباره تعریف نکن:

```php
add_filter(
	'cn/font-config',
	function ( $fonts ) {
		$fonts['en']['family'] = '"Trebuchet MS", Arial, sans-serif';
		return $fonts;
	}
);
```

## افزودن زبان جدید

```php
add_filter(
	'cn/font-config',
	function ( $fonts ) {
		$fonts['ar'] = array(
			'family'      => '"Arabic UI", sans-serif',
			'stylesheets' => array( 'assets/css/fonts/ar.css' ),
			'font_faces'  => array(),
			'preload'     => array( 'assets/fonts/arabic/regular.woff2' ),
		);

		return $fonts;
	}
);
```

فایل `assets/css/fonts/ar.css` فقط باید `@font-face` داشته باشد و نباید `body`
را دوباره تعریف کند. زیرپوشه باعث می‌شود این CSS توسط کشف خودکار کامپوننت‌های
سراسری بارگذاری نشود. مسیر `url()` داخل آن نسبت به خود CSS است؛ برای فایل
نمونهٔ بالا از `../../fonts/arabic/regular.woff2` استفاده کن و فایل فونت را
در همان قالبی قرار بده که CSS در آن است.

## آزمون

1. صفحهٔ فارسی را با Hard Refresh باز کن.
2. در Computed Style مقدار `font-family` روی `body` را بررسی کن.
3. در HTML، style با شناسهٔ `cn-fonts-inline-css` را پیدا کن.
4. در Network فایل‌های `DanaFaNum-Regular.woff2`، `DanaFaNum-Medium.woff2` و
   `DanaFaNum-Bold.woff2` را برای وزن‌های مصرف‌شده بررسی کن؛ `DanaVF.ttf`
   و stylesheet قدیمی `fonts.css` نباید درخواست شوند.
5. وزن‌های `400`، `500` و `700` را بررسی کن؛ در head فقط Regular preload شود.
6. صفحهٔ انگلیسی را باز کن و مطمئن شو فایل فارسی دانلود نمی‌شود.
7. خطاهای `404`، MIME و CORS را در Console بررسی کن.

بازبینی واقعی در ۱۴ اکتبر ۲۰۲۶ روی صفحهٔ فارسی نصب محلی انجام شد. خانوادهٔ
computed روی `body` برابر `Dana, Tahoma, sans-serif` بود؛ faceهای `400`،
`500` و `700` پس از درخواست صریح `document.fonts.load()` همگی با وضعیت
`loaded` و `display=swap` ثبت شدند. سه WOFF2 پاسخ ۲۰۰ داشتند، تنها Regular
با نوع `font/woff2` و `crossorigin` preload شده بود و هیچ فایل TTF درخواست
نشد. خطای JavaScript و overflow افقی صفحهٔ بررسی‌شده صفر بود. این بررسی
خواندنی بود و هیچ fixture، فرم، سفارش یا پیامکی ایجاد نکرد.

## خطاهای رایج

- نام `family` با نام تعریف‌شده در `font_faces` یکسان نیست.
- مسیر فایل نسبت به ریشهٔ قالب اشتباه نوشته شده است.
- font face جدید اضافه شده، اما stylesheet یا preload قدیمی پاک نشده است.
- فایل TTF به‌عنوان `woff2` معرفی شده است.
- filter چایلد مقدار یک زبان را ناقص و بدون کلیدهای لازم جایگزین کرده است.
- یک قانون CSS با specificity بیشتر، `--cn-font-family` را بی‌اثر کرده است.
