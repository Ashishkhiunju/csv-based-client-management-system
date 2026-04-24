@extends('adminlte::page')

@section('title', 'Client Management')

@section('content_header')
    <h1>Client Management</h1>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Import Clients (CSV)</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('client-management.import') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="form-row align-items-end">
                    <div class="col-md-7">
                        <div class="form-group mb-md-0">
                            <label for="csv_file">Select CSV File</label>
                            <input type="file" name="csv_file" id="csv_file"
                                class="form-control @error('csv_file') is-invalid @enderror" accept=".csv,text/csv">
                        @error('csv_file')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="form-group mb-0 d-flex flex-wrap">
                            <button type="submit" class="btn btn-success mr-2 mb-2 mb-md-0">Import</button>
                            <a class="btn btn-outline-primary mr-2 mb-2 mb-md-0" href="{{ asset('template/clients_template.csv') }}" download>
                                Download Client CSV
                            </a>
                            <a class="btn btn-outline-warning mb-2 mb-md-0" href="{{ asset('template/clients_template_with_duplicates.csv') }}" download>
                                Download Duplicate Demo CSV
                            </a>
                        </div>
                    </div>

                    <div class="col-12">
                        <small class="form-text text-muted mt-2">Header must be: company_name,email,phone_number</small>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-end" style="gap: 8px;">
            <form
                action="{{ route('client-management.destroy.all') }}"
                method="POST"
                onsubmit="return confirm('This will delete ALL client records. Are you sure?')"
            >
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm">
                    Delete all records
                </button>
            </form>

            <a href="{{ route('client-management.export.all') }}" class="btn btn-outline-secondary btn-sm">
                Export All CSV
            </a>
        </div>
        <div class="card-body">
            {!! $dataTable->table(['class' => 'table table-bordered table-striped'], true) !!}
        </div>
    </div>
@stop

@section('plugins.Datatables', true)

@section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.6.5/css/buttons.bootstrap4.min.css">
@stop

@section('js')
    {!! $dataTable->scripts() !!}

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.6.5/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.6.5/js/buttons.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.6.5/js/buttons.html5.min.js"></script>
@stop

