<?php

declare(strict_types=1);

require_once __DIR__ . '/BankAccount.php';
require_once __DIR__ . '/../Validation/Validator.php';

final class SavingsAccount extends BankAccount
{
	// Named policy values belong to the savings account rules they control.
	public const MINIMUM_BALANCE_IN_CENTS = 50000;
	public const MAX_WITHDRAWAL_IN_CENTS = 2000000;
	public const MINIMUM_BALANCE = self::MINIMUM_BALANCE_IN_CENTS;
	public const MAX_WITHDRAWAL = self::MAX_WITHDRAWAL_IN_CENTS;

	public function __construct(
		string $accountNumber,
		Customer $accountHolder,
		int $openingBalanceInCents = self::MINIMUM_BALANCE_IN_CENTS,
	) {
		parent::__construct(
			Validator::accountNumber($accountNumber),
			$accountHolder,
			$openingBalanceInCents,
		);

		if ($openingBalanceInCents < self::MINIMUM_BALANCE_IN_CENTS) {
			throw new InvalidAmountException('Savings opening balance must meet the minimum balance.');
		}
	}

	protected function canWithdraw(int $amountInCents): bool
	{
		Validator::amount($amountInCents);

		return $amountInCents <= self::MAX_WITHDRAWAL_IN_CENTS
			&& $this->balanceInCents - $amountInCents >= self::MINIMUM_BALANCE_IN_CENTS;
	}
}
