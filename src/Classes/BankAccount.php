<?php

declare(strict_types=1);

require_once __DIR__ . '/Customer.php';
require_once __DIR__ . '/../Enums/AccountStatus.php';

abstract class BankAccount
{
    protected string $accountNumber;
    protected Customer $accountHolder;
    protected int $balanceInCents;
    protected AccountStatus $status;

    protected function __construct(
        string $accountNumber,
        Customer $accountHolder,
        int $openingBalanceInCents = 0,
    ) {
        $this->accountNumber = $accountNumber;
        $this->accountHolder = $accountHolder;
        $this->balanceInCents = $openingBalanceInCents;
        $this->status = AccountStatus::ACTIVE;
    }

    public function getAccountNumber(): string
    {
        return $this->accountNumber;
    }

    public function getBalanceInCents(): int
    {
        return $this->balanceInCents;
    }

    public function getStatus(): AccountStatus
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return $this->status === AccountStatus::ACTIVE;
    }
}