<?php

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' ) {
    die();
}

require_once "../vendor/autoload.php";

use App\Indexing;

$_POST = json_decode(file_get_contents('php://input'), true);

$arKey = $_POST['key'];
$arUrls = $_POST['urls'];

$arResponse = [];

if (!Indexing::validateKey($arKey)) {
    return;
}

$httpClient = Indexing::getHttpClient($arKey);

foreach ($arUrls as $key => $sUrl) {

    if (!filter_var($sUrl, FILTER_VALIDATE_URL)) {
        continue;
    }
    try {
        $response = Indexing::send($httpClient, $sUrl);

        $code = $response->getStatusCode();
        $codePhrase = $response->getReasonPhrase();

        if ($code === 200 || $code === 403) {
            $arResponse['content'][] = [
                'url' => $sUrl,
                'status' => $codePhrase,
                'code' => $code,
                'body' => json_decode((string)$response->getBody()),
            ];
        }

        else {
            $arResponse['error'] = [
                'urls' => array_slice($arUrls, $key),
                'client_email' => $arKey['client_email'],
                'body' => json_decode((string)$response->getBody()),
                'code' => $code,
            ];
            break;
        }
    }
    catch(Exception $e) {
        $arResponse['error'] = [
            'urls' => array_slice($arUrls, $key),
            'code' => 500,
            'body' => $e->getMessage(),
            'client_email' => $arKey['client_email'],
        ];
        break;
    }
}

echo json_encode($arResponse);



