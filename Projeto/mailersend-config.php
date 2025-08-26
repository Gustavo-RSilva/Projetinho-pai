<?php
// mailersend-config.php
// >>> Ajuste para seus dados reais <<<

define('MS_SMTP_HOST', 'smtp.mailersend.net');
define('MS_SMTP_PORT', 587);              // 587 (TLS) ou 465 (SSL)
define('MS_SMTP_SECURE', 'tls');          // 'tls' ou 'ssl'
define('MS_SMTP_USER', 'apikey');         // SEMPRE 'apikey'
define('MS_SMTP_PASS', 'mlsn.8a3cdcac2155274887138b0edb904e74cf6198059793618ef7e15ebadc5850f9'); // seu API token do MailerSend

define('MS_FROM_EMAIL', 'test-p7kx4xwxoomg9yjr.mlsender.net'); // precisa ser domínio verificado no MailerSend
define('MS_FROM_NAME',  'Sistema');                 // nome amigável do remetente
