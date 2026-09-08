<?php

declare(strict_types=1);

enum AccountStatus: string
{
    case ACTIVE = 'active';
    case CLOSED = 'closed';
}