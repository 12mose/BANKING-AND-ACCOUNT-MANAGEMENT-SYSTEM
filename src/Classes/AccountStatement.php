<?php

declare(strict_types=1);

require_once __DIR__ . '/BankAccount.php';

final class AccountStatement
{
	public function __construct(private readonly BankAccount $account)
	{
	}

	public function getAccount(): BankAccount { return $this->account; }

	/** @return array<int, Transaction> */
	public function getTransactions(): array { return $this->account->getTransactions(); }

	public function render(): string
	{
		$lines = [
			'Account statement',
			'Account: ' . $this->account->getAccountNumber(),
			'Status: ' . $this->account->getStatus()->value,
			'Balance: ' . number_format($this->account->getBalanceInCents() / 100, 2),
			'Transactions:',
		];

		foreach ($this->getTransactions() as $transaction) {
			$lines[] = sprintf(
				'%s | %s | %.2f | %s',
				$transaction->getOccurredAt()->format('Y-m-d H:i:s'),
				$transaction->getType()->value,
				$transaction->getAmountInCents() / 100,
				$transaction->getDescription() ?: 'No description',
			);
		}

		return implode(PHP_EOL, $lines);
	}
}
