<?php

declare(strict_types=1);

require_once __DIR__ . '/../Validation/Validator.php';
require_once __DIR__ . '/../Exceptions/InvalidCustomerIdException.php';
require_once __DIR__ . '/../Exceptions/InvalidEmailException.php';
require_once __DIR__ . '/../Exceptions/DuplicateAccountException.php';
require_once __DIR__ . '/../Exceptions/AccountNotFoundException.php';

final class Customer
{
	private string $customerId;
	private string $fullName;
	private string $email;
	/** @var array<string, BankAccount> */
	private array $accounts = [];

	public function __construct(string $customerId, string $fullName, string $email)
	{
		$normalizedId = trim($customerId);
		if ($normalizedId === '') {
			throw new InvalidCustomerIdException('Customer id must not be blank.');
		}

		$this->customerId = $normalizedId;
		$this->fullName = Validator::name($fullName);
		$this->email = $this->validateEmail($email);
	}

	public function getCustomerId(): string
	{
		return $this->customerId;
	}

	public function getFullName(): string
	{
		return $this->fullName;
	}

	public function getEmail(): string
	{
		return $this->email;
	}

	public function updateContactDetails(string $fullName, string $email): void
	{
		$validatedName = Validator::name($fullName);
		$validatedEmail = $this->validateEmail($email);

		$this->fullName = $validatedName;
		$this->email = $validatedEmail;
	}

	public function addAccount(BankAccount $account): void
	{
		$accountNumber = $account->getAccountNumber();

		if (isset($this->accounts[$accountNumber])) {
			throw new DuplicateAccountException('Customer already owns this account.');
		}

		// Ensure the account's declared holder is this customer.
		if ($account->getAccountHolder() !== $this) {
			throw new DuplicateAccountException('Account belongs to a different customer.');
		}

		$this->accounts[$accountNumber] = $account;
	}

	/**
	 * Returns owned account by account number or throws AccountNotFoundException.
	 */
	public function getAccount(string $accountNumber): BankAccount
	{
		$normalized = Validator::accountNumber($accountNumber);

		if (!isset($this->accounts[$normalized])) {
			throw new AccountNotFoundException('Account not found for this customer.');
		}

		return $this->accounts[$normalized];
	}

	/**
	 * Returns a copy of the account map keyed by account number.
	 * Callers cannot directly mutate ownership by changing the returned array.
	 * @return array<string, BankAccount>
	 */
	public function getAccounts(): array
	{
		return $this->accounts;
	}

	private function validateEmail(string $email): string
	{
		$normalized = trim($email);

		if ($normalized === '' || filter_var($normalized, FILTER_VALIDATE_EMAIL) === false) {
			throw new InvalidEmailException('Invalid email address.');
		}

		return $normalized;
	}
}

?>