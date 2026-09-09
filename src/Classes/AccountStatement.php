<?php

declare(strict_types=1);

require_once __DIR__ . '/BankAccount.php';

final class AccountStatement
{
	private BankAccount $account;

	public function __construct(BankAccount $account)
	{
		$this->account = $account;
	}

	public function getAccount(): BankAccount
	{
		return $this->account;
	}

	/** @return array<int, Transaction> */
	public function getTransactions(): array
	{
		return [...$this->account->getTransactions()];
	}

	public function render(): string
	{
		$transactions = $this->getTransactions();

		$lines = [
			'Bank Account Statement',
			'Account Number: ' . $this->account->getAccountNumber(),
			'Status: ' . $this->account->getStatus()->value,
			'Balance: ' . $this->account->getBalanceInCents() . ' cents',
			'Transactions:',
		];

		if ($transactions === []) {
			$lines[] = 'No transactions recorded.';
			return implode(PHP_EOL, $lines);
		}

		foreach ($transactions as $transaction) {
			$source = $transaction->getSourceAccountNumber() ?? 'n/a';
			$destination = $transaction->getDestinationAccountNumber() ?? 'n/a';

			$lines[] = sprintf(
				'- %s | %s | %s | %d cents | source=%s | destination=%s | %s',
				$transaction->getOccurredAt()->format(DATE_ATOM),
				$transaction->getType()->value,
				$transaction->getDescription(),
				$transaction->getAmountInCents(),
				$source,
				$destination,
				$transaction->getTransactionId(),
			);
		}

		return implode(PHP_EOL, $lines);
	}
}
