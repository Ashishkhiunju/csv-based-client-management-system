<?php

namespace App\Http\Controllers;

use App\DataTables\ClientsDataTable;
use App\Http\Requests\ImportClientsRequest;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientManagementController extends Controller
{
    public function index(Request $request, ClientsDataTable $dataTable)
    {
        if ($request->ajax()) {
            return $dataTable->ajax();
        }

        return $dataTable->render('admin.client-management.index');
    }

    public function import(ImportClientsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $path = $validated['csv_file']->getRealPath();
        if (! $path || ! is_readable($path)) {
            return back()->with('error', 'Uploaded file is not readable.');
        }

        $handle = fopen($path, 'r');
        if (! $handle) {
            return back()->with('error', 'Unable to open uploaded CSV file.');
        }
       
        $header = fgetcsv($handle);
        if (! is_array($header)) {
            fclose($handle);
            return back()->with('error', 'CSV file is empty.');
        }
        
        $normalize = fn ($v) => Str::of((string) $v)->trim()->lower()->toString();
   
        $header = array_map($normalize, $header);
       
        $companyIdx = array_search('company_name', $header, true);
        $emailIdx = array_search('email', $header, true);
        $phoneIdx = array_search('phone_number', $header, true);
        

        if ($companyIdx === false || $emailIdx === false || $phoneIdx === false) {
            fclose($handle);
            return back()->with('error', 'CSV header must contain: company_name,email,phone_number');
        }

       
        $skipped = 0;
        $rowNumber = 1;

        // We do a pre-scan to detect duplicates and allow user confirmation.
        $seenEmails = [];
        $seenPhones = [];
        $duplicates = [];

      

        // Save non-duplicate rows temporarily for "Import Non-Duplicates".
        $token = (string) Str::uuid();
        $tmpRelativePath = "tmp/client-imports/{$token}.csv";
        Storage::disk('local')->makeDirectory('tmp/client-imports');
        $tmpHandle = fopen(Storage::disk('local')->path($tmpRelativePath), 'w');
        if (! $tmpHandle) {
            fclose($handle);
            return back()->with('error', 'Unable to prepare temporary import file.');
        }

        // Write header to temp file.
        fputcsv($tmpHandle, ['company_name', 'email', 'phone_number']);

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                $company = isset($row[$companyIdx]) ? trim((string) $row[$companyIdx]) : '';
                $email = isset($row[$emailIdx]) ? trim((string) $row[$emailIdx]) : '';
                $phone = isset($row[$phoneIdx]) ? trim((string) $row[$phoneIdx]) : '';

                // Ignore completely empty lines (common at end of CSV files).
                if ($company === '' && $email === '' && $phone === '') {
                    continue;
                }

                if ($company === '' || $email === '' || $phone === '') {
                    $skipped++;
                    continue;
                }

                $duplicateReasons = [];

                $emailKey = Str::lower($email);
                $phoneKey = $phone;

                if (isset($seenEmails[$emailKey])) {
                    $duplicateReasons[] = 'email (duplicate in file)';
                }
                if (isset($seenPhones[$phoneKey])) {
                    $duplicateReasons[] = 'phone_number (duplicate in file)';
                }

                // Check duplicates in DB (fast enough per-row for confirmation step;
                // actual import will use insertOrIgnore in batches).
                $existsInDb = Client::query()
                    ->where('email', $email)
                    ->orWhere('phone_number', $phone)
                    ->exists();
                if ($existsInDb) {
                    $duplicateReasons[] = 'exists in database (email or phone)';
                }

                $seenEmails[$emailKey] = true;
                $seenPhones[$phoneKey] = true;

                if ($duplicateReasons !== []) {
                    $duplicates[] = [
                        'row' => $rowNumber,
                        'company_name' => $company,
                        'email' => $email,
                        'phone_number' => $phone,
                        'reason' => implode(', ', $duplicateReasons),
                    ];
                    continue;
                }

                // Keep non-duplicate rows for potential confirm import.
                fputcsv($tmpHandle, [$company, $email, $phone]);
            }
        } catch (\Throwable $e) {
            fclose($handle);
            fclose($tmpHandle);
            Storage::disk('local')->delete($tmpRelativePath);
            throw $e;
        }

        fclose($handle);
        fclose($tmpHandle);

        // If there are duplicates, show review screen with options.
        if (count($duplicates) > 0) {
            session([
                "client_imports.{$token}" => [
                    'tmp_path' => $tmpRelativePath,
                    'created_at' => now()->toISOString(),
                ],
            ]);

            return redirect()
                ->route('client-management.import.review', $token)
                ->with('duplicates', $duplicates);
        }

        // No duplicates -> import immediately in batches.
        $imported = $this->importFromTempCsv($tmpRelativePath);
        Storage::disk('local')->delete($tmpRelativePath);

        return redirect()
            ->route('client-management')
            ->with('success', "Import complete. Created: {$imported}. Skipped: {$skipped}.");
    }

    public function importReview(Request $request, string $token): View|RedirectResponse
    {
        $data = session("client_imports.{$token}");
        if (! is_array($data) || empty($data['tmp_path'])) {
            return redirect()->route('client-management')->with('error', 'Import session expired.');
        }

        $duplicates = session('duplicates', []);
        
        return view('admin.client-management.import-review', [
            'token' => $token,
            'duplicates' => $duplicates,
        ]);
    }

    public function importConfirm(Request $request, string $token): RedirectResponse
    {
        $data = session("client_imports.{$token}");
        if (! is_array($data) || empty($data['tmp_path'])) {
            return redirect()->route('client-management')->with('error', 'Import session expired.');
        }

        $tmpPath = $data['tmp_path'];
        $imported = $this->importFromTempCsv($tmpPath);
        Storage::disk('local')->delete($tmpPath);
        $request->session()->forget("client_imports.{$token}");

        return redirect()
            ->route('client-management')
            ->with('success', "Import complete. Created: {$imported}. Duplicates were skipped.");
    }

    public function importCancel(Request $request, string $token): RedirectResponse
    {
        $data = session("client_imports.{$token}");
        if (is_array($data) && ! empty($data['tmp_path'])) {
            Storage::disk('local')->delete($data['tmp_path']);
        }

        $request->session()->forget("client_imports.{$token}");

        return redirect()
            ->route('client-management')
            ->with('success', 'Import cancelled.');
    }

    public function exportAllCsv(): StreamedResponse
    {
        $filename = 'clients_all_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['company_name', 'email', 'phone_number']);

            Client::query()
                ->orderBy('id')
                ->chunk(2000, function ($clients) use ($out) {
                    foreach ($clients as $client) {
                        fputcsv($out, [$client->company_name, $client->email, $client->phone_number]);
                    }
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function importFromTempCsv(string $tmpRelativePath): int
    {
        $fullPath = Storage::disk('local')->path($tmpRelativePath);
        $handle = fopen($fullPath, 'r');
        if (! $handle) {
            return 0;
        }

        // Consume header.
        fgetcsv($handle);

        $batchSize = 1000;
        $batch = [];
        $now = now();
        $created = 0;

        $flush = function () use (&$batch, &$created, &$now) {
            if ($batch === []) {
                return;
            }
            $inserted = DB::table('clients')->insertOrIgnore($batch);
            $created += $inserted;
            $batch = [];
            $now = now();
        };

        while (($row = fgetcsv($handle)) !== false) {
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
        fclose($handle);

        return $created;
    }

    public function edit(Client $client): View
    {
        return view('admin.client-management.edit', compact('client'));
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:clients,email,' . $client->id],
            'phone_number' => ['required', 'string', 'max:255', 'unique:clients,phone_number,' . $client->id],
        ]);

        $client->update($validated);

        return redirect()
            ->route('client-management')
            ->with('success', 'Client updated successfully.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $client->delete();

        return redirect()
            ->route('client-management')
            ->with('success', 'Client deleted successfully.');
    }
}

