<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Exceptions/InvalidAccountNumberException.php';
require_once __DIR__ . '/../src/Exceptions/InvalidAmountException.php';
require_once __DIR__ . '/../src/Exceptions/InvalidDescriptionException.php';
require_once __DIR__ . '/../src/Exceptions/InvalidNameException.php';
require_once __DIR__ . '/../src/Validation/Validator.php';

function expectValidationFailure(callable $operation, string $exceptionClass): void
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

expectValidationFailure(
    static fn (): string => Validator::amount(0),
    InvalidAmountException::class,
);
expectValidationFailure(
    static fn (): string => Validator::accountNumber('bad account'),
    InvalidAccountNumberException::class,
);
expectValidationFailure(
    static fn (): string => Validator::description(str_repeat('x', 256)),
    InvalidDescriptionException::class,
);
expectValidationFailure(
    static fn (): string => Validator::name('   '),
    InvalidNameException::class,
);

echo "Validation failure examples passed\n";