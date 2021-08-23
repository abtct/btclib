<?

namespace PeopleBitcoins\BtcPhp;

use Denpa\Bitcoin\Client as BitcoinClient;
use Denpa\Bitcoin\Config;
use Denpa\Bitcoin\Exceptions\BadRemoteCallException;
use Denpa\Bitcoin\Exceptions\ClientException;

// TODO: Check that 'confirmations' field is always initialized in TxInfo after these methods,
// Conditions for 'confirmations' field:
// https://bitcoin.stackexchange.com/questions/30878/how-to-get-the-number-of-the-confirmed-transactions-from-bitcoin-qt-rpc

class BtcLib implements IBtcLib
{
    protected $rpcuser, $rpcpassword, $host, $port;

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

        if($rpcwallet) {
            $c = new BitcoinClient($url);
            return $c->wallet($rpcwallet);
        }

        return new BitcoinClient($url);
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
    public function verificationProgress(): float
    {
        $resp = $this
            ->createClient(null)
            ->getBlockchainInfo()
            ->get();

        $key = 'verificationprogress';

        if(isset($resp[$key])) {
            return floatval($resp[$key]);
        }

        $keys = join(', ', array_keys($resp));
        throw new \Exception("No key {$key} in response to getblockchaininfo. Returned keys are: " . $keys);
    }

    /**
     * @inheritDoc
     */
    public function statusError(): ?string
    {
        try {
            $syncProgress = $this->verificationProgress();
            $syncProgress *= 100;

            if($syncProgress < 90) {
                return "blockchain syncing at {$syncProgress}%";
            }
        } catch(\Denpa\Bitcoin\Exceptions\BadRemoteCallException $ex) {
            return "error getting verificationProgress from node: {$ex->getMessage()}";
        } catch (\GuzzleHttp\Exception\ConnectException $_) {
            return "connect failed";
        }

        return null;
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
    public function loadWalletUnlocked(WalletInfo $wallet, int $passphraseUnlockTimeout = 60)
    {
        $this
            ->createClient(null)
            ->loadWallet($wallet->rpcwallet)
            ->get();

        if($wallet->passphrase) {
            $this
                ->createClient($wallet->rpcwallet)
                ->walletPassphrase($wallet->passphrase, $passphraseUnlockTimeout)
                ->get();
        }
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
    public function getTransactionInfo(WalletInfo $wallet, string $txid, bool $tryExtendedInfo = true): TxInfo
    {
        $resp = $this
            ->createClient($wallet->rpcwallet)
            ->getTransaction($txid, true)
            ->get();

        $result = new TxInfo((array)$resp);

        if($result->isReceive()) {
            $result = new TxInfoExtended($resp);
            $result->loadExtendedData($this, $wallet);
        }

        return $result;
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
     *
     * Credits to cryptean in C#
     *
     * @see https://github.com/cryptean/bitcoinlib/blob/master/src/BitcoinLib/Services/RpcServices/RpcExtenderService/RpcExtenderService.cs#L160
     */
    public function getTransactionSenderAddress(WalletInfo $wallet, string $txid): ?string
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

        try {
            $hex2 = $this
                ->createClient($wallet->rpcwallet)
                ->getRawTransaction($inputTxId, true)
                ->get()
            ['hex'];
        } catch(BadRemoteCallException $ex) {
            // Case 1: Blockchain transactions are still in the process of being indexed.
            // Case 2: txindex is 0 (bitcoin.conf)
            if(stripos($ex->getMessage(), 'Invalid or non-wallet transaction') === 0) {
                return null;
            }
        }

        $decoded2 = $this
            ->createClient($wallet->rpcwallet)
            ->decodeRawTransaction($hex2)
            ->get();

        $outputs = $decoded2['vout'];

        return $outputs[0]['scriptPubKey']['addresses'][0] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getTransactionsExtended(WalletInfo $wallet, int $max = 10, int $skip = 0): iterable
    {
        $resp = $this
            ->createClient($wallet->rpcwallet)
            ->listTransactions("*", $max, $skip, true)
            ->get();

        $items = [];
        if(isset($resp['txid'])) {
            $items[] = new TxInfoExtended($resp);
        } else {
            $items = array_map(function($item) {
                return new TxInfoExtended((array)$item);
            }, $resp);
        }

        foreach($items as $item) {
            $item->loadExtendedData($this, $wallet);
            yield $item;
        }
    }
}
