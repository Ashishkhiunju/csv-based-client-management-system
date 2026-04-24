<?php

namespace App\Services;

use App\Repositories\ClientRepository;
use RuntimeException;

class ClientCsvService
{
    public function __construct(
        private readonly ClientRepository $clients,
    ) {}

    /**
     * Prepare import by scanning CSV and writing non-duplicates to a temp CSV.
     *
     * @return array{token:string,tmp_path:string,duplicates:array<int,array<string,string|int>>,skipped:int}
     */
    public function prepareImport(string $sourceCsvPath, string $token, string $tmpDirAbsolutePath): array
    {
        if (!is_readable($sourceCsvPath)) {
            throw new RuntimeException('Uploaded file is not readable.');
        }

        if (!is_dir($tmpDirAbsolutePath) && !mkdir($tmpDirAbsolutePath, 0775, true) && !is_dir($tmpDirAbsolutePath)) {
            throw new RuntimeException('Unable to prepare temporary import directory.');
        }

        $in = fopen($sourceCsvPath, 'r');
        if (!$in) {
            throw new RuntimeException('Unable to open uploaded CSV file.');
        }

        $header = fgetcsv($in);
        if (!is_array($header)) {
            fclose($in);
            throw new RuntimeException('CSV file is empty.');
        }

        [$companyIdx, $emailIdx, $phoneIdx] = $this->resolveHeaderIndexes($header);

        $tmpPath = rtrim($tmpDirAbsolutePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $token . '.csv';
        $out = fopen($tmpPath, 'w');
        if (!$out) {
            fclose($in);
            throw new RuntimeException('Unable to prepare temporary import file.');
        }

        fputcsv($out, ['company_name', 'email', 'phone_number']);

        $duplicates = [];
        $skipped = 0;
        $rowNumber = 1; // header is row 1

        $seenEmails = [];
        $seenPhones = [];

        while (($row = fgetcsv($in)) !== false) {
            $rowNumber++;

            $company = isset($row[$companyIdx]) ? trim((string) $row[$companyIdx]) : '';
            $email = isset($row[$emailIdx]) ? trim((string) $row[$emailIdx]) : '';
            $phone = isset($row[$phoneIdx]) ? trim((string) $row[$phoneIdx]) : '';

            if ($company === '' && $email === '' && $phone === '') {
                continue;
            }

            if ($company === '' || $email === '' || $phone === '') {
                $skipped++;
                continue;
            }

            $reasons = [];

            $emailKey = strtolower($email);
            if (isset($seenEmails[$emailKey])) {
                $reasons[] = 'email (duplicate in file)';
            }
            if (isset($seenPhones[$phone])) {
                $reasons[] = 'phone_number (duplicate in file)';
            }

            if ($this->clients->existsByEmailOrPhone($email, $phone)) {
                $reasons[] = 'exists in database (email or phone)';
            }

            $seenEmails[$emailKey] = true;
            $seenPhones[$phone] = true;

            if ($reasons !== []) {
                $duplicates[] = [
                    'row' => $rowNumber,
                    'company_name' => $company,
                    'email' => $email,
                    'phone_number' => $phone,
                    'reason' => implode(', ', $reasons),
                ];
                continue;
            }

            fputcsv($out, [$company, $email, $phone]);
        }

        fclose($in);
        fclose($out);

        return [
            'token' => $token,
            'tmp_path' => $tmpPath,
            'duplicates' => $duplicates,
            'skipped' => $skipped,
        ];
    }

    public function cancelImport(string $tmpPath): void
    {
        if ($tmpPath !== '' && is_file($tmpPath)) {
            @unlink($tmpPath);
        }
    }

    public function confirmImport(string $tmpPath, int $batchSize = 1000): int
    {
        if (!is_readable($tmpPath)) {
            return 0;
        }

        $in = fopen($tmpPath, 'r');
        if (!$in) {
            return 0;
        }

        // Consume header.
        fgetcsv($in);

        $created = 0;
        $batch = [];
        $now = date('Y-m-d H:i:s');

        $flush = function () use (&$batch, &$created, &$now) {
            if ($batch === []) {
                return;
            }
            $inserted = $this->clients->insertOrIgnore($batch);
            $created += $inserted;
            $batch = [];
            $now = date('Y-m-d H:i:s');
        };

        while (($row = fgetcsv($in)) !== false) {
            $company = isset($row[0]) ? trim((string) $row[0]) : '';
            $email = isset($row[1]) ? trim((string) $row[1]) : '';
            $phone = isset($row[2]) ? trim((string) $row[2]) : '';

            if ($company === '' && $email === '' && $phone === '') {
                continue;
            }

            if ($company === '' || $email === '' || $phone === '') {
                continue;
            }

            $batch[] = [
                'company_name' => $company,
                'email' => $email,
                'phone_number' => $phone,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= $batchSize) {
                $flush();
            }
        }

        $flush();
        fclose($in);

        return $created;
    }

    /**
     * @param resource $out
     */
    public function exportAllToCsvStream($out): void
    {
        fputcsv($out, ['company_name', 'email', 'phone_number']);

        $this->clients->chunkForExport(2000, function (array $rows) use ($out) {
            foreach ($rows as $row) {
                fputcsv($out, [$row['company_name'], $row['email'], $row['phone_number']]);
            }
        });
    }

    /**
     * @param array<int, mixed> $header
     * @return array{int,int,int}
     */
    private function resolveHeaderIndexes(array $header): array
    {
        $normalize = array_map(function ($v) {
            return strtolower(trim((string) $v));
        }, $header);

        $companyIdx = array_search('company_name', $normalize, true);
        $emailIdx = array_search('email', $normalize, true);
        $phoneIdx = array_search('phone_number', $normalize, true);

        if ($companyIdx === false || $emailIdx === false || $phoneIdx === false) {
            throw new RuntimeException('CSV header must contain: company_name,email,phone_number');
        }

        return [$companyIdx, $emailIdx, $phoneIdx];
    }
}

