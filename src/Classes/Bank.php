<?php

declare(strict_types=1);

require_once __DIR__ . '/Customer.php';
require_once __DIR__ . '/BankAccount.php';
require_once __DIR__ . '/SavingsAccount.php';
require_once __DIR__ . '/CurrentAccount.php';
require_once __DIR__ . '/Transaction.php';
require_once __DIR__ . '/../Exceptions/InvalidAmountException.php';
require_once __DIR__ . '/../Validation/Validator.php';

final class Bank
{
	// Named policy value: the bank identity is shared by the application.
	public const BANK_NAME = 'BNR';

	/** @var array<string, Customer> */
	private array $customers = [];
	/** @var array<string, BankAccount> */
	private array $accounts = [];

	public function registerCustomer(Customer $customer): void
	{
		require_once __DIR__ . '/../Exceptions/DuplicateCustomerException.php';
		if (isset($this->customers[$customer->getCustomerId()])) {
			throw new DuplicateCustomerException('Customer ID is already registered.');
		}
		$this->customers[$customer->getCustomerId()] = $customer;
	}

	public function getCustomer(string $customerId): Customer
	{
		require_once __DIR__ . '/../Exceptions/CustomerNotFoundException.php';
		if (!isset($this->customers[$customerId])) {
			throw new CustomerNotFoundException('Customer was not found.');
		}
		return $this->customers[$customerId];
	}

	public function openSavingsAccount(string $customerId, string $accountNumber, int $openingBalanceInCents = SavingsAccount::MINIMUM_BALANCE): SavingsAccount
	{
		$customer = $this->getCustomer($customerId);
		$this->assertAccountNumberAvailable($accountNumber);
		$account = new SavingsAccount($accountNumber, $customer, $openingBalanceInCents);
		$this->registerAccount($account);
		return $account;
	}

	public function openCurrentAccount(string $customerId, string $accountNumber, int $openingBalanceInCents = 0): CurrentAccount
	{
		$customer = $this->getCustomer($customerId);
		$this->assertAccountNumberAvailable($accountNumber);
		$account = new CurrentAccount($accountNumber, $customer, $openingBalanceInCents);
		$this->registerAccount($account);
		return $account;
	}

	public function getAccount(string $accountNumber): BankAccount
	{
		require_once __DIR__ . '/../Validation/Validator.php';
		require_once __DIR__ . '/../Exceptions/AccountNotFoundException.php';
		$accountNumber = Validator::accountNumber($accountNumber);
		if (!isset($this->accounts[$accountNumber])) {
			throw new AccountNotFoundException('Account was not found.');
		}
		return $this->accounts[$accountNumber];
	}

	/** @return array{0: Transaction, 1: Transaction} */
	public function transfer(string $sourceAccountNumber, string $destinationAccountNumber, int $amountInCents, string $description = ''): array
	{
		$source = $this->getAccount($sourceAccountNumber);
		$destination = $this->getAccount($destinationAccountNumber);
		if ($source === $destination) {
			throw new InvalidAmountException('Source and destination accounts must differ.');
		}
		$amountInCents = Validator::amount($amountInCents);
		$fee = $source instanceof CurrentAccount ? $source->getTransferFeeInCents() : 0;
		$debit = $source->postTransferDebit($amountInCents + $fee, $destination->getAccountNumber(), $description);
		$credit = $destination->postTransferCredit($amountInCents, $source->getAccountNumber(), $description);
		return [$debit, $credit];
	}

	public function closeAccount(string $accountNumber): void
	{
		$this->getAccount($accountNumber)->close();
	}

	private function assertAccountNumberAvailable(string $accountNumber): void
	{
		require_once __DIR__ . '/../Validation/Validator.php';
		require_once __DIR__ . '/../Exceptions/DuplicateAccountException.php';
		$accountNumber = Validator::accountNumber($accountNumber);
		if (isset($this->accounts[$accountNumber])) {
			throw new DuplicateAccountException('Account number is already registered.');
		}
	}

	private function registerAccount(BankAccount $account): void
	{
		$this->accounts[$account->getAccountNumber()] = $account;
		$account->getAccountHolder()->addAccount($account);
	}
}
