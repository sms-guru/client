<?php
use smsguru\client;
require_once "./vendor/autoload.php";

// Please whitelist your IP and enable API in console.smsguru.co before you call this 

$smsClient = new client('YOUR_APP_KEY', 'YOUR_APP_SECRET');
$response = $smsClient->send([
				'from'	=> '68068',
				'to'	=> '60123240066',
				'text'	=> 'Hi from Package 3.0'
			]);
print_r($response);

// $json_response = json_decode($response);
// print_r($json_response);


// Check account balance
// $balance = $smsClient->balance();
// print_r($balance);


// Check sms count that can send in China
$balance = $smsClient->balance(861);		
print_r($balance);
