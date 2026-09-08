<?php

declare(strict_types=1);

enum TransactionType: string
{
    case DEPOSIT = 'deposit';
    case WITHDRAWAL = 'withdrawal';
    case TRANSFER = 'transfer';
    case FEE = 'fee';
}