@extends('adminlte::page')

@section('title', 'Import Review')

@section('content_header')
    <h1>Import Review</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <p>
                Duplicate rows were found. You can import only the non-duplicate rows or cancel.
            </p>

            <form action="{{ route('client-management.import.confirm', $token) }}" method="POST" style="display:inline">
                @csrf
                <button type="submit" class="btn btn-success">Import Non-Duplicates</button>
            </form>

            <form action="{{ route('client-management.import.cancel', $token) }}" method="POST" style="display:inline">
                @csrf
                <button type="submit" class="btn btn-secondary">Cancel</button>
            </form>
        </div>
    </div>

    <div class="card collapsed-card" id="duplicate-card">
        <div class="card-header" id="duplicate-card-header" style="cursor: pointer;">
            <h3 class="card-title">Duplicate Rows (click to expand)</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            @if (empty($duplicates))
                <p>No duplicate rows data available.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Row #</th>
                                <th>Company Name</th>
                                <th>Email</th>
                                <th>Phone Number</th>
                                <th>Duplicate Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($duplicates as $dup)
                                <tr>
                                    <td>{{ $dup['row'] ?? '' }}</td>
                                    <td>{{ $dup['company_name'] ?? '' }}</td>
                                    <td>{{ $dup['email'] ?? '' }}</td>
                                    <td>{{ $dup['phone_number'] ?? '' }}</td>
                                    <td>{{ $dup['reason'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@stop

@section('js')
    <script>
        $(function () {
            $('#duplicate-card-header').on('click', function (e) {
                if ($(e.target).closest('[data-card-widget="collapse"], button, a, input, select, textarea, label').length) {
                    return;
                }

                $('#duplicate-card').find('[data-card-widget="collapse"]').trigger('click');
            });
        });
    </script>
@stop

