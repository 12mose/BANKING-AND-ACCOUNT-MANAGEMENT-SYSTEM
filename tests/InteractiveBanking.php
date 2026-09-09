<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Classes/Bank.php';
require_once __DIR__ . '/../src/Classes/AccountStatement.php';

$bank = new Bank();
$customer = new Customer('CLI-001', 'Terminal Customer', 'terminal@example.com');
$bank->registerCustomer($customer);
$bank->openSavingsAccount('CLI-001', 'SAV-CLI1', SavingsAccount::MINIMUM_BALANCE);
$bank->openCurrentAccount('CLI-001', 'CUR-CLI1', 0);

function prompt(string $message): string
{
    echo $message;
    return trim((string) fgets(STDIN));
}

function amountInCents(string $message): int
{
    return (int) round(((float) prompt($message)) * 100);
}

echo "Banking and Account Management System" . PHP_EOL;
echo "Demo accounts: SAV-CLI1 and CUR-CLI1" . PHP_EOL;

while (true) {
    echo PHP_EOL . "1. Balance" . PHP_EOL;
    echo "2. Deposit" . PHP_EOL;
    echo "3. Withdraw" . PHP_EOL;
    echo "4. Transfer" . PHP_EOL;
    echo "5. Statement" . PHP_EOL;
    echo "6. Close account" . PHP_EOL;
    echo "0. Exit" . PHP_EOL;

    $choice = prompt("Choose an option: ");

    try {
        if ($choice === '0') {
            echo "Goodbye." . PHP_EOL;
            break;
        }

        if ($choice === '1') {
            $account = $bank->getAccount(prompt("Account number: "));
            echo sprintf("Balance: %.2f (%s)", $account->getBalanceInCents() / 100, $account->getStatus()->value) . PHP_EOL;
        } elseif ($choice === '2') {
            $account = $bank->getAccount(prompt("Account number: "));
            $account->deposit(amountInCents("Amount: "), prompt("Description: "));
            echo "Deposit completed." . PHP_EOL;
        } elseif ($choice === '3') {
            $account = $bank->getAccount(prompt("Account number: "));
            $account->withdraw(amountInCents("Amount: "), prompt("Description: "));
            echo "Withdrawal completed." . PHP_EOL;
        } elseif ($choice === '4') {
            $bank->transfer(
                prompt("Source account: "),
                prompt("Destination account: "),
                amountInCents("Amount: "),
                prompt("Description: "),
            );
            echo "Transfer completed." . PHP_EOL;
        } elseif ($choice === '5') {
            $account = $bank->getAccount(prompt("Account number: "));
            echo (new AccountStatement($account))->render() . PHP_EOL;
        } elseif ($choice === '6') {
            $bank->closeAccount(prompt("Account number: "));
            echo "Account closed." . PHP_EOL;
        } else {
            echo "Choose a listed option." . PHP_EOL;
        }
    } catch (Throwable $exception) {
        echo "Error: " . $exception->getMessage() . PHP_EOL;
    }
}