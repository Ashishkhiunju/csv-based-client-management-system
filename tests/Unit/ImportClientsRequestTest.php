<?php

use App\Http\Requests\ImportClientsRequest;

it('has expected csv_file validation rules', function () {
    $request = new ImportClientsRequest();
    $rules = $request->rules();

    expect($rules)->toHaveKey('csv_file');
    expect($rules['csv_file'])->toBe([
        'required',
        'file',
        'mimes:csv,txt',
        'max:5120',
    ]);
});

