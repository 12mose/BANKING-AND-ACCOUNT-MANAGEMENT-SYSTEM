<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Exceptions/InvalidCustomerIdException.php';
require_once __DIR__ . '/../src/Exceptions/InvalidEmailException.php';
require_once __DIR__ . '/../src/Exceptions/DuplicateAccountException.php';
require_once __DIR__ . '/../src/Exceptions/AccountNotFoundException.php';
require_once __DIR__ . '/../src/Exceptions/InvalidNameException.php';
require_once __DIR__ . '/../src/Classes/Customer.php';
require_once __DIR__ . '/../src/Classes/CurrentAccount.php';

function expectFailure(callable $op, string $exceptionClass): void
{
    try {
        $op();
    } catch (Throwable $e) {
        if ($e instanceof $exceptionClass) {
            return;
        }
        throw new RuntimeException(sprintf('Expected %s, got %s.', $exceptionClass, $e::class));
    }

    throw new RuntimeException(sprintf('Expected %s to be thrown.', $exceptionClass));
}

// 1. Creating a valid Customer.
$customer = new Customer('CUST-1', 'Alice Example', 'alice@example.com');
if ($customer->getCustomerId() !== 'CUST-1') {
    throw new RuntimeException('Customer id mismatch.');
}

// 2. Rejecting invalid customer ID.
expectFailure(static fn() => new Customer('', 'Bob', 'bob@example.com'), InvalidCustomerIdException::class);

// 3. Rejecting empty/invalid customer name.
expectFailure(static fn() => new Customer('CUST-2', '   ', 'bob@example.com'), InvalidNameException::class);

// 4. Rejecting invalid contact information.
expectFailure(static fn() => new Customer('CUST-3', 'Carol', 'not-an-email'), InvalidEmailException::class);

// 5. Adding a valid account to a customer.
$acct = new CurrentAccount('ACCT-1', $customer, 10000);
$customer->addAccount($acct);

// 6. Finding an owned account.
$found = $customer->getAccount('ACCT-1');
if ($found->getAccountNumber() !== 'ACCT-1') {
    throw new RuntimeException('Found account number mismatch.');
}

// 7. Listing the customer's accounts.
$accounts = $customer->getAccounts();
if (!isset($accounts['ACCT-1'])) {
    throw new RuntimeException('Account listing missing ACCT-1.');
}

// 8. Preventing the same account from being added twice.
expectFailure(static fn() => $customer->addAccount($acct), DuplicateAccountException::class);

// 9. Attempting to find an account that the customer does not own.
expectFailure(static fn() => $customer->getAccount('NON-EXIST'), AccountNotFoundException::class);

// 10. Demonstrating that internal account state cannot be directly modified externally.
$copied = $customer->getAccounts();
$copied['NEW'] = 'mutated';
if (isset($customer->getAccounts()['NEW'])) {
    throw new RuntimeException('Internal accounts array was mutated through returned copy.');
}

// 11. Confirming that Customer properties remain properly encapsulated.
// Use reflection to assert the `customerId` property is private.
$ref = new ReflectionClass($customer);
$prop = $ref->getProperty('customerId');
if (!$prop->isPrivate()) {
    throw new RuntimeException('customerId property must be private.');
}

echo "Customer examples passed\n";
