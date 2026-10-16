# رفع خطای Filename too long در Git ویندوز

[فهرست مستندات](./README.fa.md) · [ابزارهای پروژه](./TOOLS.fa.md)

اگر VS Code هنگام افزودن فایل‌های WPML خطای `Filename too long` نشان می‌دهد،
ابتدا پشتیبانی مسیر بلند را فقط برای مخزن همین پروژه فعال کنید. مسیرهای عمیق افزونه
را برای رفع این خطا حذف یا به `.gitignore` اضافه نکنید.

در PowerShell از ریشهٔ همین پروژه اجرا کنید:

```powershell
Set-Location -LiteralPath 'C:\Users\baghe\Local Sites\woo-charm-main\app\public'
git rev-parse --show-toplevel
git config --local core.longpaths true
git config --local --show-origin --get core.longpaths
git config --local --get core.longpaths
```

فرمان اول Git باید ریشهٔ `C:/Users/baghe/Local Sites/woo-charm-main/app/public`
را نشان دهد. خروجی دارای مبدأ باید تنظیم `core.longpaths` را از `.git/config` همین
مخزن با مقدار `true` نشان دهد؛ خروجی فرمان آخر باید فقط `true` باشد. گزینهٔ `--local`
باعث می‌شود تنظیم دیگر پروژه‌ها و پیکربندی سراسری کاربر تغییر نکند.

برای بررسی مسیرهای افزونه بدون افزودن فایل به staging:

```powershell
git add --dry-run -- wp-content/plugins/sitepress-multilingual-cms
```

این فرمان فقط فایل‌هایی را که Git می‌تواند اضافه کند گزارش می‌کند؛ commit نمی‌سازد
و فایل‌های WPML را تغییر نمی‌دهد. اگر فایل تغییرکرده‌ای در این مسیر نباشد، نبود خروجی
می‌تواند طبیعی باشد.

اگر مقدار `true` است ولی VS Code هنوز خطای قبلی را نشان می‌دهد، از Command Palette
فرمان `Developer: Reload Window` را اجرا و پنل Source Control را تازه کنید؛ سپس
عملیات موردنظر را دوباره بررسی کنید. در صورت تداوم خطا، خروجی تازهٔ Git در VS Code
و مسیر فایل دقیقِ ناموفق را با همین بررسی خط فرمان مقایسه کنید. در این مرحله تغییر
Registry ویندوز یا اجرای برنامه با دسترسی Administrator لازم نیست.
