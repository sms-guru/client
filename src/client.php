<?php

namespace smsguru;

use Carbon\Carbon;

class client {
	use iniFunctions;

	protected $username;
	protected $password;
	protected $gateway_url = 'https://console.smsguru.co/gw/smsguru/v3_0/send.php';
	// protected $gateway_url = 'http://localhost:81/gw/smsguru/v3_0/send.php';
	protected $balance_url = 'https://console.smsguru.co/api/balance/v3_0/getBalance';
	// protected $balance_url = 'http://localhost:81/api/balance/v3_0/getBalance';

	protected $ini_path = __DIR__ . "/data/token.ini";
	protected $token_url = "https://console.smsguru.co/oauth/token";
	protected $access_token;

	public function __construct($username, $password) 
	{
		$this->username = $username; 
		$this->password = $password; 		
		$this->gateway_url = $this->gateway_url . "?user=$this->username&pass=$this->password";
		$this->balance_url = $this->balance_url . "?user=$this->username&pass=$this->password";
	}

	// v3.0
	public function send($sms_data) {
        $from = $sms_data['from'];
        $to = $sms_data['to'];
        $text = $sms_data['text'];
		$this->gateway_url = $this->gateway_url . "&from=" . $from . "&to=" . $to . "&text=" . rawurlencode($text);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->gateway_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$sentResult = curl_exec($ch);
		if ($sentResult == FALSE) {
			return 'Curl failed for sending sms to smsguru.. '.curl_error($ch);
		}
		curl_close($ch);

		return $sentResult;
	}

	public function send2_0($sms_data) {
		$tokenResult = $this->prepareAccessToken();
		$AtokenResult = json_decode($tokenResult, true);
		if (isset($AtokenResult['code']) && $AtokenResult['code'] == 401) 
			return $tokenResult;
		$query_string = http_build_query($sms_data);
		return $this->postRequest($this->gateway_url, $query_string);
	}

	public function balance($country = null)
	{
		$this->balance_url = $this->balance_url . ($country ? "&country=$country" : '');
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->balance_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$balanceResult = curl_exec($ch);
		if ($balanceResult == FALSE) {
			return 'Curl failed for request balance from smsguru.. '.curl_error($ch);
		}
		curl_close($ch);

		return $balanceResult;
	}

	public function balance2_0($country = null)
	{
		$tokenResult = $this->prepareAccessToken();
		$AtokenResult = json_decode($tokenResult, true);
		if (isset($AtokenResult['code']) && $AtokenResult['code'] == 401) 
			return $tokenResult;

		$query_string = $country ? http_build_query($country) : [];
		return $this->postRequest($this->balance_url, $query_string);
	}

	public function prepareAccessToken()
	{
		// oauth mechanism not involve refresh token, involved only client_id & client_secret
		// load token info 
		$config = $this->loadINI();
		if ($config['expire_time']) {
			// subsequent load, check token expiry
			$recorded_time = new Carbon($config['expire_time']);
			if ($recorded_time->gte(Carbon::now())) {
				// token not expire, reuse it 
				$this->access_token = $config['access_token'];
				return;
			}
		}

		// 1st time load, or token expired, request token
		$token_data = $this->requestToken($this->username, $this->password);
		// Array
		// (
		// 	[error] => invalid_client
		// 	[error_description] => Client authentication failed
		// 	[message] => Client authentication failed
		// )
		if (isset($token_data['error_description']) && $token_data['error_description'] == "Client authentication failed") {
			return '{"code":401,"desc":"Invalid Username or password"}';
		}

		$data = [
			"expire_time"	=> Carbon::now()->addSeconds($token_data['expires_in'])->toString(),	// token expire date time 
			"access_token"	=> $token_data['access_token']
		];
		$this->updateINI($data);
		$this->access_token = $token_data['access_token'];
	}

	public function requestToken($username, $password)
	{
		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_URL => $this->token_url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => "grant_type=client_credentials&client_id=$username&client_secret=$password",
			CURLOPT_HTTPHEADER => [
			  "Accept: application/json"
			],
		]);

		$response = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);

		if ($err) {
			echo "cURL Error #:" . $err;
			exit();
		}

		// {"token_type":"Bearer","expires_in":3591,"access_token":"eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxNCIsImp0aSI6IjYxN2ZlNjc3MzE2ZDAyZWYxMmE2NTYxODkxZDllZmY2ZTI4ZTAwM2ZiMjQyMzIyYjgwZjg3M2JlODg3YmVhOTgwMTc3ZTdkODAwN2UyMWQ0IiwiaWF0IjoxNjE3OTAyMTAzLjU2MDg1OCwibmJmIjoxNjE3OTAyMTAzLjU2MDg2NiwiZXhwIjoxNjE3OTA1NzAzLjM5NzU1MSwic3ViIjoiIiwic2NvcGVzIjpbIioiXX0.a73ZYlB2FVxDPqtRhjm_PmDGkgduD81LmB3I28CUa3Oc0fhXxFqAKNiFFAsOnhCipAWVtA_AYR-GE7vN9tUjCzDvDFL7y2dQSgGPzZIowxoMV6idkg9PvVFo9dUZATrZfL550oaxMBTZyHgpkKpmD5mxGKS5XuVcvHN8420IRc7kL365ifsQdoQXbNTTQA17RbnUUFOEK14I4Bei6NhR_9Y6Yn7Pgb9tzauoH-UF0HraCq49beOtB3OeX991RsGa2YFONyA1AmAdaiN3fHnFK_K3RI3NI9ADGQhD3_Q61-LnG4LURmNGylUwOYWWlib0MWM7gD_lyDhUOVa0tU3N3OAUhXMQJfytjugjrninm6qqCiGjpBvaWM380jKqZLcXd1GPqVTY8pG9dYZY8fg37TsZ4MvMqLCVO0boBkUERYwZnHS8uhAnuh1Zmzjh5kqMnLO4c1EFWQrFmviPiLntEsovNd8_AdTfU25yzXbgV12AsxSjEaZxAQhPLwcpzeZy_C3dxAaZCxcKD7IdCWitEZ3G_vD1VHW1aZ401nLwSqmpC9LCJJ6swf_34muKIB8LPRd9XIWpXaUGaJe_qLz_7d4feKZjuAaBvPaay9GTsjGci9HH1rU-mncMGE7fcTrAjVgOlmHi0mL5jpE10AESNSslemRRTmnxCHZLLKQ19w4"}


		$Aresponse = json_decode($response, true);
		// print_r($Aresponse);
		return $Aresponse;
	}

	private function postRequest($url, $query_string)
	{
		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => $query_string,
			CURLOPT_HTTPHEADER => [
				"Authorization: Bearer " . $this->access_token,
				"Accept: application/json"
			],
		]);

		$sentResult = curl_exec($ch);
		if ($sentResult == FALSE) {
			return '{"code":500,"desc":"Server error, please contact support@smsguru.co ... ' . curl_error($ch) . '"}';
		}
		curl_close($ch);

		return $sentResult;
	}
}