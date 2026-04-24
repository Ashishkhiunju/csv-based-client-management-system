<?php

namespace App\Repositories;

interface ClientRepository
{
    public function existsByEmailOrPhone(string $email, string $phoneNumber): bool;

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    public function insertOrIgnore(array $rows): int;

    /**
     * @param callable(array<int, array<string, string>>): void $callback
     */
    public function chunkForExport(int $chunkSize, callable $callback): void;
}

