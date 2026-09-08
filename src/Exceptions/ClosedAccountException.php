<?php

declare(strict_types=1);

require_once __DIR__ . '/DomainException.php';

class ClosedAccountException extends BankingDomainException
{
}