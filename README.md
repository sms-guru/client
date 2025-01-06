# SMSGuru Client API
PHP Client for send SMS using SMSGuru's API

In previous version, user credentials from our portal is required for each API triggered, this posts security threat by exposing account details in URL. And it shares the same credentials from API access and Web Portal access, this way, if user trigger forget password and updated their password, API will fail immediately. 


Installation:
```bash
composer require sms-guru/client
```

To send sms:
```php
use smsguru\client;
require_once "./vendor/autoload.php";

// Please whitelist your IP and enable API in console.smsguru.co before you call this 

$smsClient = new client('YOUR_APP_KEY', 'YOUR_APP_SECRET');
$response = $smsClient->send([
				'from'	=> '68068',
				'to'	=> '60123240066',
				'text'	=> 'Hi from Package 2.0'
			]);
```

To check account balance:
```php
use smsguru\client;
require_once "./vendor/autoload.php";

// Please whitelist your IP and enable API in console.smsguru.co before you call this 

$smsClient = new client('YOUR_APP_KEY', 'YOUR_APP_SECRET');
$response = $smsClient->balance([
				'country'	=> 'SGP'
			]);
```

To find out more info about our service, please visit [SMSGuru](console.smsguru.co) official site. 