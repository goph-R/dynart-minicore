;<?php '

; Logger configuration, level can be "info", "warning", "error"
; Path can be relative to the working directory or an absolute path.
logger.level = "error"
logger.path = "logs/error.log"

; Database configuration
database.default.dsn = "mysql:localhost"
database.default.name = ""
database.default.user = ""
database.default.password = ""

; All locales separated by comma.
; Currently supported: en, hu
translation.all = "en"

; Default locale
translation.default = "en"

; If the application is in a folder (for example: https://domain.com/app)
; put the folder path here, start with a slash! (for example: /app)
request.uri_prefix = "/gears/admin"

; The base URL of the application (for example: https://domain.com/app)
router.base_url = "http://localhost/gears/admin"

; The index file (usually index.php)
router.index = "index.php"

; Is the server uses rewrite for routing?
; Check the example .htaccess file for Apache server config.
router.use_rewrite = false

; The query parameter that will be used for routing.
router.parameter = "route"

; A prefix that will be always on the beginning of the route paths.
; You can define variables like {variable} in it. Must start with a slash!
; When you have more locale (translation.all), set it to "/{locale}".
router.path_prefix = ""

; Is the mailer send fake emails? If it is true,
; it will only log the email on info level.
mailer.fake = false

; The mail server options
mailer.host = ""
mailer.port = ""
mailer.username = ""
mailer.password = ""

; Is the mailer uses an SMTP authentication?
mailer.smtp_auth = true

; How the SMTP is secured? Can be: tls or ssl
mailer.smtp_secure = "ssl"

; Should PHPMailer verifying SSL certs?
mailer.verify_ssl = false

; The debug level for PHPMailer
mailer.debug_level = 0

; The character set for PHPMailer
mailer.charset = "UTF-8"

; The encoding type for PHPMailer
mailer.encoding = "quoted-printable"

; The email sender
mailer.from_email = "info@yourdomain.com"
mailer.from_name = "From Name"

; The absolute path of the root folder of the application.
; For example: /var/www/domain.com
app.path = ""

; In paths and URLs the ~ symbol equals with the root folder (app.path)
; or with the base url (router.base_url).

; Path to the minicore framework folder
app.core_folder = "~/vendor/dynart/minicore"

; Path to a folder that contains the error html files (401.html, 500.html, ..)
app.error_static_folder = "~/vendor/dynart/minicore/static"

; URL for the static files (css, js, etc.)
app.static_url = "~/static"

; Path and URL for the media files (usually uploaded by users)
app.media_folder = "~/media"
app.media_url = "~/media"

;';