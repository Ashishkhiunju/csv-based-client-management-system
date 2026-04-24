@extends('adminlte::auth.login')

@section('auth_body')
    @parent

    <div class="alert alert-info mt-3 mb-0">
        <strong>Demo login</strong><br>
        Username: <strong>Administrator@admin.com</strong><br>
        Password: <strong>administrator</strong>
        <p class="my-0">
            <a href="{{ route('readme') }}">Open README</a>
        </p>
    </div>
@stop

