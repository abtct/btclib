<?php

// Включаем отображение ошибок PHP
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Подключение namespace BtcPhp
require "vendor/autoload.php";

use PeopleBitcoins\BtcPhp\BtcLib;

if(!file_exists('config.json')) {
    throw new Exception("No config file on disk");
}

// Загрузка $config из json-файла (host, port, rpcuser, rpcpassword)
$config = json_decode(file_get_contents('config.json'), true);

// Клиент для работы с нодой (настройки из нашего конфига)
$btclib = new BtcLib($config['host'], $config['port'], $config['rpcuser'], $config['rpcpassword']
);

// Проверить, если код выполняется в консоли
function isPhpCli()
{
    return php_sapi_name() === 'cli';
}

// HTML выводим только для браузера
if(!isPhpCli()) {
    require __DIR__ . '/ui.php';
    echo "<br>" . PHP_EOL . PHP_EOL;
}

/** Проверить, что порт TCP работает. */
function testtcp($host, $port)
{
    $fp = fsockopen($host, $port, $errno, $errstr, 30);
    if (!$fp) {
        echo "$errstr ($errno)<br />\n";
    } else {
        echo "<p>Connection $port OK</p>";
        fclose($fp);
    }
}

// проверим, что порты работают

// Порты используются для работы с режимом mainnet
testtcp($config['host'],8333);
testtcp($config['host'],8332);

// Порты используется для работы с режимом testnet
testtcp($config['host'],18333);
testtcp($config['host'],18332);

// HTML выводим только для браузера
if(!isPhpCli()) {
    echo "<br>" . PHP_EOL . PHP_EOL;
    phpinfo();
}
