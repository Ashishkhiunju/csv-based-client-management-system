<?php

namespace App\Repositories;

use App\Models\Client;
use Illuminate\Support\Facades\DB;

class EloquentClientRepository implements ClientRepository
{
    public function existsByEmailOrPhone(string $email, string $phoneNumber): bool
    {
        return Client::query()
            ->where('email', $email)
            ->orWhere('phone_number', $phoneNumber)
            ->exists();
    }

    public function insertOrIgnore(array $rows): int
    {
        return DB::table('clients')->insertOrIgnore($rows);
    }

    public function chunkForExport(int $chunkSize, callable $callback): void
    {
        Client::query()
            ->orderBy('id')
            ->chunk($chunkSize, function ($clients) use ($callback) {
                $rows = [];
                foreach ($clients as $client) {
                    $rows[] = [
                        'company_name' => (string) $client->company_name,
                        'email' => (string) $client->email,
                        'phone_number' => (string) $client->phone_number,
                    ];
                }
                $callback($rows);
            });
    }
}

