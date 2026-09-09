<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Exceptions/ClosedAccountException.php';
require_once __DIR__ . '/../src/Exceptions/CustomerNotFoundException.php';
require_once __DIR__ . '/../src/Exceptions/InvalidAmountException.php';
require_once __DIR__ . '/../src/Classes/Bank.php';

function expectLifecycleFailure(callable $operation, string $exceptionClass): Throwable
{
    try {
        $operation();
    } catch (Throwable $exception) {
        if ($exception instanceof $exceptionClass) {
            return $exception;
        }

        throw new RuntimeException(sprintf('Expected %s, got %s.', $exceptionClass, $exception::class), 0, $exception);
    }

    throw new RuntimeException(sprintf('Expected %s to be thrown.', $exceptionClass));
}

$bank = new Bank();
$customer = new Customer('CUST-LIFECYCLE', 'Lifecycle User', 'lifecycle@example.com');
$bank->registerCustomer($customer);

$savings = $bank->openSavingsAccount('CUST-LIFECYCLE', SavingsAccount::MINIMUM_BALANCE);
$current = $bank->openCurrentAccount('CUST-LIFECYCLE', 10000);

if ($savings->getAccountNumber() === $current->getAccountNumber()) {
    throw new RuntimeException('Generated account numbers must be unique.');
}
if ($bank->getAccount($savings->getAccountNumber()) !== $savings) {
    throw new RuntimeException('Generated account was not registered with the bank.');
}
if ($savings->getTransactions()[0]->getType() !== TransactionType::ACCOUNT_OPENING) {
    throw new RuntimeException('Account opening was not recorded as an opening transaction.');
}
if ($bank->getAccountBalanceInCents($current->getAccountNumber()) !== 10000) {
    throw new RuntimeException('Balance inquiry returned the wrong balance.');
}
if (method_exists($current, 'setBalanceInCents')) {
    throw new RuntimeException('Accounts must not expose a public balance setter.');
}

expectLifecycleFailure(
    static fn(): SavingsAccount => $bank->openSavingsAccount('UNKNOWN', SavingsAccount::MINIMUM_BALANCE),
    CustomerNotFoundException::class,
);
expectLifecycleFailure(
    static fn(): CurrentAccount => $bank->openCurrentAccount('CUST-LIFECYCLE', -1),
    InvalidAmountException::class,
);
expectLifecycleFailure(
    static fn(): SavingsAccount => $bank->openSavingsAccount('CUST-LIFECYCLE', SavingsAccount::MINIMUM_BALANCE - 1),
    InvalidAmountException::class,
);

$current->deposit(5000, 'Closure test deposit');
$balanceBeforeClosure = $current->getBalanceInCents();
$historyBeforeClosure = count($current->getTransactions());
$bank->closeAccount($current->getAccountNumber());

if ($current->getStatus() !== AccountStatus::CLOSED) {
    throw new RuntimeException('Closed account did not report CLOSED status.');
}
if ($bank->getAccount($current->getAccountNumber()) !== $current) {
    throw new RuntimeException('Closed account was removed from the bank registry.');
}
if ($current->getBalanceInCents() !== $balanceBeforeClosure) {
    throw new RuntimeException('Closing an account changed its balance.');
}
if (count($current->getTransactions()) !== $historyBeforeClosure) {
    throw new RuntimeException('Closing an account removed transaction history.');
}
if ($current->getTransactions()[array_key_last($current->getTransactions())]->getType() !== TransactionType::ACCOUNT_CLOSURE) {
    throw new RuntimeException('Account closure was not recorded as a closure transaction.');
}

$transactionIds = array_map(
    static fn(Transaction $transaction): string => $transaction->getTransactionId(),
    $current->getTransactions(),
);
if (count($transactionIds) !== count(array_unique($transactionIds))) {
    throw new RuntimeException('Transaction IDs must be unique.');
}

$closedDepositException = expectLifecycleFailure(
    static fn(): Transaction => $current->deposit(1),
    ClosedAccountException::class,
);
if (stripos($closedDepositException->getMessage(), 'closed') === false) {
    throw new RuntimeException('Closed-account error should clearly mention closure.');
}
expectLifecycleFailure(
    static fn(): Transaction => $current->withdraw(1),
    ClosedAccountException::class,
);
expectLifecycleFailure(
    static fn(): Transaction => $current->applyTransferFee(),
    ClosedAccountException::class,
);

expectLifecycleFailure(
    static fn(): void => $bank->closeAccount($current->getAccountNumber()),
    ClosedAccountException::class,
);

echo "Account lifecycle examples passed\n";
