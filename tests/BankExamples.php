<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Exceptions/AccountNotFoundException.php';
require_once __DIR__ . '/../src/Exceptions/CustomerNotFoundException.php';
require_once __DIR__ . '/../src/Exceptions/DuplicateAccountException.php';
require_once __DIR__ . '/../src/Exceptions/DuplicateCustomerException.php';
require_once __DIR__ . '/../src/Exceptions/InvalidAccountNumberException.php';
require_once __DIR__ . '/../src/Classes/Bank.php';

function expectBankFailure(callable $operation, string $exceptionClass): void
{
	try {
		$operation();
	} catch (Throwable $exception) {
		if ($exception instanceof $exceptionClass) {
			return;
		}

		throw new RuntimeException(sprintf('Expected %s, got %s.', $exceptionClass, $exception::class));
	}

	throw new RuntimeException(sprintf('Expected %s to be thrown.', $exceptionClass));
}

$bank = new Bank();
$customer = new Customer('CUST-1', 'Alice Example', 'alice@example.com');
$bank->registerCustomer($customer);

if ($bank->getCustomer('CUST-1') !== $customer) {
	throw new RuntimeException('Customer retrieval mismatch.');
}

expectBankFailure(
	static function () use ($bank): void {
		$bank->registerCustomer(new Customer('CUST-1', 'Other', 'other@example.com'));
	},
	DuplicateCustomerException::class,
);

$account = $bank->openCurrentAccount('CUST-1', 'acct-1', 10000);
if ($bank->getAccount('ACCT-1') !== $account || $customer->getAccount('ACCT-1') !== $account) {
	throw new RuntimeException('Account retrieval mismatch.');
}

$registeredAccount = new CurrentAccount('ACCT-2', $customer, 20000);
$bank->registerAccount($registeredAccount);
if ($bank->getAccount('ACCT-2') !== $registeredAccount) {
	throw new RuntimeException('Direct account registration mismatch.');
}

expectBankFailure(
	static function () use ($bank): void {
		$bank->openCurrentAccount('CUST-1', 'ACCT-1', 10000);
	},
	DuplicateAccountException::class,
);
expectBankFailure(
	static fn (): BankAccount => $bank->getAccount('bad account'),
	InvalidAccountNumberException::class,
);
expectBankFailure(
	static fn (): BankAccount => $bank->getAccount('ACCT-999'),
	AccountNotFoundException::class,
);

$customers = $bank->getCustomers();
$customers['CUST-2'] = new Customer('CUST-2', 'Mutated', 'mutated@example.com');
if (isset($bank->getCustomers()['CUST-2'])) {
	throw new RuntimeException('Internal customer collection was mutated.');
}

$accounts = $bank->getAccounts();
$accounts['ACCT-3'] = $account;
if (isset($bank->getAccounts()['ACCT-3'])) {
	throw new RuntimeException('Internal account collection was mutated.');
}

$generated = $bank->generateAccountNumber();

$nextGenerated = $bank->generateAccountNumber();
if ($generated === $nextGenerated || preg_match('/^[A-Z0-9][A-Z0-9-]{3,19}$/', $generated) !== 1) {
	throw new RuntimeException('Generated account number is invalid.');
}

echo "Bank examples passed\n";