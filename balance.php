<?php
use smsguru\client;
require_once "./vendor/autoload.php";

// Please whitelist your IP and enable API in console.smsguru.co before you call this 

$smsClient = new client('YOUR_APP_KEY', 'YOUR_APP_SECRET');


# To check default balance in MYR 
$response = $smsClient->balance();
print_r($response);
echo "\n";

$arr_response = json_decode($response, true);
print_r($arr_response);


# To check credit balance for a country
$response = $smsClient->balance(['country' => '65']);
print_r($response);
echo "\n";

$json_response = json_decode($response);
print_r($json_response);