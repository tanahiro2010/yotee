<?php

declare(strict_types=1);

namespace App\Domain;

/** PRD §58: スパム / 誤情報 / なりすまし / 不適切な内容 / その他. */
enum ReportReason: string
{
    case Spam = 'spam';
    case Misinformation = 'misinformation';
    case Impersonation = 'impersonation';
    case Inappropriate = 'inappropriate';
    case Other = 'other';
}
