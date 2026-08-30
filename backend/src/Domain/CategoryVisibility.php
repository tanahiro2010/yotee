<?php

declare(strict_types=1);

namespace App\Domain;

enum CategoryVisibility: string
{
    case Private = 'private';
    case Unlisted = 'unlisted';
    case Public = 'public';
}
