<?php

declare(strict_types=1);

namespace Hexlet\Code\Urls;

use Carbon\Carbon;

class UrlWithLastCheck
{
    private Url $url;
    private ?Carbon $lastCheck;

    private ?int $lastStatusCode;

    public function __construct(Url $url, ?Carbon $lastCheck, ?int $lastStatusCode)
    {
        $this->url = $url;
        $this->lastCheck = $lastCheck;
        $this->lastStatusCode = $lastStatusCode;
    }

    public function getUrl(): Url
    {
        return $this->url;
    }

    public function getLastCheck(): ?Carbon
    {
        return $this->lastCheck;
    }

    public function getLastStatusCode(): ?int
    {
        return $this->lastStatusCode;
    }
}
