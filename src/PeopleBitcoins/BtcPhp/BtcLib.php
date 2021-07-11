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

    /**
     * @inheritDoc
     */
    public function nodeId(): string
    {
        return $this->host .':'. $this->port;
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
    public function getBalance(WalletInfo $wallet, $enoughConfirmations = IBtcLib::DEFAULT_CONFIRMATIONS): float
    {
        $resp = $this
            ->createClient($wallet->rpcwallet)
            ->getBalance('*', $enoughConfirmations)
            ->get();

        return floatval($resp);
    }

    /**
     * @inheritDoc
     */
    public function createTransaction(WalletInfo $from, string $to, float $amount, bool $feeIsInAmount, $targetConfirmations = IBtcLib::DEFAULT_CONFIRMATIONS, string $estimateMode = 'ECONOMICAL'): string
    {
        if($from->passphrase) {
            $this
                ->createClient($from->rpcwallet)
                ->walletPassphrase($from->passphrase, 60)
                ->get();
        }

        $resp = $this
            ->createClient($from->rpcwallet)
            ->sendToAddress($to, $amount, '', '',  $feeIsInAmount, false, $targetConfirmations, $estimateMode)
            ->get();

        return strval($resp);
    }

    /**
     * @inheritDoc
     */
    public function getTransactionInfo( WalletInfo $wallet, string $txid): TxInfo
    {
        $resp = $this
            ->createClient($wallet->rpcwallet)
            ->getTransaction($txid, true)
            ->get()
            ['hex'];

        return new TxInfo((array)$resp);
    }

    /**
     * @inheritDoc
     */
    public function getTransactions(WalletInfo $wallet, bool $watchOnlyIncluded = true, int $max = 10, int $skip = 0): iterable
    {
        $resp = $this
            ->createClient($wallet->rpcwallet)
            ->listTransactions("*", $max, $skip, $watchOnlyIncluded)
            ->get();

        if(isset($resp['txid'])) {
            return [
                new TxInfo($resp),
            ];
        }

        return array_map(function($item) {
            return new TxInfo((array)$item);
        }, $resp);
    }

    /**
     * @inheritDoc
     */
    public function getTransactionSenderAddress(WalletInfo $wallet, string $txid): string
    {
        $hex1 = $this
            ->createClient($wallet->rpcwallet)
            ->getTransaction($txid, true)
            ->get()
        ['hex'];

        $decoded1 = $this
            ->createClient($wallet->rpcwallet)
            ->decodeRawTransaction($hex1)
            ->get();

        $inputs = $decoded1['vin'];
        $inputTxId = $inputs[0]['txid'];
        $vout = $inputs[0]['vout'];

        $hex2 = $this
            ->createClient($wallet->rpcwallet)
            ->getRawTransaction($inputTxId, true)
            ->get()
        ['hex'];

        $decoded2 = $this
            ->createClient($wallet->rpcwallet)
            ->decodeRawTransaction($hex2)
            ->get();

        // TODO: conf: txindex=1
        // daemon: -reindex=1

        throw new \Exception("TODO");
    }
}
