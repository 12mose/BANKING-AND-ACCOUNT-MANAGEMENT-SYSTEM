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
	private string $accountNumber;
	private Customer $accountHolder;
	private int $balanceInCents;
	private AccountStatus $status;
	/** @var array<int, Transaction> */
	private array $transactions = [];
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
		$this->assertActive();
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
		$this->assertActive();
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

	public function postTransferDebit(int $amountInCents, string $destinationAccountNumber, string $description = ''): Transaction
	{
		$this->assertActive();
		$validatedAmount = Validator::amount($amountInCents);
		$destination = Validator::accountNumber($destinationAccountNumber);
		$cleanDescription = Validator::description($description);

		if (!$this->canWithdraw($validatedAmount)) {
			throw new InsufficientFundsException('Transfer is not permitted by this account policy.');
		}

		$this->balanceInCents -= $validatedAmount;
		$transaction = new Transaction(
			$this->generateTransactionId(),
			new DateTimeImmutable('now'),
			TransactionType::TRANSFER,
			$validatedAmount,
			$this->accountNumber,
			$destination,
			$cleanDescription === '' ? 'Transfer debit' : $cleanDescription,
		);
		$this->recordTransaction($transaction);

		return $transaction;
	}

	public function postTransferCredit(int $amountInCents, string $sourceAccountNumber, string $description = ''): Transaction
	{
		$this->assertActive();
		$validatedAmount = Validator::amount($amountInCents);
		$source = Validator::accountNumber($sourceAccountNumber);
		$cleanDescription = Validator::description($description);

		$this->balanceInCents += $validatedAmount;
		$transaction = new Transaction(
			$this->generateTransactionId(),
			new DateTimeImmutable('now'),
			TransactionType::TRANSFER,
			$validatedAmount,
			$source,
			$this->accountNumber,
			$cleanDescription === '' ? 'Transfer credit' : $cleanDescription,
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

	/** @return array<int, Transaction> */
	public function getTransactions(): array
	{
		return [...$this->transactions];
	}

	protected function chargeFee(int $amountInCents, string $description): Transaction
	{
		$this->assertActive();
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

	protected function getBalanceForPolicy(): int
	{
		return $this->balanceInCents;
	}

	protected function recordTransaction(Transaction $transaction): void
	{
		$this->transactions[] = $transaction;
	}

	private function assertActive(): void
	{
		if (!$this->isActive()) {
			throw new ClosedAccountException('Closed accounts cannot perform financial operations.');
		}
	}

	private function generateTransactionId(): string
	{
		return sprintf('TXN-%010d', self::$nextTransactionSequence++);
	}
}
