<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Classes/Customer.php';
require_once __DIR__ . '/../src/Classes/SavingsAccount.php';
require_once __DIR__ . '/../src/Classes/AccountStatement.php';

$customer = new Customer('CUST-STATEMENT', 'Statement User', 'statement@example.com');
$account = new SavingsAccount('SAVE-STATEMENT', $customer, 50000);
$account->deposit(1500, 'Deposit for statement');
$account->withdraw(500, 'Monthly withdrawal');

$statement = new AccountStatement($account);

if ($statement->getAccount() !== $account) {
    throw new RuntimeException('Statement should reference the same account instance.');
}

$transactions = $statement->getTransactions();
if (count($transactions) !== 2) {
    throw new RuntimeException('Statement should include the account transaction history.');
}

$rendered = $statement->render();
if (strpos($rendered, 'SAVE-STATEMENT') === false) {
    throw new RuntimeException('Rendered statement should include the account number.');
}
if (strpos($rendered, 'active') === false) {
    throw new RuntimeException('Rendered statement should include the account status.');
}
if (strpos($rendered, 'Deposit for statement') === false || strpos($rendered, 'Monthly withdrawal') === false) {
    throw new RuntimeException('Rendered statement should include transaction descriptions.');
}

$transactions[] = new Transaction(
    'TXN-STATEMENT',
    new DateTimeImmutable('now'),
    TransactionType::DEPOSIT,
    999,
    null,
    'SAVE-STATEMENT',
    'Attempted mutation',
);
if (count($statement->getTransactions()) !== 2) {
    throw new RuntimeException('Statement transaction snapshots must be read-only from the caller side.');
}

echo "Statement examples passed\n";
