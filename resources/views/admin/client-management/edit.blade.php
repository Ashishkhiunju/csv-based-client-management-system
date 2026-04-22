@extends('adminlte::page')

@section('title', 'Edit Client')

@section('content_header')
    <h1>Edit Client</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('client-management.update', $client) }}">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="company_name">Company Name</label>
                    <input id="company_name" type="text" name="company_name"
                        class="form-control @error('company_name') is-invalid @enderror"
                        value="{{ old('company_name', $client->company_name) }}" required>
                    @error('company_name')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email"
                        class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email', $client->email) }}" required>
                    @error('email')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="phone_number">Phone Number</label>
                    <input id="phone_number" type="text" name="phone_number"
                        class="form-control @error('phone_number') is-invalid @enderror"
                        value="{{ old('phone_number', $client->phone_number) }}" required>
                    @error('phone_number')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <a href="{{ route('client-management') }}" class="btn btn-secondary">Back</a>
                </div>
            </form>
        </div>
    </div>
@stop

