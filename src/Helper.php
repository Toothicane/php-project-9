<?php

declare(strict_types=1);

namespace Hexlet\Code;

function truncate(?string $text, int $length = 200): string
{
    if ($text === null || mb_strlen($text) <= $length) {
        return $text ?? '';
    }

    return mb_substr($text, 0, $length - 3) . '...';
}
