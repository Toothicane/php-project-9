<?php

declare(strict_types=1);

namespace Hexlet\Code\UrlChecks;

use Carbon\Carbon;

class UrlCheck
{
    private ?int $id = null;
    private int $urlId;
    private int $statusCode;
    private Carbon $createdAt;

    public static function fromArray(array $checkData): UrlCheck
    {
        ['url_id' => $urlId, 'status_code' => $statusCode, 'created_at' => $createdAt] = $checkData;

        $check = new UrlCheck();
        $check->setUrlId($urlId);
        $check->setStatusCode($statusCode);
        $check->setCreatedAt(Carbon::parse($createdAt));

        return $check;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrlId(): int
    {
        return $this->urlId;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setUrlId(int $urlId): void
    {
        $this->urlId = $urlId;
    }

    public function setStatusCode(int $statusCode): void
    {
        $this->statusCode = $statusCode;
    }

    public function setCreatedAt(Carbon $time): void
    {
        $this->createdAt = $time;
    }

    public function exists(): bool
    {
        return !is_null($this->getId());
    }
}
