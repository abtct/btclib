<?php

namespace PeopleBitcoins\BtcPhp;

/**
 * Информация о транзакции вместе с адресом отправителя
 *
 * @package PeopleBitcoins\BtcPhp
 */
final class TxInfoExtended extends TxInfo
{
    /**
     * @var ?string
     */
    public $senderAddress;

    /**
     * Загрузить доп. информацию о транзакции
     *
     * @param IBtcLib $btclib
     * @param WalletInfo $wallet
     * @return bool
     */
    public function loadExtendedData(IBtcLib $btclib, WalletInfo $wallet)
    {
        if($this->senderAddress) {
            return true;
        }

        if(!$this->txid) {
            return false;
        }

        $this->senderAddress = $btclib->getTransactionSenderAddress($wallet, $this->txid);

        return !empty($this->senderAddress);
    }
}