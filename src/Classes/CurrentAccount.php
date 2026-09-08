<?php

declare(strict_types=1);

require_once __DIR__ . '/BankAccount.php';
require_once __DIR__ . '/../Validation/Validator.php';

final class CurrentAccount extends BankAccount
{
	// Named policy values belong to current-account overdraft and fee rules.
	public const OVERDRAFT_LIMIT = 0;
	public const TRANSFER_FEE = 300;

	public function __construct(
		string $accountNumber,
		Customer $accountHolder,
		int $openingBalanceInCents = 0,
	) {
		parent::__construct(
			Validator::accountNumber($accountNumber),
			$accountHolder,
			$openingBalanceInCents,
		);
	}

	public function canWithdraw(int $amountInCents): bool
	{
		Validator::amount($amountInCents);

		return $this->balanceInCents - $amountInCents >= -self::OVERDRAFT_LIMIT;
	}

	public function getTransferFeeInCents(): int
	{
		return self::TRANSFER_FEE;
	}
}
