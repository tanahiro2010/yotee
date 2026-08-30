<?php

declare(strict_types=1);

namespace App\Domain;

enum DevicePlatform: string
{
    case Ios = 'ios';
    case Android = 'android';
}
