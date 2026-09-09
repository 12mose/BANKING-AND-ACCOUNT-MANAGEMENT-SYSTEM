<?php

declare(strict_types=1);

require_once __DIR__ . '/../Enums/TransactionType.php';
require_once __DIR__ . '/../Exceptions/InvalidAmountException.php';
require_once __DIR__ . '/../Exceptions/InvalidDescriptionException.php';

final class Transaction
{
	public function __construct(
		private readonly string $transactionId,
		private readonly DateTimeImmutable $occurredAt,
		private readonly TransactionType $type,
		private readonly int $amountInCents,
		private readonly ?string $sourceAccountNumber,
		private readonly ?string $destinationAccountNumber,
		private readonly string $description,
	) {
		if (trim($transactionId) === '' || $amountInCents <= 0) {
			throw new InvalidAmountException('Transaction ID and amount must be valid.');
		}
		if ($type === TransactionType::TRANSFER && ($sourceAccountNumber === null || $destinationAccountNumber === null)) {
			throw new InvalidDescriptionException('Transfers require source and destination accounts.');
		}
		if (strlen(trim($description)) > 255) {
			throw new InvalidDescriptionException('Description must not exceed 255 characters.');
		}
	}

	public function getTransactionId(): string { return $this->transactionId; }
	public function getOccurredAt(): DateTimeImmutable { return $this->occurredAt; }
	public function getType(): TransactionType { return $this->type; }
	public function getAmountInCents(): int { return $this->amountInCents; }
	public function getSourceAccountNumber(): ?string { return $this->sourceAccountNumber; }
	public function getDestinationAccountNumber(): ?string { return $this->destinationAccountNumber; }
	public function getDescription(): string { return $this->description; }
}
