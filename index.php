<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require "vendor/autoload.php";

use PeopleBitcoins\BtcPhp\BtcLib;
use PeopleBitcoins\BtcPhp\WalletInfo;

require_once(__DIR__.'/src/PeopleBitcoins/BtcPhp/WalletInfo.php');

if(file_exists('config.json')) {
    $config = json_decode(file_get_contents('config.json'), true);
} else {
    throw new Exception("No config file on disk");
}

function isPhpCli()
{
    return php_sapi_name() === 'cli';
}

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

testtcp($config['host'],8333);
testtcp($config['host'],8332);

testtcp($config['host'],18333);
testtcp($config['host'],18332);

echo "<br>" . PHP_EOL . PHP_EOL;

BtcLib::test(
    $config['host'],
    $config['port'],
    $config['rpcuser'],
    $config['rpcpassword']
);

echo "<br>" . PHP_EOL . PHP_EOL;

$btclib = new BtcLib(
    $config['host'],
    $config['port'],
    $config['rpcuser'],
    $config['rpcpassword']
);

if(!isPhpCli()) {

    require __DIR__ . '/ui.php';

    echo "<br>";

    phpinfo();
}

?>
