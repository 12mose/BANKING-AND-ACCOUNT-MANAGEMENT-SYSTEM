<?php

declare(strict_types=1);

require_once __DIR__ . '/BankAccount.php';
require_once __DIR__ . '/../Validation/Validator.php';

final class SavingsAccount extends BankAccount
{
	// Named policy values belong to the savings account rules they control.
	public const MINIMUM_BALANCE = 50000;
	public const MAX_WITHDRAWAL = 2000000;

	public function __construct(
		string $accountNumber,
		Customer $accountHolder,
		int $openingBalanceInCents = self::MINIMUM_BALANCE,
	) {
		parent::__construct(
			Validator::accountNumber($accountNumber),
			$accountHolder,
			$openingBalanceInCents,
		);

		if ($openingBalanceInCents < self::MINIMUM_BALANCE) {
			throw new InvalidAmountException('Savings opening balance must meet the minimum balance.');
		}
	}

	public function canWithdraw(int $amountInCents): bool
	{
		Validator::amount($amountInCents);

		return $amountInCents <= self::MAX_WITHDRAWAL
			&& $this->getBalanceForPolicy() - $amountInCents >= self::MINIMUM_BALANCE;
	}
}
