<?php
$json = '{"first_name": "Anil", "email": "anil.gupta@techinflo.com", "last_name": "Gupta", "order": null, "customer": null, "state": "paid", "id_transaction": 563015, "status": 0, "origin": " payplug-php 0.9 PHP 5.3.27", "custom_data": null, "custom_datas": null, "amount": 100}';
$decode = json_decode($json, true);
echo '<pre>';
print_r($decode);
echo $decode->first_name;
die('wwew');
require_once("../../lib/Payplug.php");
$parametersFile = __DIR__ . "/params.json";
$headers = getallheaders();
$parameters = Parameters::loadFromFile($parametersFile);

echo "in return";

/* For more security, put the keys in uppercase and retrieve
 * the signature using the key in uppercase
 */
$headers = array_change_key_case($headers, CASE_UPPER);
$signature = base64_decode($headers['PAYPLUG-SIGNATURE']);

/* The data is sent in the body of the POST request in JSON format
 * (MIME type = application / json).
 * Example : {"state": "paid", "customer", "2", "transaction_id": 4121, "custom_data": "29", "order": "42"}
 */
$body = file_get_contents('php://input');

$data = json_decode($body, true);
echo '<pre>';
print_r($data);

$pbkey = $parameters->payplugPublicKey;
/* $pbkey = PayPlug public key that you saved from the setup page */
$publicKey = openssl_pkey_get_public($pbkey);
$isSignatureValid = openssl_verify($body , $signature, $pbkey, OPENSSL_ALGO_SHA1);

/* Take into account the IPN and send an email with the confirmation*/
if($isSignatureValid){
    $message = "IPN received for ".$data->first_name." ".$data->last_name." for an amount of ".$data->amount." EUR";
    mail("anil.gupta@techinflo.com","IPN Received",$message);
} else {
    mail("anil.gupta@techinflo.com","IPN Failed","The signature was invalid");
}