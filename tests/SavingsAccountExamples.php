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
require_once __DIR__ . '/../src/Classes/SavingsAccount.php';

function expectSavingsAccountFailure(callable $operation, string $exceptionClass): void
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

$customer = new Customer('CUST-SAVINGS', 'Savings Account User', 'savings@example.com');
$account = new SavingsAccount('SAVE-001', $customer);

if ($account->getBalanceInCents() !== SavingsAccount::MINIMUM_BALANCE_IN_CENTS) {
	throw new RuntimeException('Savings account default opening balance mismatch.');
}

expectSavingsAccountFailure(
	static fn (): SavingsAccount => new SavingsAccount(
		'SAVE-002',
		$customer,
		SavingsAccount::MINIMUM_BALANCE_IN_CENTS - 1,
	),
	InvalidAmountException::class,
);

$account->deposit(SavingsAccount::MAX_WITHDRAWAL_IN_CENTS + SavingsAccount::MINIMUM_BALANCE_IN_CENTS);
$balanceBeforeFailedWithdrawal = $account->getBalanceInCents();

expectSavingsAccountFailure(
	static fn (): Transaction => $account->withdraw(SavingsAccount::MAX_WITHDRAWAL_IN_CENTS + 1),
	InsufficientFundsException::class,
);

if ($account->getBalanceInCents() !== $balanceBeforeFailedWithdrawal) {
	throw new RuntimeException('Failed savings withdrawal changed the balance.');
}

$account->withdraw(SavingsAccount::MAX_WITHDRAWAL_IN_CENTS);
if ($account->getBalanceInCents() !== SavingsAccount::MINIMUM_BALANCE_IN_CENTS) {
	throw new RuntimeException('Savings withdrawal did not preserve the minimum balance.');
}

expectSavingsAccountFailure(
	static fn (): Transaction => $account->withdraw(1),
	InsufficientFundsException::class,
);

$account->close();
expectSavingsAccountFailure(
	static fn (): Transaction => $account->deposit(1),
	ClosedAccountException::class,
);

echo "Savings account examples passed\n";