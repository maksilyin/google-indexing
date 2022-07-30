<?php

namespace App;

use Google_Client;

class Indexing
{
    public static function validateKey($arKeyItem)
    {
        $fields = [
            'type',
            'project_id',
            'private_key_id',
            'private_key',
            'client_email',
            'client_id',
            'auth_uri',
            'token_uri',
            'auth_provider_x509_cert_url',
            'client_x509_cert_url',
        ];

        foreach ($fields as $field) {
            if (!isset($arKeyItem) || empty($arKeyItem)) {
                return false;
            }
        }

        return true;
    }

    public static function getHttpClient($arKey)
    {

        $client = new Google_Client();

        $client->setAuthConfig($arKey);
        $client->addScope('https://www.googleapis.com/auth/indexing');

        return $client->authorize();
    }

    public static function send($httpClient, $sUrl)
    {
        $endpoint = 'https://indexing.googleapis.com/v3/urlNotifications:publish';

        $content = '{
            "url": "http://f0650992.xsph.ru/test/",
            "type": "URL_UPDATED"
        }';

        return $httpClient->post($endpoint, [ 'body' => $content ]);
    }
}
