<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Classes/CurrentAccount.php';
require_once __DIR__ . '/../src/Classes/SavingsAccount.php';
require_once __DIR__ . '/../src/Classes/Transaction.php';

function expectTransactionFailure(callable $operation, string $exceptionClass): void
{
    try {
        $operation();
    } catch (Throwable $exception) {
        if ($exception instanceof $exceptionClass) {
            return;
        }

        throw new RuntimeException(
            sprintf('Expected %s, got %s.', $exceptionClass, $exception::class),
            0,
            $exception,
        );
    }

    throw new RuntimeException(sprintf('Expected %s to be thrown.', $exceptionClass));
}

$customer = new Customer('CUST-TXN', 'Transaction User', 'transaction@example.com');
$source = new CurrentAccount('ACC-SRC', $customer, 25000);
$destination = new SavingsAccount('ACC-DST', $customer, 50000);

$transfer = new Transaction(
    'TXN-1001',
    new DateTimeImmutable('2024-01-15T12:30:00+00:00'),
    TransactionType::TRANSFER,
    5000,
    'ACC-SRC',
    'ACC-DST',
    'Transfer to savings',
);

if ($transfer->getTransactionId() !== 'TXN-1001') {
    throw new RuntimeException('Transaction ID mismatch.');
}

if ($transfer->getType() !== TransactionType::TRANSFER) {
    throw new RuntimeException('Transaction type mismatch.');
}

if ($transfer->getAmountInCents() !== 5000) {
    throw new RuntimeException('Transaction amount mismatch.');
}

if ($transfer->getSourceAccountNumber() !== 'ACC-SRC' || $transfer->getDestinationAccountNumber() !== 'ACC-DST') {
    throw new RuntimeException('Transfer account endpoints mismatch.');
}

$payload = $transfer->toArray();
if ($payload['transactionId'] !== 'TXN-1001') {
    throw new RuntimeException('Serialized ID mismatch.');
}
if ($payload['type'] !== 'transfer') {
    throw new RuntimeException('Serialized transaction type mismatch.');
}
if ($payload['occurredAt'] !== '2024-01-15T12:30:00+00:00') {
    throw new RuntimeException('Serialized timestamp mismatch.');
}
if (json_encode($transfer) === false) {
    throw new RuntimeException('Transaction should serialize to JSON without failing.');
}

$history = $source->getTransactions();
$history[] = $transfer;
if (count($source->getTransactions()) !== 0) {
    throw new RuntimeException('Transaction history should be returned as an immutable snapshot.');
}

$source->deposit(3000, 'Top-up deposit');
$entries = $source->getTransactions();
if (count($entries) !== 1 || $entries[0]->getType() !== TransactionType::DEPOSIT) {
    throw new RuntimeException('Deposit history should be recorded with the correct metadata.');
}

$reflection = new ReflectionClass(Transaction::class);
foreach (['transactionId', 'occurredAt', 'type', 'amountInCents', 'sourceAccountNumber', 'destinationAccountNumber', 'description'] as $propertyName) {
    $property = $reflection->getProperty($propertyName);
    if (!$property->isReadOnly()) {
        throw new RuntimeException(sprintf('Transaction property %s must be immutable.', $propertyName));
    }
}

expectTransactionFailure(
    static fn (): Transaction => new Transaction(
        '',
        new DateTimeImmutable('now'),
        TransactionType::DEPOSIT,
        100,
        null,
        null,
        'Missing ID',
    ),
    InvalidArgumentException::class,
);

expectTransactionFailure(
    static fn (): Transaction => new Transaction(
        'TXN-INVALID',
        new DateTimeImmutable('now'),
        TransactionType::TRANSFER,
        100,
        'ACC-SRC',
        null,
        'Invalid transfer',
    ),
    InvalidAccountNumberException::class,
);

echo "Transaction audit examples passed\n";
