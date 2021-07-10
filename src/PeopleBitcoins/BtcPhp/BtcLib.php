<?

namespace PeopleBitcoins\BtcPhp;

use Denpa\Bitcoin\Client as BitcoinClient;

class BtcLib
{
    public static function test(string $rpcuser, string $rpcpassword)
    {
        try {
            $bitcoind = new BitcoinClient("http://{$rpcuser}:{$rpcpassword}@127.0.0.1:8332/");

              $result = $bitcoind->listWallets()->get();

            var_dump($result);

            echo "BtcLib OK";
        } catch(\Exception $ex) {
            echo "BtcLib exception: {$ex->getMessage()}";
            echo PHP_EOL . PHP_EOL . "<br>";
            echo "rpcuser = {$rpcuser} rpcpassword = **";
        }
    }
}
