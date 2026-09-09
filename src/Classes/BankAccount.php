<?php

declare(strict_types=1);

require_once __DIR__ . '/Customer.php';
require_once __DIR__ . '/../Enums/AccountStatus.php';
require_once __DIR__ . '/../Enums/TransactionType.php';
require_once __DIR__ . '/../Exceptions/ClosedAccountException.php';
require_once __DIR__ . '/../Exceptions/InsufficientFundsException.php';
require_once __DIR__ . '/../Validation/Validator.php';
require_once __DIR__ . '/Transaction.php';

abstract class BankAccount
{
    private string $accountNumber;
    private Customer $accountHolder;
    private int $balanceInCents;
    private AccountStatus $status;
    /** @var array<int, Transaction> */
    private array $transactions = [];

    protected function __construct(
        string $accountNumber,
        Customer $accountHolder,
        int $openingBalanceInCents = 0,
    ) {
        $this->accountNumber = Validator::accountNumber($accountNumber);
        $this->accountHolder = $accountHolder;
        if ($openingBalanceInCents < 0) {
            throw new InvalidAmountException('Opening balance cannot be negative.');
        }
        $this->balanceInCents = $openingBalanceInCents;
        $this->status = AccountStatus::ACTIVE;
    }

    public function getAccountNumber(): string
    {
        return $this->accountNumber;
    }

    public function getAccountHolder(): Customer { return $this->accountHolder; }

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

    public function deposit(int $amountInCents, string $description = ''): Transaction
    {
        $this->assertActive();
        $amountInCents = Validator::amount($amountInCents);
        $transaction = $this->newTransaction(TransactionType::DEPOSIT, $amountInCents, null, null, $description);
        $this->balanceInCents += $amountInCents;
        $this->recordTransaction($transaction);
        return $transaction;
    }

    public function withdraw(int $amountInCents, string $description = ''): Transaction
    {
        $this->assertActive();
        $amountInCents = Validator::amount($amountInCents);
        if (!$this->canWithdraw($amountInCents)) {
            throw new InsufficientFundsException('Withdrawal is not permitted by this account policy.');
        }

        $transaction = $this->newTransaction(TransactionType::WITHDRAWAL, $amountInCents, $this->accountNumber, null, $description);
        $this->balanceInCents -= $amountInCents;
        $this->recordTransaction($transaction);
        return $transaction;
    }

    public function close(): void
    {
        if (!$this->isActive()) {
            throw new ClosedAccountException('Account is already closed.');
        }
        $this->status = AccountStatus::CLOSED;
    }

    /** @return array<int, Transaction> */
    public function getTransactions(): array { return $this->transactions; }

    abstract protected function canWithdraw(int $amountInCents): bool;

    protected function getBalanceForPolicy(): int { return $this->balanceInCents; }

    public function postTransferDebit(int $amountInCents, string $destinationAccountNumber, string $description = ''): Transaction
    {
        $this->assertActive();
        $amountInCents = Validator::amount($amountInCents);
        if (!$this->canWithdraw($amountInCents)) {
            throw new InsufficientFundsException('Transfer is not permitted by this account policy.');
        }
        $transaction = $this->newTransaction(TransactionType::TRANSFER, $amountInCents, $this->accountNumber, Validator::accountNumber($destinationAccountNumber), $description);
        $this->balanceInCents -= $amountInCents;
        $this->recordTransaction($transaction);
        return $transaction;
    }

    public function postTransferCredit(int $amountInCents, string $sourceAccountNumber, string $description = ''): Transaction
    {
        $this->assertActive();
        $amountInCents = Validator::amount($amountInCents);
        $transaction = $this->newTransaction(TransactionType::TRANSFER, $amountInCents, Validator::accountNumber($sourceAccountNumber), $this->accountNumber, $description);
        $this->balanceInCents += $amountInCents;
        $this->recordTransaction($transaction);
        return $transaction;
    }

    private function assertActive(): void
    {
        if (!$this->isActive()) {
            throw new ClosedAccountException('Closed accounts cannot perform financial operations.');
        }
    }

    private function newTransaction(TransactionType $type, int $amount, ?string $source, ?string $destination, string $description): Transaction
    {
        return new Transaction(uniqid('TX-', true), new DateTimeImmutable(), $type, $amount, $source, $destination, $description);
    }

    private function recordTransaction(Transaction $transaction): void
    {
        $this->transactions[] = $transaction;
    }
}