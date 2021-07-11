<?

namespace PeopleBitcoins\BtcPhp;

use Denpa\Bitcoin\Client as BitcoinClient;
use Denpa\Bitcoin\Config;

class BtcLib implements IBtcLib
{
    protected $rpcuser, $rpcpassword, $host, $port;

    public static function test(string $host, string $port, string $rpcuser, string $rpcpassword)
    {
        $bitcoind = new BitcoinClient("http://{$rpcuser}:{$rpcpassword}@{$host}:{$port}/");

        try {
            $result = $bitcoind->listWallets()->get();

            echo "BtcLib OK";
        } catch(\Exception $ex) {
            echo "BtcLib exception: {$ex->getMessage()}";
            echo PHP_EOL . PHP_EOL . "<br>";
            echo "rpcuser = {$rpcuser} rpcpassword = **";
        }
    }

    public function __construct(string $host, string $port, string $rpcuser, string $rpcpassword)
    {
        $this->host = $host;
        $this->port = $port;
        $this->rpcuser = $rpcuser;
        $this->rpcpassword = $rpcpassword;
    }

    protected function createClient(?string $rpcwallet): BitcoinClient
    {
        $url = "http://{$this->rpcuser}:{$this->rpcpassword}@{$this->host}:{$this->port}/";

        $c = new FixedRpcClient($url, $rpcwallet);

        return $c;
    }

    protected function random(int $length): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * @inheritDoc
     */
    public function isReady(): bool
    {
        try {
            self::test($this->host, $this->port, $this->rpcuser, $this->rpcpassword);
            // todo: check block number in sync
            return true;
        } catch (\Exception $_) {}

        return false;
    }

    /**
     * @inheritDoc
     */
    public function getWallets(): array
    {
        $resp = $this
            ->createClient(null)
            ->listWallets()
            ->get();

        if(is_string($resp)) {
            return [$resp];
        }

        return array_values($resp);
    }

    /** @inheritDoc */
    public function newWallet(string $label = ""): WalletInfo
    {
        $result = new WalletInfo();
        $result->rpcwallet = "wl" . $this->random(6);
        $result->passphrase = $this->random(8);

        $resp = $this
            ->createClient($result->rpcwallet)
            ->createWallet($result->rpcwallet, false, false, $result->passphrase)
            ->get();

        $result->rpcwallet = is_string($resp) ? $resp : $resp['name'];

        $resp = $this
            ->createClient($result->rpcwallet)
            ->getNewAddress()
            ->get();

        $result->address = is_string($resp) ? $resp : $resp['address'];

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getBalance(WalletInfo $wallet): float
    {
        $resp = $this
            ->createClient($wallet->rpcwallet)
            ->getBalance()
            ->get();

        var_dump(compact('resp'));

        return floatval($resp);
    }

    /**
     * @inheritDoc
     */
    public function sendTransaction(WalletInfo $from, string $to): string
    {
        throw new \Exception("TODO");
    }

    /**
     * @inheritDoc
     */
    public function getTxInfo(string $tx): TxInfo
    {
        throw new \Exception("TODO");
    }

    /**
     * @inheritDoc
     */
    public function getTransactions(WalletInfo $wallet, int $max = 10): iterable
    {
        throw new \Exception("TODO");
    }

    /**
     * @inheritDoc
     */
    public function nodeId(): string
    {
        return $this->host .':'. $this->port;
    }
}
