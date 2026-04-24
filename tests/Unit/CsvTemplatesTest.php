<?php

it('has client csv template with header and 100 rows', function () {
    $projectRoot = dirname(__DIR__, 2);
    $path = $projectRoot . '/public/template/clients_template.csv';
    expect(is_file($path))->toBeTrue();

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    expect($lines)->not()->toBeEmpty();

    expect($lines[0])->toBe('company_name,email,phone_number');
    // header + 100 records = 101 lines total
    expect(count($lines))->toBe(101);
});

it('has duplicate demo csv template with 4 rows and duplicates', function () {
    $projectRoot = dirname(__DIR__, 2);
    $path = $projectRoot . '/public/template/clients_template_with_duplicates.csv';
    expect(is_file($path))->toBeTrue();

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    $lines = array_values(array_filter($lines, fn ($line) => trim((string) $line) !== ''));
    expect($lines)->not()->toBeEmpty();
    expect($lines[0])->toBe('company_name,email,phone_number');

    // header + 4 records = 5 lines total
    expect(count($lines))->toBe(5);

    $emails = [];
    $phones = [];
    foreach (array_slice($lines, 1) as $line) {
        [$company, $email, $phone] = str_getcsv($line);
        $emails[] = $email;
        $phones[] = $phone;
    }

    expect(count($emails))->toBe(4);
    expect(count(array_unique($emails)))->toBe(3);   // email duplicate exists
    expect(count(array_unique($phones)))->toBeLessThan(4);   // phone duplicate exists
});

