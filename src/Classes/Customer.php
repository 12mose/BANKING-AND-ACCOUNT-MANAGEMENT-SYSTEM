<?php

declare(strict_types=1);

require_once __DIR__ . '/../Exceptions/AccountNotFoundException.php';
require_once __DIR__ . '/../Exceptions/InvalidNameException.php';
require_once __DIR__ . '/../Validation/Validator.php';

final class Customer
{
	private readonly string $customerId;
	private string $fullName;
	private string $email;
	/** @var array<string, BankAccount> */
	private array $accounts = [];

	public function __construct(string $customerId, string $fullName, string $email)
	{
		$customerId = trim($customerId);
		if ($customerId === '') {
			throw new InvalidNameException('Customer ID must not be blank.');
		}

		$this->customerId = $customerId;
		$this->updateContactDetails($fullName, $email);
	}

	public function getCustomerId(): string { return $this->customerId; }
	public function getFullName(): string { return $this->fullName; }
	public function getEmail(): string { return $this->email; }

	public function updateContactDetails(string $fullName, string $email): void
	{
		$fullName = Validator::name($fullName);
		if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
			throw new InvalidNameException('A valid email address is required.');
		}

		$this->fullName = $fullName;
		$this->email = trim($email);
	}

	public function addAccount(BankAccount $account): void
	{
		if ($account->getAccountHolder() !== $this) {
			throw new InvalidNameException('Account holder does not match customer.');
		}
		if (isset($this->accounts[$account->getAccountNumber()])) {
			throw new InvalidNameException('Customer already owns this account.');
		}

		$this->accounts[$account->getAccountNumber()] = $account;
	}

	public function getAccount(string $accountNumber): BankAccount
	{
		$accountNumber = Validator::accountNumber($accountNumber);
		if (!isset($this->accounts[$accountNumber])) {
			throw new AccountNotFoundException('Account does not belong to this customer.');
		}

		return $this->accounts[$accountNumber];
	}

	/** @return array<string, BankAccount> */
	public function getAccounts(): array { return $this->accounts; }
}