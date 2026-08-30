<?php

declare(strict_types=1);

namespace App\Domain;

enum AuthProvider: string
{
    case Google = 'google';
    case Apple = 'apple';
}
