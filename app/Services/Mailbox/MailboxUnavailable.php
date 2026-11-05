<?php

namespace App\Services\Mailbox;

use RuntimeException;

/**
 * E-posta sunucusuna bağlanılamadı veya işlem reddedildi. Ayrıntı yalnızca uygulama loguna yazılır;
 * kullanıcıya genel bir mesaj gösterilir.
 */
class MailboxUnavailable extends RuntimeException {}
