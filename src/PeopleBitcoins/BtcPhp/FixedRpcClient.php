<?php


namespace PeopleBitcoins\BtcPhp;

use Denpa\Bitcoin\Client as BitcoinClient;

class FixedRpcClient extends BitcoinClient
{
    /**
     * FixedRpcClient constructor.
     * @param $config string|array
     * @param $rpcwallet ?string
     */
    public function __construct($config, $rpcwallet)
    {
        parent::__construct($config);

        if(!is_null($rpcwallet))
        {
            $this->path = "wallet/{$rpcwallet}";
        }
    }
}