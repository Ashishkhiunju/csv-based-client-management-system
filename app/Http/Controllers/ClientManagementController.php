<?php

namespace App\Http\Controllers;

use App\DataTables\ClientsDataTable;
use App\Http\Requests\ImportClientsRequest;
use App\Models\Client;
use App\Services\ClientCsvService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
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

        $token = (string) Str::uuid();
        $tmpDir = storage_path('app/tmp/client-imports');

        try {
            $result = app(ClientCsvService::class)->prepareImport($path, $token, $tmpDir);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        session([
            "client_imports.{$token}" => [
                'tmp_path' => $result['tmp_path'], // absolute path
                'created_at' => now()->toISOString(),
            ],
        ]);

        if (count($result['duplicates']) > 0) {
            return redirect()
                ->route('client-management.import.review', $token)
                ->with('duplicates', $result['duplicates']);
        }

        $imported = app(ClientCsvService::class)->confirmImport($result['tmp_path']);
        app(ClientCsvService::class)->cancelImport($result['tmp_path']);

        return redirect()
            ->route('client-management')
            ->with('success', "Import complete. Created: {$imported}. Skipped: {$result['skipped']}.");
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

        $tmpPath = (string) $data['tmp_path'];
        $imported = app(ClientCsvService::class)->confirmImport($tmpPath);
        app(ClientCsvService::class)->cancelImport($tmpPath);
        $request->session()->forget("client_imports.{$token}");

        return redirect()
            ->route('client-management')
            ->with('success', "Import complete. Created: {$imported}. Duplicates were skipped.");
    }

    public function importCancel(Request $request, string $token): RedirectResponse
    {
        $data = session("client_imports.{$token}");
        if (is_array($data) && ! empty($data['tmp_path'])) {
            app(ClientCsvService::class)->cancelImport((string) $data['tmp_path']);
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
            app(ClientCsvService::class)->exportAllToCsvStream($out);
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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

    public function destroyAll(Request $request): RedirectResponse
    {
        $deleted = Client::query()->delete();

        return redirect()
            ->route('client-management')
            ->with('success', "Deleted {$deleted} client record(s).");
    }
}

