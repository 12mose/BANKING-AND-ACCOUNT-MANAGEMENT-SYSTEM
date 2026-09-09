<?php

declare(strict_types=1);

enum TransactionType: string
{
    case ACCOUNT_OPENING = 'account_opening';
    case ACCOUNT_CLOSURE = 'account_closure';
    case DEPOSIT = 'deposit';
    case WITHDRAWAL = 'withdrawal';
    case TRANSFER = 'transfer';
    case FEE = 'fee';
}