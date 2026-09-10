<?php

declare(strict_types=1);

namespace Hexlet\Code\UrlChecks;

use PDO;

class UrlCheckRepository
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function create(UrlCheck $check): void
    {
        $sql = "INSERT INTO url_checks (url_id, status_code, created_at)
            VALUES (:urlId, :statusCode, :createdAt)
        ";

        $stmt = $this->conn->prepare($sql);

        $urlId = $check->getUrlId();
        $statusCode = $check->getStatusCode();
        $createdAt = $check->getCreatedAt()->toDateTimeString();

        $stmt->bindParam(':urlId', $urlId);
        $stmt->bindParam(':statusCode', $statusCode);
        $stmt->bindParam(':createdAt', $createdAt);

        $stmt->execute();

        $id = (int) $this->conn->lastInsertId();
        $check->setId($id);
    }

    public function getByUrlId(int $urlId): array
    {
        $checks = [];

        $sql = "SELECT * FROM url_checks
            WHERE url_id = :urlId
            ORDER BY created_at DESC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':urlId', $urlId);
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $check = UrlCheck::fromArray($row);
            $check->setId($row['id']);
            $checks[] = $check;
        }

        return $checks;
    }
}
