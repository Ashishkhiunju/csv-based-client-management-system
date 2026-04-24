

@extends('adminlte::page')

@section('title', 'Edit Client')

@section('content_header')
    <h1>Tests</h1>
@stop

@section('content')
    @if (!app()->environment('local'))
        <div class="alert alert-warning">
            Web test runner is protected by a token.
            Set TEST_RUNNER_TOKEN:9ccd77a53a7f9186bc925f9e7f9f413fe18d655127b597e0834e20ee905c4644
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Run tests</h3>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2" style="gap: 10px;">
                <form method="POST" action="{{ route('admin.test.runFeature') }}">
                    @csrf
                    @if (!app()->environment('local'))
                        <div class="mb-2" style="min-width: 280px;">
                            <label class="form-label text-muted mb-1" for="test_runner_token_feature" style="font-size: 0.9rem;">
                                Test runner token
                            </label>
                            <input
                                id="test_runner_token_feature"
                                name="test_runner_token"
                                type="password"
                                class="form-control form-control-sm"
                                placeholder="TEST_RUNNER_TOKEN"
                                required
                            />
                        </div>
                    @endif
                    <button type="submit" class="btn btn-success">
                        Run Feature Test
                    </button>
                    <div class="text-muted mt-1" style="font-size: 0.9rem;">
                        tests/Feature/ClientManagementTest.php
                    </div>
                </form>

                <form method="POST" action="{{ route('admin.test.runUnit') }}">
                    @csrf
                    @if (!app()->environment('local'))
                        <div class="mb-2" style="min-width: 280px;">
                            <label class="form-label text-muted mb-1" for="test_runner_token_unit" style="font-size: 0.9rem;">
                                Test runner token
                            </label>
                            <input
                                id="test_runner_token_unit"
                                name="test_runner_token"
                                type="password"
                                class="form-control form-control-sm"
                                placeholder="TEST_RUNNER_TOKEN"
                                required
                            />
                        </div>
                    @endif
                    <button type="submit" class="btn btn-success">
                        Run Unit Tests
                    </button>
                    <div class="text-muted mt-1" style="font-size: 0.9rem;">
                        ClientCsvServiceTest, CsvTemplatesTest, ImportClientsRequestTest
                    </div>
                </form>
            </div>

            @if (session()->has('feature_test_output'))
                <hr>
                <h5 class="mb-2">
                    Feature test result
                    <small class="text-muted">(exit: {{ session('feature_test_exit') }})</small>
                </h5>
                <pre class="p-3 bg-dark text-light" style="white-space: pre-wrap; border-radius: 6px;">{{ session('feature_test_output') }}</pre>
            @endif

            @if (session()->has('unit_test_output'))
                <hr>
                <h5 class="mb-2">
                    Unit tests result
                    <small class="text-muted">(exit: {{ session('unit_test_exit') }})</small>
                </h5>
                <pre class="p-3 bg-dark text-light" style="white-space: pre-wrap; border-radius: 6px;">{{ session('unit_test_output') }}</pre>
            @endif
        </div>
    </div>
@stop