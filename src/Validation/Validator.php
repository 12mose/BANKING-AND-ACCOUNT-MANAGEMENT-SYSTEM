<?php

declare(strict_types=1);

require_once __DIR__ . '/../Exceptions/InvalidAccountNumberException.php';
require_once __DIR__ . '/../Exceptions/InvalidAmountException.php';
require_once __DIR__ . '/../Exceptions/InvalidDescriptionException.php';
require_once __DIR__ . '/../Exceptions/InvalidNameException.php';

final class Validator
{
    private const ACCOUNT_NUMBER_PATTERN = '/^[A-Z0-9][A-Z0-9-]{3,19}$/';
    private const MAX_DESCRIPTION_LENGTH = 255;

    private function __construct()
    {
    }

    public static function name(string $name): string
    {
        $normalizedName = trim($name);

        if ($normalizedName === '') {
            throw new InvalidNameException('Name must not be blank.');
        }

        return $normalizedName;
    }

    public static function accountNumber(string $accountNumber): string
    {
        $normalizedAccountNumber = strtoupper(trim($accountNumber));

        if (preg_match(self::ACCOUNT_NUMBER_PATTERN, $normalizedAccountNumber) !== 1) {
            throw new InvalidAccountNumberException(
                'Account number must contain 4 to 20 uppercase letters, numbers, or hyphens.',
            );
        }

        return $normalizedAccountNumber;
    }

    public static function description(string $description): string
    {
        $normalizedDescription = trim($description);

        if (strlen($normalizedDescription) > self::MAX_DESCRIPTION_LENGTH) {
            throw new InvalidDescriptionException('Description must not exceed 255 characters.');
        }

        return $normalizedDescription;
    }

    public static function amount(int $amountInCents): int
    {
        if ($amountInCents <= 0) {
            throw new InvalidAmountException('Amount must be greater than zero cents.');
        }

        return $amountInCents;
    }
}