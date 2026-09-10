<?php

declare(strict_types=1);

namespace Hexlet\Code\Urls;

use Carbon\Carbon;

class Url
{
    private ?int $id = null;
    private string $name;
    private Carbon $createdAt;

    public static function fromArray(array $urlData): Url
    {
        ['name' => $name, 'created_at' => $createdAt] = $urlData;
        $url = new Url();
        $url->setName($name);
        $url->setCreatedAt(Carbon::parse($createdAt));
        return $url;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
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
