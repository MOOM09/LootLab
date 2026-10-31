
<?php

$config = require __DIR__ . '/config/config.php';

require_once __DIR__ . '/app/Services/SmsService.php';

try {
    $sms = new SmsService();

    $result = $sms->send(
        '+962797209466',
        'LootLab: Your SMS integration is working!'
    );

    echo 'SMS accepted by Textbee!';
    print_r($result);

} catch (Throwable $e) {
    echo 'Error: ' . $e->getMessage();
}
