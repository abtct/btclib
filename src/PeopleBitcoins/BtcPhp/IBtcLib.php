<?php

namespace PeopleBitcoins\BtcPhp;

interface IBtcLib
{
    /**
     * По умолчанию Bitcoin-клиент считает транзакции достоверными на уровне 6 подтверждений (google)
     */
    public const DEFAULT_CONFIRMATIONS = 5;

    /** Уникальный идентификатор ноды, с которой происходит подключение */
    public function nodeId(): string;

    /**
     * Попытка выполнить вызов getblockchaininfo и прочитать степень синхронизации ноды в процентах (от 0 до 1)
     *
     * @return float
     */
    public function verificationProgress(): float;

    /**
     * Возвращает строку с описанием причины, по которой работать с нодой в данный момент нельзя.
     * Или null, если все ок.
     *
     * @return string|null
     */
    public function statusError(): ?string;

    /**
     * Вернуть список аккаунтов (rpcwallets) на этой ноде.
     *
     * @return array []string
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
     * @param WalletInfo $wallet Доступ к кошельку
     * @param int $enoughConfirmations
     * @return float
     * @oaram int $enoughConfirmations  Минимальное количество подтверждений у транзакций на кошельке для подсчета баланса
     */
    public function getBalance(WalletInfo $wallet, $enoughConfirmations = self::DEFAULT_CONFIRMATIONS): float;

    /**
     * Перевести средства с кошелька на ноде на любой другой кошелек.
     *
     * @param WalletInfo $from              Доступ к кошельку-донору
     * @param string $to                    Адрес кошелька-получателя
     * @param float $amount                 Сумма перевода
     * @param bool $feeIsInAmount           True - сумма перевода будет уменьшена в пользу коммисии
     * @param int $targetConfirmations      Целевое количество подтверждений
     * @param string $estimateMode          Режим расчета комиссии (?)
     * @return string                       Вернуть Tx - хэш созданной транзакции
     */
    public function createTransaction(WalletInfo $from, string $to, float $amount, bool $feeIsInAmount, $targetConfirmations = self::DEFAULT_CONFIRMATIONS + 1, string $estimateMode = 'ECONOMICAL'): string;

    /**
     * Получить информацию о транзакции (должна принадлежать кошельку)
     *
     * @param WalletInfo $wallet Доступ к кошельку
     * @param string $txid
     * @return mixed
     */
    public function getTransactionInfo(WalletInfo $wallet, string $txid): TxInfo;

    /**
     * Получить список транзакций по кошельку (базовый метод).
     * Порядок - от старых к новым.
     *
     * @param WalletInfo $wallet            доступ к кошельку по которому ищем транзакции
     * @param int $max                      макс. количество
     * @param int $skip                     пропустить N транзакций в выоде
     * @param bool $watchOnlyIncluded       Include transactions to watch-only addresses (see 'importaddress')
     * @return []TxInfo                     Список транзакций
     */
    public function getTransactions(WalletInfo $wallet, bool $watchOnlyIncluded = true, int $max = 10, int $skip = 0): iterable;

    /**
     * Получить для транзакции адрес отправителя.
     * Возможно при выполнении с аккаунта-получателя указываемой транзакции.
     *
     * @param WalletInfo $wallet            доступ к кошельку получателю транзакции
     * @param string $txid                  Tx - хэш созданной транзакции
     * @return string|null                       Адрес отправителя денег
     */
    public function getTransactionSenderAddress(WalletInfo $wallet, string $txid): ?string;

    /**
     * Получить список транзакций с расширенной информацией (адреса отправителей)
     *
     * @param WalletInfo $wallet
     * @param int $max
     * @param int $skip
     * @return iterable|[]TxInfoExtended
     */
    public function getTransactionsExtended(WalletInfo $wallet, int $max = 10, int $skip = 0): iterable;
}