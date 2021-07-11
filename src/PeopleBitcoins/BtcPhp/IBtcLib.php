<?php

namespace PeopleBitcoins\BtcPhp;

interface IBtcLib
{
    /** Уникальный идентификатор ноды, с которой происходит подключение */
    public function nodeId(): string;

    /**
     * Проверить подключение к ноде (JSON-RPC)
     *
     * @param string $host
     * @param string $port
     * @param string $rpcuser
     * @param string $prcpassword
     * @return mixed
     */
    public static function test(string $host, string $port, string $rpcuser, string $prcpassword);

    /**
     * Получить ОК по синхронизации (true - нода готова к работе)
     *
     * @return bool
     */
    public function isReady(): bool;

    /**
     * Вернуть список аккаунтов (rpcwallets) на этой ноде.
     *
     * @return []string
     */
    public function getWallets(): array;

    /**
     * Создать новый кошелек на ноде со случайными rpcwallet (ID) и паролем (passphrase).
     *
     * @param string $label label группы для занесения bitcoin-адреса в эту группу на ноде
     * @return WalletInfo
     */
    public function newWallet(string $label = ""): WalletInfo;

    /**
     * Получить текущий баланс на любом кошельке Bitcoin сети.
     *
     * @param WalletInfo $wallet    Доступ к кошельку
     * @return float
     */
    public function getBalance(WalletInfo $wallet): float;

    /**
     * Перевести средства с кошелька на ноде на любой другой кошелек.
     *
     * @param WalletInfo $from  Доступ к кошельку-донору
     * @param string $to        Адрес кошелька-получателя
     * @return string           Вернуть Tx - хэш созданной транзакции
     */
    public function sendTransaction(WalletInfo $from, string $to): string;

    /**
     * Получит информацию о транзакции/
     *
     * @param WalletInfo $wallet Доступ к кошельку
     * @param string $txid
     * @return mixed
     */
    public function getTransactionInfo(WalletInfo $wallet, string $txid): TxInfo;

    /**
     * @param WalletInfo $wallet            доступ к кошельку по которому ищем транзакции
     * @param int $max                      макс. количество
     * @param int $skip                     пропустить N транзакций в выоде
     * @param bool $watchOnlyIncluded       Include transactions to watch-only addresses (see 'importaddress')
     * @return []TxInfo                     Список транзакций
     */
    public function getTransactions(WalletInfo $wallet, bool $watchOnlyIncluded = true, int $max = 10, int $skip = 0): iterable;

    /**
     * @param WalletInfo $wallet            доступ к кошельку получателю транзакции
     * @param string $txid                  Tx - хэш созданной транзакции
     * @return string                       Адрес отправителя денег
     */
    public function getTransactionSenderAddress(WalletInfo $wallet, string $txid): string;
}