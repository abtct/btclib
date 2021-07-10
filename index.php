<?php

require "vendor/autoload.php";

use PeopleBitcoins\BtcPhp\BtcPhp;
use PeopleBitcoins\BtcPhp\BtcLib;

$config = [
  'rpcuser'     => 'people_bitcoins',
  'rpcpassword' => 'MW6EJqKCWe',
];

function is_cli()
{
    return php_sapi_name() === 'cli';
}

function testtcp($port)
{

  $fp = fsockopen("127.0.0.1", $port, $errno, $errstr, 30);
  if (!$fp) {
      echo "$errstr ($errno)<br />\n";
  } else {
    echo "<p>Connection $port OK</p>";
      fclose($fp);
  }
}

testtcp(8333);
testtcp(8332);

echo "<br>" . PHP_EOL . PHP_EOL;

BtcPhp::test();

echo "<br>" . PHP_EOL . PHP_EOL;

BtcLib::test($config['rpcuser'], $config['rpcpassword']);

echo "<br>" . PHP_EOL . PHP_EOL;


if(is_cli()) {
  die();
}

echo "<br>";

phpinfo();


?>
