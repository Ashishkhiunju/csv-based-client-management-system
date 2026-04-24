<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\Process\Process;

class TestRunnerController extends Controller
{
    public function index(): View
    {
        return view('admin.test');
    }

    public function runFeature(Request $request): RedirectResponse
    {
        $this->ensureAllowed($request);

        $process = $this->runCommand([
            PHP_BINARY,
            base_path('vendor/bin/pest'),
            base_path('tests/Feature/ClientManagementTest.php'),
        ], $this->testingEnvOverrides());

        return back()->with([
            'feature_test_exit' => $process->getExitCode(),
            'feature_test_output' => $this->formatProcessOutput($process),
        ]);
    }

    public function runUnit(Request $request): RedirectResponse
    {
        $this->ensureAllowed($request);

        $process = $this->runCommand([
            PHP_BINARY,
            base_path('vendor/bin/pest'),
            base_path('tests/Unit/ClientCsvServiceTest.php'),
            base_path('tests/Unit/CsvTemplatesTest.php'),
            base_path('tests/Unit/ImportClientsRequestTest.php'),
        ], $this->testingEnvOverrides());

        return back()->with([
            'unit_test_exit' => $process->getExitCode(),
            'unit_test_output' => $this->formatProcessOutput($process),
        ]);
    }

    private function ensureAllowed(Request $request): void
    {
        if (app()->environment('local')) {
            return;
        }

        $token = (string) env('TEST_RUNNER_TOKEN', '');
        if ($token === '') {
            abort(403, 'Test runner is not enabled.');
        }

        $provided = (string) $request->input('test_runner_token', '');
        if (! hash_equals($token, $provided)) {
            abort(403, 'Invalid test runner token.');
        }

        $allowedIps = (string) env('TEST_RUNNER_ALLOWED_IPS', '');
        if ($allowedIps !== '') {
            $ips = array_values(array_filter(array_map('trim', explode(',', $allowedIps))));
            if ($ips !== [] && ! in_array((string) $request->ip(), $ips, true)) {
                abort(403, 'IP not allowed for test runner.');
            }
        }
    }

    /**
     * @param array<int,string> $command
     * @param array<string,string> $extraEnv
     */
    private function runCommand(array $command, array $extraEnv = []): Process
    {
        if (! defined('STDOUT')) {
            define('STDOUT', fopen('php://stdout', 'w'));
        }
        if (! defined('STDERR')) {
            define('STDERR', fopen('php://stderr', 'w'));
        }

        $tmpDir = storage_path('app/process-tmp');
        if (! is_dir($tmpDir)) {
            @mkdir($tmpDir, 0777, true);
        }

        $env = array_merge($_ENV, $_SERVER, [
            // Symfony Process on Windows writes temp output files to sys temp.
            // Some setups point that to C:\WINDOWS which is not writable.
            'TMP' => $tmpDir,
            'TEMP' => $tmpDir,
        ], $extraEnv);

        $process = new Process($command, base_path(), $env);
        $process->setTimeout(300);
        $process->run();

        // Don't throw; we still want to show output in UI.
        return $process;
    }

    /**
     * Force a DB that doesn't depend on MySQL running.
     *
     * @return array<string,string>
     */
    private function testingEnvOverrides(): array
    {
        $sqlitePath = storage_path('app/testing.sqlite');
        if (! file_exists($sqlitePath)) {
            @mkdir(dirname($sqlitePath), 0777, true);
            @file_put_contents($sqlitePath, '');
        }

        return [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => $sqlitePath,
            'CACHE_STORE' => 'array',
            'SESSION_DRIVER' => 'array',
            'QUEUE_CONNECTION' => 'sync',
            'MAIL_MAILER' => 'array',
        ];
    }

    private function formatProcessOutput(Process $process): string
    {
        $out = trim($process->getOutput());
        $err = trim($process->getErrorOutput());

        if ($err !== '') {
            return $out === '' ? $err : ($out . "\n\n" . $err);
        }

        return $out;
    }
}

