<?php

namespace PeopleBitcoins\BtcPhp;

use Exception;

/**
 * Информация о кошельке на ноде
 *
 * @package PeopleBitcoins\BtcPhp
 */
class WalletInfo implements \Serializable
{
    /** @var string */
    public $address;

    /** @var string */
    public $rpcwallet;

    /** @var string */
    public $passphrase;

    /**
     * @inheritDoc
     */
    public function serialize()
    {
        return json_encode(
            get_object_vars($this)
        );
    }

    /**
     * @inheritDoc
     */
    public function unserialize($data)
    {
        foreach(json_decode($data, true) as $key => $value) {
            $this->$key = $value;
        }
    }
}