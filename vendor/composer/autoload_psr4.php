<?php

// autoload_psr4.php @generated by Composer

$vendorDir = dirname(__DIR__);
$baseDir = dirname($vendorDir);

return array(
    'Vendor\\Test\\' => array($baseDir . '/src'),
    'Psr\\SimpleCache\\' => array($vendorDir . '/psr/simple-cache/src'),
    'Psr\\Http\\Message\\' => array($vendorDir . '/psr/http-message/src', $vendorDir . '/psr/http-factory/src'),
    'Psr\\Http\\Client\\' => array($vendorDir . '/psr/http-client/src'),
    'Psr\\Container\\' => array($vendorDir . '/psr/container/src'),
    'Kunnu\\Dropbox\\' => array($vendorDir . '/kunalvarma05/dropbox-php-sdk/src/Dropbox'),
    'Illuminate\\Support\\' => array($vendorDir . '/illuminate/conditionable', $vendorDir . '/illuminate/macroable', $vendorDir . '/illuminate/collections'),
    'Illuminate\\Contracts\\' => array($vendorDir . '/illuminate/contracts'),
    'GuzzleHttp\\Psr7\\' => array($vendorDir . '/guzzlehttp/psr7/src'),
    'GuzzleHttp\\Promise\\' => array($vendorDir . '/guzzlehttp/promises/src'),
    'GuzzleHttp\\' => array($vendorDir . '/guzzlehttp/guzzle/src'),
);
