<?php

use App\Repositories\ClientRepository;
use App\Services\ClientCsvService;

class FakeClientRepository implements ClientRepository
{
    /** @var array<string,bool> */
    public array $existingEmails = [];
    /** @var array<string,bool> */
    public array $existingPhones = [];

    /** @var array<int,array<string,mixed>> */
    public array $inserted = [];

    public function existsByEmailOrPhone(string $email, string $phoneNumber): bool
    {
        return isset($this->existingEmails[strtolower($email)]) || isset($this->existingPhones[$phoneNumber]);
    }

    public function insertOrIgnore(array $rows): int
    {
        $count = 0;
        foreach ($rows as $row) {
            $email = strtolower((string) $row['email']);
            $phone = (string) $row['phone_number'];
            if (isset($this->existingEmails[$email]) || isset($this->existingPhones[$phone])) {
                continue;
            }
            $this->existingEmails[$email] = true;
            $this->existingPhones[$phone] = true;
            $this->inserted[] = $row;
            $count++;
        }
        return $count;
    }

    public function chunkForExport(int $chunkSize, callable $callback): void
    {
        $rows = [];
        foreach ($this->inserted as $row) {
            $rows[] = [
                'company_name' => (string) $row['company_name'],
                'email' => (string) $row['email'],
                'phone_number' => (string) $row['phone_number'],
            ];
        }
        $callback($rows);
    }
}

it('prepares import and reports duplicates in file and db', function () {
    $repo = new FakeClientRepository();
    $repo->existingEmails['dbdup@example.com'] = true;

    $svc = new ClientCsvService($repo);

    $tmpDir = sys_get_temp_dir() . '/client-import-test';
    if (!is_dir($tmpDir)) { 
        mkdir($tmpDir, 0775, true);
     }

    $source = tempnam(sys_get_temp_dir(), 'src_clients_');
    file_put_contents($source, implode("\n", [
        'company_name,email,phone_number',
        'A,a@example.com,9000000001',
        'B,a@example.com,9000000002',          // dup email in file
        'C,dbdup@example.com,9000000003',      // dup in db
        'D,d@example.com,9000000004',
    ]) . "\n");

    $token = 'token123';
    $result = $svc->prepareImport($source, $token, $tmpDir);
    
    expect($result['duplicates'])->toHaveCount(2);
    expect(is_file($result['tmp_path']))->toBeTrue();

    // Confirm should insert only non-duplicates (A and D).
    $created = $svc->confirmImport($result['tmp_path'], 2);
    expect($created)->toBe(2);
   

    $svc->cancelImport($result['tmp_path']);
});

