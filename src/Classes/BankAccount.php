<?php

declare(strict_types=1);

require_once __DIR__ . '/Customer.php';
require_once __DIR__ . '/Transaction.php';
require_once __DIR__ . '/../Enums/AccountStatus.php';
require_once __DIR__ . '/../Enums/TransactionType.php';
require_once __DIR__ . '/../Validation/Validator.php';
require_once __DIR__ . '/../Exceptions/ClosedAccountException.php';
require_once __DIR__ . '/../Exceptions/InsufficientFundsException.php';
require_once __DIR__ . '/../Exceptions/InvalidAmountException.php';

abstract class BankAccount
{
    protected string $accountNumber;
    protected Customer $accountHolder;
    protected int $balanceInCents;
    protected AccountStatus $status;

    /** @var array<int, Transaction> */
    protected array $transactions = [];
    private static int $nextTransactionSequence = 1;

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

        $this->recordTransaction(new Transaction(
            $this->generateTransactionId(),
            new DateTimeImmutable('now'),
            TransactionType::ACCOUNT_OPENING,
            $openingBalanceInCents,
            null,
            $this->accountNumber,
            'Account opened',
        ));
    }

    public function getAccountNumber(): string
    {
        return $this->accountNumber;
    }

    public function getAccountHolder(): Customer
    {
        return $this->accountHolder;
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

    public function deposit(int $amountInCents, string $description = ''): Transaction
    {
        if (!$this->isActive()) {
            throw new ClosedAccountException('Cannot deposit into a closed account.');
        }

        $validatedAmount = Validator::amount($amountInCents);
        $cleanDescription = Validator::description($description);

        $this->balanceInCents += $validatedAmount;

        $transaction = new Transaction(
            $this->generateTransactionId(),
            new DateTimeImmutable('now'),
            TransactionType::DEPOSIT,
            $validatedAmount,
            null,
            $this->accountNumber,
            $cleanDescription === '' ? 'Deposit' : $cleanDescription,
        );

        $this->recordTransaction($transaction);

        return $transaction;
    }

    public function withdraw(int $amountInCents, string $description = ''): Transaction
    {
        if (!$this->isActive()) {
            throw new ClosedAccountException('Cannot withdraw from a closed account.');
        }

        $validatedAmount = Validator::amount($amountInCents);
        $cleanDescription = Validator::description($description);

        if (!$this->canWithdraw($validatedAmount)) {
            throw new InsufficientFundsException('Withdrawal exceeds the permitted balance or policy limit.');
        }

        $this->balanceInCents -= $validatedAmount;

        $transaction = new Transaction(
            $this->generateTransactionId(),
            new DateTimeImmutable('now'),
            TransactionType::WITHDRAWAL,
            $validatedAmount,
            $this->accountNumber,
            null,
            $cleanDescription === '' ? 'Withdrawal' : $cleanDescription,
        );

        $this->recordTransaction($transaction);

        return $transaction;
    }

    public function close(): void
    {
        if ($this->status === AccountStatus::CLOSED) {
            throw new ClosedAccountException('Account is already closed.');
        }

        $this->status = AccountStatus::CLOSED;
        $this->recordTransaction(new Transaction(
            $this->generateTransactionId(),
            new DateTimeImmutable('now'),
            TransactionType::ACCOUNT_CLOSURE,
            $this->balanceInCents,
            $this->accountNumber,
            null,
            'Account closed',
        ));
    }

    /**
     * @return array<int, Transaction>
     */
    public function getTransactions(): array
    {
        return [...$this->transactions];
    }

    protected function recordTransaction(Transaction $transaction): void
    {
        $this->transactions[] = $transaction;
    }

    protected function chargeFee(int $amountInCents, string $description): Transaction
    {
        if (!$this->isActive()) {
            throw new ClosedAccountException('Cannot charge a fee to a closed account.');
        }

        $validatedAmount = Validator::amount($amountInCents);
        $cleanDescription = Validator::description($description);

        if (!$this->canWithdraw($validatedAmount)) {
            throw new InsufficientFundsException('Account balance cannot cover the required fee.');
        }

        $this->balanceInCents -= $validatedAmount;

        $transaction = new Transaction(
            $this->generateTransactionId(),
            new DateTimeImmutable('now'),
            TransactionType::FEE,
            $validatedAmount,
            $this->accountNumber,
            null,
            $cleanDescription === '' ? 'Account fee' : $cleanDescription,
        );

        $this->recordTransaction($transaction);

        return $transaction;
    }

    abstract protected function canWithdraw(int $amountInCents): bool;

    private function generateTransactionId(): string
    {
        return sprintf('TXN-%010d', self::$nextTransactionSequence++);
    }
}