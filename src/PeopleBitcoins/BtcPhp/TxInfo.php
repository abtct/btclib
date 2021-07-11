<?php


namespace PeopleBitcoins\BtcPhp;

/**
 * Информация о транзакции.
 * @package PeopleBitcoins\BtcPhp
 */
class TxInfo
{
    /**
     * @var string Tx - хэш транзакции
     */
    public $tx;

    /**
     * @var int Unix-время создания транзакции (или завершения)
     */
    public $unixtime;

    /**
     * @var string Адрес отправителя
     */
    public $fromAddress;

    /**
     * @var string Адрес получателя
     */
    public $toAddress;

    /**
     * @var float Сумма перевода (без учета комиссии)
     */
    public $amount;

    /**
     * @var float Сумма коммисии
     */
    public $fee;

    /**
     * @var bool True - транзакция подтверждена (перевод выполнен)
     */
    public $complete;
}