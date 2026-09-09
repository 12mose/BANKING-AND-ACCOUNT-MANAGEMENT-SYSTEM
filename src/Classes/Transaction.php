<?php

declare(strict_types=1);

require_once __DIR__ . '/../Enums/TransactionType.php';
require_once __DIR__ . '/../Validation/Validator.php';
require_once __DIR__ . '/../Exceptions/InvalidAccountNumberException.php';
require_once __DIR__ . '/../Exceptions/InvalidAmountException.php';

final class Transaction
{
	private readonly string $transactionId;
	private readonly DateTimeImmutable $occurredAt;
	private readonly TransactionType $type;
	private readonly int $amountInCents;
	private readonly ?string $sourceAccountNumber;
	private readonly ?string $destinationAccountNumber;
	private readonly string $description;
	/** @var array<string, true> */
	private static array $issuedTransactionIds = [];

	public function __construct(
		string $transactionId,
		DateTimeImmutable $occurredAt,
		TransactionType $type,
		int $amountInCents,
		?string $sourceAccountNumber,
		?string $destinationAccountNumber,
		string $description,
	) {
		$trimmedId = trim($transactionId);
		if ($trimmedId === '') {
			throw new InvalidArgumentException('Transaction id must not be blank.');
		}
		if (isset(self::$issuedTransactionIds[$trimmedId])) {
			throw new InvalidArgumentException('Transaction id must be unique.');
		}

		$this->transactionId = $trimmedId;
		$this->occurredAt = $occurredAt;
		$this->type = $type;
		if (in_array($type, [TransactionType::ACCOUNT_OPENING, TransactionType::ACCOUNT_CLOSURE], true)) {
			if ($amountInCents < 0) {
				throw new InvalidAmountException('Transaction amount cannot be negative.');
			}

			$this->amountInCents = $amountInCents;
		} else {
			$this->amountInCents = Validator::amount($amountInCents);
		}
		$this->sourceAccountNumber = $sourceAccountNumber !== null ? Validator::accountNumber($sourceAccountNumber) : null;
		$this->destinationAccountNumber = $destinationAccountNumber !== null ? Validator::accountNumber($destinationAccountNumber) : null;
		$this->description = Validator::description($description);

		if (
			$type === TransactionType::TRANSFER
			&& ($this->sourceAccountNumber === null || $this->destinationAccountNumber === null)
		) {
			throw new InvalidAccountNumberException(
				'Transfer transactions require both a source and destination account.',
			);
		}

		self::$issuedTransactionIds[$trimmedId] = true;
	}

	public function getTransactionId(): string
	{
		return $this->transactionId;
	}

	public function getOccurredAt(): DateTimeImmutable
	{
		return $this->occurredAt;
	}

	public function getType(): TransactionType
	{
		return $this->type;
	}

	public function getAmountInCents(): int
	{
		return $this->amountInCents;
	}

	public function getSourceAccountNumber(): ?string
	{
		return $this->sourceAccountNumber;
	}

	public function getDestinationAccountNumber(): ?string
	{
		return $this->destinationAccountNumber;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	/** @return array<string, mixed> */
	public function toArray(): array
	{
		return [
			'transactionId' => $this->transactionId,
			'occurredAt' => $this->occurredAt->format(DATE_ATOM),
			'type' => $this->type->value,
			'amountInCents' => $this->amountInCents,
			'sourceAccountNumber' => $this->sourceAccountNumber,
			'destinationAccountNumber' => $this->destinationAccountNumber,
			'description' => $this->description,
		];
	}
}
