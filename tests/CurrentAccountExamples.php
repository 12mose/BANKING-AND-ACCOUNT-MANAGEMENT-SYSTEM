<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Exceptions/InvalidCustomerIdException.php';
require_once __DIR__ . '/../src/Exceptions/InvalidEmailException.php';
require_once __DIR__ . '/../src/Exceptions/InvalidNameException.php';
require_once __DIR__ . '/../src/Exceptions/InvalidAmountException.php';
require_once __DIR__ . '/../src/Exceptions/InvalidAccountNumberException.php';
require_once __DIR__ . '/../src/Exceptions/ClosedAccountException.php';
require_once __DIR__ . '/../src/Exceptions/InsufficientFundsException.php';
require_once __DIR__ . '/../src/Classes/Customer.php';
require_once __DIR__ . '/../src/Classes/CurrentAccount.php';

function expectCurrentAccountFailure(callable $operation, string $exceptionClass): void
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

$customer = new Customer('CUST-CURRENT', 'Current Account User', 'current@example.com');
$account = new CurrentAccount('CURR-001', $customer, 10000);

if ($account->getBalanceInCents() !== 10000) {
    throw new RuntimeException('Current account opening balance mismatch.');
}

$feeTransaction = $account->applyTransferFee();
if ($feeTransaction->getType() !== TransactionType::FEE) {
    throw new RuntimeException('Transfer fee must be recorded as a fee transaction.');
}
if ($feeTransaction->getAmountInCents() !== CurrentAccount::TRANSFER_FEE_IN_CENTS) {
    throw new RuntimeException('Transfer fee amount mismatch.');
}
if ($account->getBalanceInCents() !== 10000 - CurrentAccount::TRANSFER_FEE_IN_CENTS) {
    throw new RuntimeException('Transfer fee was not deducted from the balance.');
}

// Zero is the overdraft boundary: an exact-balance withdrawal succeeds.
$account->withdraw(10000 - CurrentAccount::TRANSFER_FEE_IN_CENTS, 'Withdraw entire balance');
if ($account->getBalanceInCents() !== 0) {
    throw new RuntimeException('Current account should allow a zero balance.');
}

expectCurrentAccountFailure(
    static fn(): Transaction => $account->withdraw(1),
    InsufficientFundsException::class,
);

if ($account->getTransferFeeInCents() !== CurrentAccount::TRANSFER_FEE_IN_CENTS) {
    throw new RuntimeException('Current account transfer fee constant mismatch.');
}

expectCurrentAccountFailure(
    static fn(): Transaction => $account->applyTransferFee(),
    InsufficientFundsException::class,
);

expectCurrentAccountFailure(
    static fn(): Transaction => $account->deposit(-1),
    InvalidAmountException::class,
);

$account->close();
expectCurrentAccountFailure(
    static fn(): Transaction => $account->deposit(100),
    ClosedAccountException::class,
);

echo "Current account examples passed\n";