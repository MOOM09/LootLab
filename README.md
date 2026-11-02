# 🎮 LootHub | دليل تشغيل المشروع للتيم

> **اقرأ الملف من البداية للنهاية قبل تشغيل المشروع.** هذا الدليل مكتوب لأعضاء فريق LootHub، وخصوصًا لأي شخص أول مرة يستخدم XAMPP أو Composer أو APIs.

**اسم المستودع:** LootLab — **اسم المتجر في الواجهة:** LootHub  
**الفرع الخاص بالتعديلات:** \`TARIFI\`  
**التقنيات:** PHP 8+ (OOP / MVC) · MySQL/MariaDB · HTML/CSS/JavaScript · Composer · Bootstrap · REST APIs.

## ⚠️ مهم جدًا قبل أي شيء

- **لا ترفعوا ملف \`.env\` أبدًا.** فيه مفاتيح وكلمات مرور سرية. استخدموا \`.env.example\` وانسخوه محليًا إلى \`.env\`.
- لا ترسلوا \`STRIPE_SECRET_KEY\` أو \`GEMINI_API_KEY\` أو \`GOOGLE_CLIENT_SECRET\` أو \`MAIL_PASSWORD\` أو \`TEXTBEE_API_KEY\` في الشات أو على GitHub.
- **الـ README وحده لا يضيف الميزات إلى الكود.** قبل تجربة Email OTP أو SMS أو خريطة الأردن، تأكدوا أن صاحب فرع \`TARIFI\` رفع آخر ملفات المشروع والـ migrations. بعض هذه الملفات كانت موجودة محليًا فقط وقت إعداد هذا الدليل.
- تشغيل المزايا الأساسية (عرض المنتجات/السلة) لا يتطلب تفعيل كل الـ APIs. الخدمات الخارجية تُجهّز حسب الميزة المطلوبة.
- شغّلوا خدمات الدفع بوضع **Test/Sandbox فقط** في العرض التجريبي.

---

## 1) البرامج المطلوبة — نزّلوا هاي أولًا

| البرنامج | ليش بنحتاجه؟ | الرابط |
|---|---|---|
| **Git** | تنزيل المشروع وتحديثه | https://git-scm.com/downloads |
| **XAMPP** | Apache + MySQL/MariaDB + PHP | https://www.apachefriends.org/ |
| **Composer** | تثبيت مكتبات PHP | https://getcomposer.org/download/ |
| **VS Code** | فتح وتعديل الكود | https://code.visualstudio.com/ |
| **متصفح حديث** | تشغيل المشروع واختباره | Chrome / Edge |

بعد التثبيت، افتح **PowerShell** أو Terminal في VS Code ونفّذ:

~~~powershell
php -v
composer --version
git --version
~~~

إذا \`php\` مش معروف في Windows، استخدم \`C:\xampp\php\php.exe\` أو أضف \`C:\xampp\php\` إلى PATH. في إعداد Composer على Windows اختَر ملف PHP الخاص بـ XAMPP.

**تأكد من تفعيل امتدادات PHP** المطلوبة في \`C:\xampp\php\php.ini\`: \`curl\` و\`openssl\` و\`mbstring\` و\`pdo_mysql\` و\`fileinfo\`. بعد تعديل \`php.ini\` أعد تشغيل Apache.

## 2) تنزيل المشروع من GitHub

افتح PowerShell:

~~~powershell
cd C:\xampp\htdocs
git clone -b TARIFI https://github.com/MOOM09/LootLab.git
cd LootLab
composer install
~~~

> **مهم:** لا تحتاج تنزّل كل مكتبة لحالها؛ \`composer install\` يقرأ \`composer.json\` و\`composer.lock\` ويثبت الاعتمادات المحددة. لا ترفع مجلد \`vendor/\` إلى GitHub.

المكتبات المستخدمة:

| المكتبة | أمر التثبيت عند الحاجة | استخدامها |
|---|---|---|
| \`vlucas/phpdotenv\` | \`composer require vlucas/phpdotenv\` | قراءة \`.env\` |
| \`google/apiclient\` | \`composer require google/apiclient\` | Google Login |
| \`phpmailer/phpmailer\` | \`composer require phpmailer/phpmailer\` | Gmail SMTP / Email OTP |
| \`stripe/stripe-php\` | \`composer require stripe/stripe-php\` | Stripe Checkout |

**لا تنفّذ أوامر \`composer require\` الأربعة إذا \`composer install\` نجح**؛ الأوامر موجودة للتوضيح أو لإصلاح مكتبة ناقصة.

## 3) تجهيز قاعدة البيانات

1. افتح XAMPP وشغّل **Apache** و**MySQL**.
2. افتح http://localhost/phpmyadmin .
3. أنشئ قاعدة باسم \`lootlab\` بترميز \`utf8mb4\`.
4. استورد **أحدث ملف SQL كامل** يسلّمه مسؤول قاعدة البيانات. إذا عندك فقط \`database/lootlab.sql\`، فهو مخطط أساسي وقد لا يحتوي إضافات المشروع الأخيرة.
5. إذا لم تكن الإضافات مدمجة في الـ SQL النهائي، شغّل الـ migrations **بالترتيب** ومن تبويب SQL، **مرة واحدة فقط لكل ملف**:

~~~text
database/migrations/2026_11_01_jordan_delivery_pin.sql
database/migrations/2026_11_01_stripe_payment_tracking.sql
database/migrations/2026_11_01_email_verification.sql
database/migrations/2026_11_01_email_otp_upgrade.sql
~~~

**انتبه:** ترتيب ملفات البريد مهم؛ \`email_otp_upgrade.sql\` يعتمد على جدول أنشأه \`email_verification.sql\`. لا تعيد تشغيل migration إذا تم تطبيقه، ولا تستورد مخططًا قديمًا فوق قاعدة فيها بيانات. إذا لم تجد الملفات على GitHub، اطلب آخر نسخة من مسؤول الفرع بدل إنشاء أعمدة عشوائية.

**المنتجات والصور:** يوجد بيانات Seed/استيراد للمنتجات وتصنيفاتها، وبعض المجسمات \`.glb\`. إذا ظهرت الصفحة فارغة أو الصور ناقصة، تأكد أن التيم شارك **آخر SQL للمنتجات** وملفات \`public/images/products\` و\`public/models/collectibles\`، وأن المسارات المخزنة بقاعدة البيانات صحيحة.

## 4) إعداد ملف البيئة .env

من داخل مجلد المشروع:

~~~powershell
Copy-Item .env.example .env
~~~

افتح \`.env\` وعدّل القيم حسب جهازك. **لا تنسخ مفاتيح شخص آخر داخل المستودع**؛ كل عضو يأخذ مفاتيحه أو يطلب وصولًا آمنًا إلى مفاتيح الاختبار من المسؤول.

~~~dotenv
APP_URL=http://localhost/projects/LootLab/public

DB_HOST=localhost
DB_NAME=lootlab
DB_USER=root
DB_PASS=""

# Google Gemini (مساعد التسوق)
GEMINI_API_KEY=YOUR_GEMINI_KEY

# Stripe (مفاتيح TEST فقط)
STRIPE_PUBLISHABLE_KEY=pk_test_REPLACE_ME
STRIPE_SECRET_KEY=sk_test_REPLACE_ME
STRIPE_JOD_TO_USD_RATE=1.41

# Google OAuth Login
GOOGLE_CLIENT_ID=YOUR_GOOGLE_CLIENT_ID
GOOGLE_CLIENT_SECRET=YOUR_GOOGLE_CLIENT_SECRET
GOOGLE_REDIRECT_URI=http://localhost/projects/LootLab/public/auth/google/callback

# Gmail SMTP + Email OTP
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
MAIL_USERNAME=YOUR_GMAIL@gmail.com
MAIL_PASSWORD="YOUR_GMAIL_APP_PASSWORD"
MAIL_FROM_ADDRESS=YOUR_GMAIL@gmail.com
MAIL_FROM_NAME="LootHub"

# TextBee SMS + OTP
TEXTBEE_API_KEY=YOUR_TEXTBEE_KEY
TEXTBEE_DEVICE_ID=YOUR_TEXTBEE_DEVICE_ID

# PhotoMaker: خدمة مستقلة محلية (اختيارية)
PHOTOMAKER_GENERATE_URL=http://127.0.0.1:7861/generate
~~~

**ملاحظات مهمة:**
- لا تكتب نفس المفتاح مرتين داخل \`.env\` (خصوصًا \`TEXTBEE_API_KEY\`).
- إذا Gmail App Password فيها مسافات، ضعها بين علامتي تنصيص أو احذف مسافات العرض؛ بدون تنصيص قد يظهر \`Dotenv InvalidFileException\`.
- لا تغير \`APP_URL\` إلا إذا غيّرت مسار مجلد المشروع أو نشرته.
- \`STRIPE_JOD_TO_USD_RATE=1.41\` **سعر تحويل تقريبي للتجربة** وليس سعر صرف حيًا أو حلًا محاسبيًا للإنتاج.
- إذا نسخت المشروع باسم مجلد آخر، عدّل \`APP_URL\` و\`GOOGLE_REDIRECT_URI\` ليتطابقا معه.

## 5) شغّل الموقع

شغّل Apache وMySQL في XAMPP، ثم افتح:

- **المتجر:** http://localhost/projects/LootLab/public/
- **المنتجات:** http://localhost/projects/LootLab/public/products
- **التسجيل:** http://localhost/projects/LootLab/public/register
- **تسجيل الدخول:** http://localhost/projects/LootLab/public/login
- **السلة:** http://localhost/projects/LootLab/public/cart
- **الدفع:** http://localhost/projects/LootLab/public/checkout
- **تأكيد الإيميل بالرمز:** http://localhost/projects/LootLab/public/verify-email/pending

> \`localhost\` يفتح على **نفس الجهاز فقط**. لا يمكن فتح رابط \`localhost\` الموجود على اللابتوب من هاتف آخر. لكن المستخدم يستطيع قراءة **رمز الإيميل من هاتفه** وكتابته في الموقع على اللابتوب.

---

## 6) شرح الـ APIs والخدمات الخارجية

### A) Gemini API — Loot AI (مساعد التسوق)

**الوظيفة:** مساعد تسوق يجيب عن أسئلة المستخدم ويقترح منتجات فعلية من كتالوج المتجر.

1. افتح https://aistudio.google.com/apikey وأنشئ API Key.
2. ضعه في \`GEMINI_API_KEY\` داخل \`.env\`.
3. افتح صفحة **Loot AI** وجرّب سؤالًا عن منتج موجود.
4. الملفات المهمة: \`app/Services/AiService.php\` و\`app/Controllers/AiController.php\` و\`app/Models/AiConversation.php\`.

**مهم:** اسم موديل Gemini والـ endpoint محددان داخل \`AiService.php\`، وقد يتغير توفر الموديل أو حصصه. إذا ظهر \`404\` أو \`429\`، افحص اسم الموديل والحصة والمفتاح من توثيق Google بدل تغيير API Key عشوائيًا.

التوثيق: https://ai.google.dev/gemini-api/docs

### B) Gmail SMTP + Email OTP — تأكيد الحساب

**الوظيفة:** بعد Register يصل **رمز من 6 أرقام** إلى إيميل المستخدم؛ يكتبه على الموقع. بدون التحقق لا يكتمل تفعيل الحساب.

1. فعّل **2-Step Verification** في حساب Google.
2. افتح https://myaccount.google.com/apppasswords وأنشئ **App Password** باسم \`LootHub\`.
3. اضبط \`MAIL_*\` كما في مثال \`.env\`.
4. على شبكة التطوير المستخدمة، كان **المنفذ 465 + ssl** يعمل، بينما 587 لم يعمل. للتحقق من جهازك:

~~~powershell
Test-NetConnection smtp.gmail.com -Port 465
~~~

5. سجّل بإيميل جديد، افتح Gmail من أي جهاز لقراءة الرمز، وأدخله في صفحة التحقق على اللابتوب.
6. الرمز صالح **10 دقائق**، ويُستخدم مرة واحدة، وبعد **5 محاولات خاطئة** يلزم طلب رمز جديد. توجد قيود على إعادة الإرسال.

**ملفات الميزة:** \`app/Services/MailService.php\`، \`app/Services/EmailVerificationService.php\`، \`app/Controllers/EmailVerificationController.php\`، \`app/Controllers/AuthController.php\`، \`app/Views/auth/verify-email-pending.php\`.

**لا تستخدم كلمة مرور Gmail العادية.** ولا ترفع كلمة مرور التطبيق إلى GitHub.

التوثيق: https://github.com/PHPMailer/PHPMailer · https://support.google.com/accounts/answer/185833

### C) TextBee — SMS OTP قبل الدفع

**الوظيفة:** بعد اختيار عنوان التوصيل ورقم الهاتف، يرسل LootHub رمز SMS للتحقق من الرقم **قبل** فتح Stripe.

1. افتح https://textbee.dev/download ونزّل تطبيق Android.
2. سجّل دخولك في https://textbee.dev/ واربط الهاتف بالـ API Key أو QR.
3. فعّل صلاحية **Send SMS**؛ لازم الهاتف متصل بالإنترنت والشريحة قادرة ترسل SMS.
4. من Dashboard انسخ \`TEXTBEE_API_KEY\` و\`TEXTBEE_DEVICE_ID\` إلى \`.env\`.
5. جرّب SMS من لوحة TextBee أولًا، ثم جرّب Checkout داخل LootHub.
6. **SMS OTP** صالح تقريبًا **5 دقائق**، مع حدود للمحاولات وإعادة الإرسال.

**ملفات الميزة:** \`app/Services/TextBeeService.php\`، \`app/Controllers/CheckoutController.php\`، \`app/Views/checkout/verify.php\`.

**مهم:** TextBee يرسل من شريحة الهاتف، وقد يترتب عليه تكلفة SMS؛ وهو ليس بديلًا عن SMTP.

التوثيق: https://textbee.dev/docs

### D) Stripe — الدفع الإلكتروني التجريبي

**الوظيفة:** بعد نجاح SMS OTP ينتقل المستخدم إلى Stripe Checkout، ثم يتأكد السيرفر من نتيجة الدفع.

1. افتح https://dashboard.stripe.com/test/apikeys واستخدم **Test keys** فقط.
2. ضع \`pk_test_...\` في \`STRIPE_PUBLISHABLE_KEY\` و\`sk_test_...\` في \`STRIPE_SECRET_KEY\`.
3. تأكد أن مكتبة \`stripe/stripe-php\` مثبّتة بواسطة Composer.
4. أضف منتجًا إلى السلة، ثم Checkout → SMS OTP → **Pay with Stripe**.
5. بطاقة الاختبار:

~~~text
Card number: 4242 4242 4242 4242
Expiry: أي تاريخ مستقبلي
CVC: أي 3 أرقام
~~~

**ملفات الميزة:** \`app/Services/StripeService.php\`، \`app/Controllers/CheckoutController.php\`، \`app/Services/CheckoutService.php\`.

**تنبيهات:** لا تستخدم بطاقة بنكية حقيقية في Sandbox. الدفع لا يُعد ناجحًا لمجرد إدخال OTP. الربط الحالي تجريبي؛ **Stripe Webhook** ضروري قبل التشغيل التجاري الحقيقي لتأكيد الدفع حتى لو المستخدم أغلق المتصفح. التحويل من JOD إلى USD في المشروع تقريبي للتجربة.

التوثيق: https://docs.stripe.com/testing · https://docs.stripe.com/webhooks

### E) Google OAuth — Continue with Google

**الوظيفة:** تسجيل دخول المستخدم بحساب Google.

1. افتح https://console.cloud.google.com/ .
2. أنشئ مشروعًا، واضبط **OAuth consent screen**.
3. أنشئ **OAuth Client ID** من نوع **Web application**.
4. أضف Authorized redirect URI **مطابقًا حرفيًا** لـ:

~~~text
http://localhost/projects/LootLab/public/auth/google/callback
~~~

5. انسخ Client ID وClient Secret إلى \`.env\`.
6. إذا التطبيق بوضع Testing، أضف حسابات التيم المسموح لها ضمن Test users عند الحاجة.

**ملفات الميزة:** \`app/Services/GoogleAuthService.php\` و\`app/Controllers/AuthController.php\`.

التوثيق: https://developers.google.com/identity/protocols/oauth2

### F) خريطة عنوان التوصيل — Leaflet / OpenStreetMap / Nominatim

**الوظيفة:** المستخدم يبحث عن عنوان **داخل الأردن**، يحدد دبوس موقع الالتقاء، ونحفظ الإحداثيات مع الطلب.

- **Leaflet:** مكتبة JavaScript لعرض الخريطة.
- **OpenStreetMap:** بيانات الخرائط.
- **Nominatim:** البحث عن العناوين.
- لا تحتاج **Google Maps API Key** ولا HERE API Key.
- لازم إنترنت لتحميل Tiles والبحث.
- البحث يتم عند طلب المستخدم، مع الالتزام بحدود خدمة Nominatim العامة (بدون autocomplete كثيف).

**ملفات الميزة:** \`public/js/checkout-map.js\` و\`public/css/checkout-map.css\` و\`app/Views/checkout/index.php\`.

التوثيق: https://leafletjs.com/ · https://operations.osmfoundation.org/policies/nominatim/

### G) PhotoMaker — تصميم AI بالوجه (خدمة منفصلة)

**الوظيفة:** رفع صورة وتوليد تصميم مخصص باستخدام خدمة صور خارج تطبيق PHP.

- \`PHOTOMAKER_GENERATE_URL=http://127.0.0.1:7861/generate\` يشير إلى **سيرفر محلي منفصل**، وليس API يعمل تلقائيًا بمجرد تشغيل XAMPP.
- يجب تشغيل Backend الخاص بـ PhotoMaker بشكل مستقل وتوفير موارده/موديله قبل اختبار هذه الميزة.
- **لا يوجد أمر تثبيت عام موثوق** للخدمة من داخل مستودع PHP وحده؛ اطلب من مسؤول AI ملفات تشغيل خدمة PhotoMaker وتعليماتها إن كانت جزءًا من العرض.
- إذا لم تكن الخدمة تعمل، باقي الموقع وGemini يمكن تشغيلهما بشكل مستقل.

**ملفات الربط:** \`app/Services/AiImageService.php\` و\`app/Controllers/AiImageController.php\`.

### H) عرض منتجات 3D

**الوظيفة:** عرض ملفات \`.glb\` داخل صفحة المنتج وتدويرها. هذه ليست API مدفوعة.

- تحتاج ملفات المجسمات في \`public/models/collectibles/\`.
- تحتاج سكربت العرض \`public/js/product-3d.js\` وملفات Three.js المحلية في \`public/js/vendor/\`.
- إذا الصورة تظهر لكن المجسم لا يظهر، افحص Console وNetwork في F12 وتأكد من عدم وجود \`404\` لملف GLB أو JS.

---

## 7) ملفات المشروع الرئيسية

~~~text
LootLab/
├── app/
│   ├── Controllers/        # استقبال الطلبات وتنفيذ المنطق
│   ├── Models/             # قواعد البيانات والكيانات
│   ├── Services/           # Gemini, Stripe, Mail, TextBee, Google, PhotoMaker
│   ├── Middleware/         # حماية الوصول والصلاحيات
│   └── Views/              # واجهات المستخدم
├── config/config.php       # قراءة .env وتجهيز الإعدادات
├── database/               # SQL + migrations + seeds
├── public/                 # نقطة الدخول، CSS, JS, Images, Models
├── routes/web.php          # مسارات GET/POST وغيرها
├── vendor/                 # ينشئه Composer (لا يُرفع)
├── composer.json
├── composer.lock
├── .env.example            # مثال آمن للمفاتيح
└── README.md
~~~

## 8) لو ظهر خطأ — شوف هون

| المشكلة | الحل |
|---|---|
| \`Class Stripe\\StripeClient not found\` | نفّذ \`composer install\` وتأكد من تحميل \`vendor/autoload.php\` |
| \`Class PHPMailer not found\` | نفّذ \`composer install\` |
| \`Dotenv InvalidFileException / unexpected whitespace\` | راجع تنسيق \`.env\`، خصوصًا App Password التي تحتوي مسافات |
| \`Could not connect to SMTP host\` | اختبر \`smtp.gmail.com:465\` و\`MAIL_ENCRYPTION=ssl\` |
| إيميل التفعيل لا يصل | افحص Spam، إعدادات SMTP، وحظر إعادة الإرسال المؤقت |
| رابط تفعيل يبدأ بـ \`localhost\` | تأكد أنك تستخدم نسخة **Email OTP** الجديدة بدل روابط التفعيل القديمة |
| \`Too many requests\` | انتظر مهلة Rate Limit؛ لا تضغط Resend بشكل متكرر |
| \`Unknown column p.model_path\` | قاعدة البيانات أقدم من كود 3D؛ استورد Migration/SQL المناسب من المسؤول |
| \`Unknown column email_verified_at\` | Migration البريد لم يُطبّق |
| \`Unknown column delivery_lat\` | Migration خريطة التوصيل لم يُطبّق |
| صفحة Checkout أو الخريطة فارغة | راجع F12 Console/Network، الإنترنت، ومسارات الملفات |
| Gemini \`404\` | تحقق من اسم الموديل والـ API endpoint داخل \`AiService.php\` |
| TextBee لا يرسل | افحص Device ID، حالة الهاتف، رصيد الشريحة، وصلاحية Send SMS |
| Google \`redirect_uri_mismatch\` | طابق Redirect URI في Google Cloud مع \`.env\` |
| \`404\` في روابط صفحات PHP | تأكد من Apache و\`public/.htaccess\` و\`mod_rewrite\` و\`APP_URL\` |
| ملفات جديدة لا تظهر بعد تحديث Git | نفّذ \`git pull origin TARIFI\` و\`composer install\` إذا تغيرت الاعتمادات |

## 9) أوامر Git للتيم

### تنزيل آخر تحديثات TARIFI

~~~powershell
cd C:\xampp\htdocs\projects\LootLab
git switch TARIFI
git pull origin TARIFI
composer install
~~~

### رفع تعديلاتك إلى TARIFI (لمن عنده صلاحية Push)

~~~powershell
cd C:\xampp\htdocs\projects\LootLab
git status
git add app public routes database composer.json composer.lock README.md .env.example
git status
git commit -m "Integrate LootHub APIs and update team setup guide"
git pull --rebase origin TARIFI
git push origin TARIFI
~~~

**قبل \`git commit\`:** راجع \`git status\` و\`git diff --cached --name-only\`. تأكد أن \`.env\` وملفات الاختبار التي تحتوي أسرارًا **غير موجودة** في الملفات المرفوعة. إذا أحد أعضاء الفريق ليس مخولًا بالدفع مباشرة إلى \`TARIFI\`، ينشئ فرعًا خاصًا ويرفع Pull Request بدلًا من ذلك.

### فحص عدم تتبّع .env

~~~powershell
git check-ignore -v .env
git status --short
~~~

إذا كان \`.env\` متتبَّعًا من قبل (رغم وجود \`.gitignore\`)، احذفه من التتبّع **دون حذف الملف المحلي**:

~~~powershell
git rm --cached .env
~~~

**إذا سبق نشر مفتاح سري في GitHub، حذفه من آخر Commit لا يكفي: يجب إلغاء المفتاح وإنشاء مفتاح جديد.**

## 10) قائمة اختبار سريعة قبل العرض

- [ ] Apache وMySQL شغّالين
- [ ] \`composer install\` ناجح
- [ ] قاعدة البيانات وآخر migrations موجودة
- [ ] المنتجات والصور و3D تظهر
- [ ] Register → رمز Email OTP → تفعيل → Login
- [ ] Google Login (إذا مفاتيحه مجهزة)
- [ ] إضافة منتج للسلة وتعديل الكمية
- [ ] Checkout → تحديد موقع الأردن → SMS OTP
- [ ] Stripe Sandbox → دفع تجريبي → تأكيد الطلب
- [ ] Loot AI يرد بمعلومات من كتالوج المتجر
- [ ] لوحة Admin تعمل حسب صلاحيات الحساب
- [ ] لا توجد مفاتيح حقيقية أو \`.env\` في GitHub

---

### توزيع المسؤوليات

**مسؤول تكامل الـ APIs والـ AI:** يجهّز Gemini، PhotoMaker عند توفر Backend، TextBee، Stripe، Google OAuth، SMTP/Email OTP، وخريطة التوصيل، ويشارك إعدادات الاختبار **بطريقة آمنة**.

**باقي التيم:** يشغّلوا المشروع بالخطوات أعلاه، يبلغوا عن الأخطاء مع رسالة الخطأ والمسار (بدون إرسال أسرار)، ويحدثوا فرعهم قبل البدء.

> **آخر تنبيه:** هذه الوثيقة تصف التجهيز المطلوب وآخر ميزات التطوير. نجاح أي ميزة على جهاز جديد يتوقف على **وجود آخر كود وقاعدة بيانات وملفات الأصول في المستودع**، وليس على الـ README وحده.

**المستودع:** https://github.com/MOOM09/LootLab/tree/TARIFI
