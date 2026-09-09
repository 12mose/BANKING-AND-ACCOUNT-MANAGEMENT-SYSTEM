<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Classes/Bank.php';
require_once __DIR__ . '/../src/Classes/AccountStatement.php';

$bank = new Bank();
$customer = new Customer('CUS-001', 'Ada Lovelace', 'ada@example.com');
$bank->registerCustomer($customer);
$savings = $bank->openSavingsAccount('CUS-001', 'SAV-1001', 100000);
$current = $bank->openCurrentAccount('CUS-001', 'CUR-1001', 50000);

$savings->deposit(25000, 'Initial deposit');
$savings->withdraw(10000, 'Cash withdrawal');
$bank->transfer('CUR-1001', 'SAV-1001', 5000, 'Monthly transfer');

echo (new AccountStatement($savings))->render() . PHP_EOL . PHP_EOL;

foreach ([
    static fn (): Transaction => $savings->deposit(-1),
    static fn (): Transaction => $savings->withdraw(999999),
    static fn (): array => $bank->transfer('SAV-1001', 'MISSING', 100),
] as $failure) {
    try {
        $failure();
    } catch (Throwable $exception) {
        echo 'Handled: ' . $exception::class . ' - ' . $exception->getMessage() . PHP_EOL;
    }
}

$bank->closeAccount('CUR-1001');
try {
    $current->deposit(100);
} catch (Throwable $exception) {
    echo 'Handled closed account: ' . $exception::class . PHP_EOL;
}