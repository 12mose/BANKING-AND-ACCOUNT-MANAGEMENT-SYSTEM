<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Classes/Customer.php';
require_once __DIR__ . '/../src/Classes/SavingsAccount.php';
require_once __DIR__ . '/../src/Classes/CurrentAccount.php';
require_once __DIR__ . '/../src/Exceptions/InvalidAmountException.php';
require_once __DIR__ . '/../src/Exceptions/InsufficientFundsException.php';
require_once __DIR__ . '/../src/Exceptions/ClosedAccountException.php';

function assertCondition(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function expectException(callable $callback, string $expectedClass, string $message): void
{
    try {
        $callback();
        throw new RuntimeException($message);
    } catch (Throwable $exception) {
        assertCondition($exception instanceof $expectedClass, sprintf(
            'Expected %s but got %s: %s',
            $expectedClass,
            get_class($exception),
            $exception->getMessage(),
        ));
    }
}

$customer = new Customer('C-100', 'Alice Example', 'alice@example.com');
$account = new SavingsAccount('SAV-100', $customer, SavingsAccount::MINIMUM_BALANCE_IN_CENTS);

$depositAmount = 25000;
$depositTransaction = $account->deposit($depositAmount, 'salary');

assertCondition($account->getBalanceInCents() === SavingsAccount::MINIMUM_BALANCE_IN_CENTS + $depositAmount, 'Deposit should increase balance by exactly the deposit amount.');
assertCondition($depositTransaction->getType() === TransactionType::DEPOSIT, 'Deposit transaction should be recorded as a deposit.');
assertCondition($depositTransaction->getAmountInCents() === $depositAmount, 'Deposit transaction must keep the original amount.');

$withdrawAmount = 10000;
$withdrawTransaction = $account->withdraw($withdrawAmount, 'groceries');

assertCondition($account->getBalanceInCents() === SavingsAccount::MINIMUM_BALANCE_IN_CENTS + $depositAmount - $withdrawAmount, 'Withdrawal should decrease balance by exactly the withdrawal amount.');
assertCondition($withdrawTransaction->getType() === TransactionType::WITHDRAWAL, 'Withdrawal transaction should be recorded as a withdrawal.');
assertCondition(count($account->getTransactions()) === 2, 'Successful deposit and withdrawal should each create one transaction.');

$beforeFailureBalance = $account->getBalanceInCents();
$beforeFailureCount = count($account->getTransactions());
expectException(
    fn () => $account->withdraw(SavingsAccount::MAX_WITHDRAWAL_IN_CENTS, 'too large'),
    InsufficientFundsException::class,
    'Excessive withdrawals should be rejected without changing state.',
);
assertCondition($account->getBalanceInCents() === $beforeFailureBalance, 'Failed withdrawal must leave balance unchanged.');
assertCondition(count($account->getTransactions()) === $beforeFailureCount, 'Failed withdrawal must leave transaction history unchanged.');

expectException(
    fn () => $account->deposit(0, 'zero'),
    InvalidAmountException::class,
    'Zero deposit amount should be rejected.',
);
expectException(
    fn () => $account->withdraw(-1, 'negative'),
    InvalidAmountException::class,
    'Negative withdrawal amount should be rejected.',
);

$closedAccount = new SavingsAccount('SAV-200', $customer, SavingsAccount::MINIMUM_BALANCE_IN_CENTS);
$closedAccount->close();
expectException(
    fn () => $closedAccount->deposit(1000, 'closed deposit'),
    ClosedAccountException::class,
    'Closed account deposit should be rejected.',
);
expectException(
    fn () => $closedAccount->withdraw(1000, 'closed withdrawal'),
    ClosedAccountException::class,
    'Closed account withdrawal should be rejected.',
);

$currentAccount = new CurrentAccount('CUR-100', $customer, 0);
$currentAccount->deposit(5000, 'starting balance');
assertCondition($currentAccount->getBalanceInCents() === 5000, 'Current account should support valid deposits.');

$currentAccount->withdraw(5000, 'cash');
assertCondition($currentAccount->getBalanceInCents() === 0, 'Current account should allow withdrawals within balance.');

expectException(
    fn () => $currentAccount->withdraw(1, 'overdraft'),
    InsufficientFundsException::class,
    'Current account overdraft limits should be enforced.',
);

print "Issue 12 verification passed: deposit and withdrawal rules are valid.\n";
