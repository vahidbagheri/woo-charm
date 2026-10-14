# استفاده از Yekan Bakh Variable برای فارسی

> راهنمای اصلی فونت‌ها: [FONTS.fa.md](./FONTS.fa.md)

قالب فایل زیر را در والد دارد:

```text
voiceweb/assets/fonts/YekanBakh-VF.ttf
```

مشخصات فایل:

| ویژگی | مقدار |
|---|---|
| خانواده | `Yekan Bakh VF` |
| نوع | Variable TrueType |
| محور | `wght` |
| بازهٔ وزن | `1 1000` |

تنظیم فعال فعلی فارسی `Dana` با سه فایل موجود WOFF2 Regular/Medium/Bold
و وزن‌های `400`/`500`/`700` است؛ `swap` برای همه و preload فقط برای Regular.
این سند یک جایگزینی اختیاری با فایل TTF متغیر موجود Yekan Bakh را آموزش
می‌دهد و پیش‌فرض زندهٔ فروشگاه را تغییر نمی‌دهد. برای اجرای مثال، همهٔ اجزای
تنظیم فارسی باید هماهنگ تغییر کنند؛ [تنظیم پیش‌فرض](./FONTS.fa.md) را ببین.

## روش پیشنهادی در چایلد

کد زیر را در `voiceweb-child/functions.php` قرار بده:

```php
/**
 * Use Yekan Bakh Variable on Persian front-end pages.
 */
add_filter(
	'cn/font-config',
	function ( $fonts ) {
		$fonts['fa'] = array(
			'family'      => '"Yekan Bakh VF", Tahoma, sans-serif',
			'stylesheets' => array(),
			'font_faces'  => array(
				array(
					'family'  => 'Yekan Bakh VF',
					'path'    => 'assets/fonts/YekanBakh-VF.ttf',
					'format'  => 'ttf',
					'weight'  => '1 1000',
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

لودر والد به‌صورت خودکار:

1. زبان جاری را تشخیص می‌دهد؛
2. filter چایلد را اعمال می‌کند؛
3. `@font-face` را تولید می‌کند؛
4. مقدار `--cn-font-family` را تنظیم می‌کند؛
5. فایل را فقط برای زبان فارسی preload می‌کند.

نیازی به enqueue دستی یا تعریف دوبارهٔ `@font-face` در `CSS` نیست.

## نگهداری فایل در چایلد

کد بالا فایل والد را پیدا می‌کند. اگر می‌خواهی فایل فونت نیز متعلق به پروژهٔ
چایلد باشد، همان مسیر نسبی را در چایلد بساز:

```text
voiceweb-child/assets/fonts/YekanBakh-VF.ttf
```

مقدار `path` تغییر نمی‌کند؛ resolver ابتدا چایلد را بررسی می‌کند.

## تغییر مستقیم والد

اگر والد محصول نهایی است و قرار نیست با نسخهٔ دیگری جایگزین شود، می‌توان entry
زبان `fa` را مستقیماً در `config/fonts.php` با آرایهٔ بالا جایگزین کرد. برای
پروژهٔ دارای قالب چایلد، روش filter امن‌تر است.

## فایل‌هایی که نباید تغییر کنند

برای این جابه‌جایی نیازی به تغییر فایل‌های زیر نیست:

```text
voiceweb/inc/fonts.php
voiceweb/inc/assets.php
voiceweb/assets/scss/global.scss
voiceweb-child/assets/scss/global.scss
```

همچنین فایل فونت را با `wp_enqueue_style()` یا `wp_enqueue_script()` بارگذاری
نکن.

## آزمون

1. cache مرورگر و هر cache صفحه را پاک کن.
2. یک صفحهٔ فارسی را با Hard Refresh باز کن.
3. روی `body` مقدار `font-family` را بررسی کن؛ مقدار نخست باید
   `Yekan Bakh VF` باشد.
4. style با شناسهٔ `cn-fonts-inline-css` را در HTML بررسی کن.
5. در Network فقط فایل زیر باید برای فونت فارسی جدید لود شود:

   ```text
   assets/fonts/YekanBakh-VF.ttf
   ```

6. در حالت جایگزینی این مثال، فایل‌های `DanaFaNum-*.woff2` فارسی نباید
   از تنظیم قدیمی دانلود شوند. `DanaVF.ttf` از قبل در تنظیم پیش‌فرض غیرفعال است.
7. وزن‌های `400`، `600`، `700` و `900` را بررسی کن.
8. صفحهٔ انگلیسی را باز کن؛ Yekan Bakh نباید در آن دانلود شود.

## بازگشت به Dana WOFF2

filter بالا را از `voiceweb-child/functions.php` حذف کن. تنظیم والد دوباره
خانوادهٔ `Dana, Tahoma, sans-serif` و این سه face را فعال می‌کند:

```text
assets/fonts/dana/DanaFaNum-Regular.woff2  → 400، preload
assets/fonts/dana/DanaFaNum-Medium.woff2   → 500
assets/fonts/dana/DanaFaNum-Bold.woff2     → 700
```

همه `font-display: swap` دارند. اگر برای تمرین خود config والد را تغییر داده‌ای،
حذف filter کافی نیست؛ entry سه‌وزنی `fa` در [FONTS.fa.md](./FONTS.fa.md)
را به والد برگردان. فایل TTF قبلی Dana با حذف این filter فعال نمی‌شود.

## نسخهٔ WOFF2

برای production می‌توان فایل متغیر را به `WOFF2` تبدیل کرد. فقط در صورتی بازهٔ
وزن `1 1000` را حفظ کن که فایل خروجی همچنان variable و دارای همان محور باشد:

```php
'path'   => 'assets/fonts/YekanBakh-VF.woff2',
'format' => 'woff2',
'weight' => '1 1000',
```

نام فایل واقعی باید دقیقاً با مقدار `path` یکسان باشد.
