<?php

declare(strict_types=1);

require_once __DIR__ . '/../Enums/TransactionType.php';
require_once __DIR__ . '/../Validation/Validator.php';
require_once __DIR__ . '/../Exceptions/InvalidAccountNumberException.php';

final class Transaction
{
    private string $transactionId;
    private DateTimeImmutable $occurredAt;
    private TransactionType $type;
    private int $amountInCents;
    private ?string $sourceAccountNumber;
    private ?string $destinationAccountNumber;
    private string $description;

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

        $this->transactionId = $trimmedId;
        $this->occurredAt = $occurredAt;
        $this->type = $type;
        $this->amountInCents = Validator::amount($amountInCents);
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
}
