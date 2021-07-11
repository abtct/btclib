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
    public $txid;

    /**
     * @var int Unix-время создания транзакции
     */
    public $time;

    /** @var int Количество подтверждений транзакции */
    public $confirmations;

    /**
     * @var string Адрес получателя транзакции
     */
    public $address;

    /** @var string Тип тразакции */
    public $category;

    /**
     * @var float Сумма перевода (без учета комиссии), отрицательная в случае траты
     */
    public $amount;

    /**
     * @var ?float Сумма коммисии
     */
    public $fee;

    /** @var bool true - транзакция забыта (не подтверждена и оставлена) */
    public $abandoned;

    /**
     * Загрузить значения полей из массива декодированной HEX транзакции.
     *
     * @param IBtcLib $btclib
     * @param array $resp
     */
    public function __construct(array $resp)
    {
        if(isset($resp['details'])) {
            $resp = array_merge($resp, $resp['details']);
            unset($resp['details']);
        }

        foreach($resp as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * Человекочитаемое время транзакции
     *
     * @return string
     */
    public function displayTime(): string
    {
        $dt = new \DateTime();
        $dt->setTimestamp($this->time);
        return $dt->format(DATE_RFC822);
    }

    /**
     * Отобразить статус (Abandoned/OK/Progress)
     *
     * @param int $enoughConfirmations
     * @return string
     */
    public function displayStatus(int $enoughConfirmations = IBtcLib::DEFAULT_CONFIRMATIONS): string
    {
        if($this->abandoned) {
            return 'Abandoned';
        }

        if($this->confirmations >= $enoughConfirmations) {
            return 'OK';
        }

        return 'Progress';
    }

    /**
     * Вид транзакции относительно кошелька (Send/Receive)
     *
     * @return string
     */
    public function displayType(): string
    {
        if($this->category) {
            return ucfirst($this->category);
        }

        if($this->amount > 0) {
            return "Receive";
        }

        return "Send";
    }

    /**
     * Проверить, что тип транзакции - получение денег от кого-то еще.
     *
     * @return bool
     */
    public function isReceive(): bool
    {
        return $this->displayType() == 'Receive';
    }
}