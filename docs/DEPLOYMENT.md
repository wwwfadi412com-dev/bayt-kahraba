# من الاستضافة التجريبية إلى الإنتاج

## 1) wp-config.php — إعدادات مستقلة عن البيئة
```php
// staging / local / production
define( 'WP_ENVIRONMENT_TYPE', getenv( 'WP_ENVIRONMENT_TYPE' ) ?: 'staging' );

// الروابط من متغيرات البيئة (لا شيء مثبت في قاعدة البيانات)
if ( getenv( 'WP_HOME' ) ) {
	define( 'WP_HOME', getenv( 'WP_HOME' ) );
	define( 'WP_SITEURL', getenv( 'WP_HOME' ) );
}

// شبكة أمان بعد النقل: أي رابط قديم داخل المحتوى يُستبدل تلقائياً بالدومين الحالي
define( 'BK_LEGACY_URLS', 'https://mystore.free-host.net,http://localhost:8080' );

// منع إرسال إيميلات للزبائن من نسخة التجربة
define( 'BK_BLOCK_EMAILS', true );

// اختياري: إخراج AVIF بدل WebP إن كان الخادم يدعمه
define( 'BK_PREFER_AVIF', false );
```
في أي بيئة غير `production` يضيف القالب تلقائياً `noindex` ويمنع الزحف في robots.txt ويُظهر شارة البيئة في شريط الإدارة.

## 2) النقل
1. صدّر قاعدة البيانات وانسخ `wp-content/uploads` والقالب.
2. على الخادم الجديد:
   ```bash
   wp search-replace 'https://mystore.free-host.net' 'https://mystore.sy' --all-tables --precise --skip-columns=guid
   wp rewrite flush
   wp cache flush
   ```
   (`search-replace` يعالج البيانات المسلسلة بأمان.)
3. اضبط `WP_ENVIRONMENT_TYPE=production` واحذف `BK_BLOCK_EMAILS`.
4. الإعدادات ← القراءة: أزل "منع محركات البحث".
5. أرسل `https://mystore.sy/wp-sitemap.xml` إلى Google Search Console وتحقق من `https://mystore.sy/llms.txt`.

## 3) بعد الإطلاق
- كاش صفحات + Redis Object Cache.
- Cron حقيقي: `define( 'DISABLE_WP_CRON', true );` + `*/5 * * * * wp cron event run --due-now`.
- تحديث الأسعار اليومي من سطر الأوامر عند الحاجة: `wp bk prices --rate=13500 --step=5000`.
