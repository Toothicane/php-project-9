<?php

declare(strict_types=1);

namespace Hexlet\Code\Urls;

use Carbon\Carbon;

class UrlWithLastCheck
{
    private Url $url;
    private ?Carbon $lastCheck;

    public function __construct(Url $url, ?Carbon $lastCheck)
    {
        $this->url = $url;
        $this->lastCheck = $lastCheck;
    }

    public function getUrl(): Url
    {
        return $this->url;
    }

    public function getLastCheck(): ?Carbon
    {
        return $this->lastCheck;
    }
}
