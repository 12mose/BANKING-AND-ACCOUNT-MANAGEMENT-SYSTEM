<?php

declare(strict_types=1);

require_once __DIR__ . '/BankAccount.php';
require_once __DIR__ . '/CurrentAccount.php';
require_once __DIR__ . '/Customer.php';
require_once __DIR__ . '/SavingsAccount.php';
require_once __DIR__ . '/../Exceptions/AccountNotFoundException.php';
require_once __DIR__ . '/../Exceptions/DuplicateAccountException.php';
require_once __DIR__ . '/../Exceptions/DuplicateCustomerException.php';
require_once __DIR__ . '/../Exceptions/CustomerNotFoundException.php';
require_once __DIR__ . '/../Validation/Validator.php';

final class Bank
{
	// Named policy value: the bank identity is shared by the application.
	public const BANK_NAME = 'BNR';

	/** @var array<string, Customer> */
	private array $customers = [];
	/** @var array<string, BankAccount> */
	private array $accounts = [];
	private int $nextAccountNumber = 1;

	public function registerCustomer(Customer $customer): void
	{
		$customerId = $customer->getCustomerId();

		if (isset($this->customers[$customerId])) {
			throw new DuplicateCustomerException('Customer id is already registered.');
		}

		$this->customers[$customerId] = $customer;
	}

	public function getCustomer(string $customerId): Customer
	{
		$normalizedId = trim($customerId);

		if (!isset($this->customers[$normalizedId])) {
			throw new CustomerNotFoundException('Customer not found.');
		}

		return $this->customers[$normalizedId];
	}

	public function registerAccount(BankAccount $account): void
	{
		$accountNumber = Validator::accountNumber($account->getAccountNumber());
		$customer = $account->getAccountHolder();

		if (isset($this->accounts[$accountNumber])) {
			throw new DuplicateAccountException('Account number is already registered.');
		}

		if (!isset($this->customers[$customer->getCustomerId()])) {
			throw new CustomerNotFoundException('Account holder is not registered.');
		}

		if ($this->customers[$customer->getCustomerId()] !== $customer) {
			throw new DuplicateCustomerException('Account holder does not match the registered customer.');
		}

		$customer->addAccount($account);
		$this->accounts[$accountNumber] = $account;
	}

	public function openSavingsAccount(
		string $customerId,
		string $accountNumber,
		int $openingBalanceInCents = SavingsAccount::MINIMUM_BALANCE,
	): SavingsAccount {
		$account = new SavingsAccount(
			Validator::accountNumber($accountNumber),
			$this->getCustomer($customerId),
			$openingBalanceInCents,
		);
		$this->registerAccount($account);

		return $account;
	}

	public function openCurrentAccount(
		string $customerId,
		string $accountNumber,
		int $openingBalanceInCents = 0,
	): CurrentAccount {
		$account = new CurrentAccount(
			Validator::accountNumber($accountNumber),
			$this->getCustomer($customerId),
			$openingBalanceInCents,
		);
		$this->registerAccount($account);

		return $account;
	}

	public function generateAccountNumber(): string
	{
		do {
			$accountNumber = sprintf('%s-%06d', self::BANK_NAME, $this->nextAccountNumber++);
		} while (isset($this->accounts[$accountNumber]));

		return $accountNumber;
	}

	public function getAccount(string $accountNumber): BankAccount
	{
		$normalizedNumber = Validator::accountNumber($accountNumber);

		if (!isset($this->accounts[$normalizedNumber])) {
			throw new AccountNotFoundException('Account not found.');
		}

		return $this->accounts[$normalizedNumber];
	}

	/** @return array<string, Customer> */
	public function getCustomers(): array
	{
		return $this->customers;
	}

	/** @return array<string, BankAccount> */
	public function getAccounts(): array
	{
		return $this->accounts;
	}
}
