<?php

declare(strict_types=1);

namespace Hexlet\Code\Urls;

use PDO;
use Carbon\Carbon;

class UrlRepository
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function getAll(): array
    {
        $urls = [];
        $sql = "SELECT * FROM urls
            ORDER BY created_at DESC
        ";
        $stmt = $this->conn->query($sql);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $url = Url::fromArray($row);
            $url->setId($row['id']);
            $urls[] = $url;
        }

        return $urls;
    }

    public function getAllWithLastCheck(): array
    {
        $sql = "SELECT
                urls.id,
                urls.name,
                urls.created_at,
                last_check.created_at AS last_check,
                last_check.status_code AS last_status_code
            FROM urls
            LEFT JOIN (
                SELECT DISTINCT ON (url_id)
                    url_id,
                    created_at,
                    status_code
                FROM url_checks
                ORDER BY url_id, created_at DESC, id DESC
            ) AS last_check ON last_check.url_id = urls.id
            ORDER BY urls.created_at DESC
        ";

        $stmt = $this->conn->query($sql);

        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $url = Url::fromArray($row);
            $url->setId($row['id']);

            $lastCheck = ($row['last_check'] !== null)
                ? Carbon::parse($row['last_check'])
                : null;

            $lastCode = ($row['last_status_code'] !== null)
                ? (int) $row['last_status_code']
                : null;


            $result[] = new UrlWithLastCheck($url, $lastCheck, $lastCode);
        }

        return $result;
    }

    public function save(Url $url): void
    {
        if ($url->exists()) {
            $this->update($url);
        } else {
            $this->create($url);
        }
    }

    public function create(Url $url): void
    {
        $sql = "INSERT INTO urls (name, created_at)
            VALUES (:name, :createdAt)
        ";
        $stmt = $this->conn->prepare($sql);
        $name = $url->getName();
        $createdAt = $url->getCreatedAt()->toDateTimeString();
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':createdAt', $createdAt);
        $stmt->execute();
        $id = (int) $this->conn->lastInsertId();
        $url->setId($id);
    }

    public function update(Url $url): void
    {
        $sql = "UPDATE urls SET
            name = :name, created_at = :createdAt
            WHERE id = :id
        ";
        $stmt = $this->conn->prepare($sql);
        $name = $url->getName();
        $createdAt = $url->getCreatedAt()->toDateTimeString();
        $id = $url->getId();
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':createdAt', $createdAt);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    }

    public function find(int $id): ?Url
    {
        $sql = "SELECT * FROM urls
            WHERE id = :id
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $url = Url::fromArray($row);
            $url->setId($row['id']);
            return $url;
        }

        return null;
    }

    public function findByName(string $name): ?Url
    {
        $sql = "SELECT * FROM urls
            WHERE name = :name
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':name', $name);
        $stmt->execute();
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $url = Url::fromArray($row);
            $url->setId($row['id']);
            return $url;
        }

        return null;
    }

    public function delete(int $id): void
    {
        $sql = "DELETE FROM urls
            WHERE id = :id
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    }
}
