<?php

define('USE_SMTP', false); //use wp_mail if this is set to false
define('SMTP_HOST', ''); //host for sending emails
define('SMTP_PORT', 25); //depending on encryption, 25 default
define('SMTP_ENCRYPTION', ''); //values: ssl, tls
define('SMTP_USERNAME', ''); //user authentication
define('SMTP_PASSWORD', ''); //password authentication
define('SMTP_FROM_EMAIL', 'artur@kamenew.de');
define('SMTP_FROM_NAME', 'Smartsteuer.de');

define('DEFAULT_TAB', 'overview'); //default tab when clicking on "Smart Kwk" plugin
define('BCC_EMAIL', 'akamenew@gmail.com'); //send email copy of voucher code (carboncopy@smartsteuer.de)
define('PLACEHOLDER_VOUCHER', '[[gutscheincode]]');
define('BACKOFFICE_REQUEST_URL', 'https://www.smartsteuer.de/portal/kwkOrders.html?token=A29F8KVcCGft'); //request url for obtaining k-numbers
define('BACKOFFICE_REQUEST_TIMEOUT', 30); //request url timeout
define('MAX_OVERVIEW_ROWS', 10); //rows displayed in overview tab (pagination)
define('ALLOWED_ORDERTIME_OFFSET', 2); //days after actual order date for backoffice request