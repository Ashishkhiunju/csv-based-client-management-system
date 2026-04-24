<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Http\UploadedFile;


function makeCsvUpload(array $lines): UploadedFile
{
    $content = implode("\n", $lines) . "\n";
    $tmp = tempnam(sys_get_temp_dir(), 'clients_');
    file_put_contents($tmp, $content);

    return new UploadedFile(
        $tmp,
        'clients.csv',
        'text/csv',
        null,
        true
    );
}

it('requires auth for client management page', function () {
    $this->get('/admin/client-management')->assertRedirect('/login');
});

it('shows import review when duplicates exist in database', function () {
    $user = User::factory()->create();

    // Existing client in DB → duplicates should be detected.
    Client::create([
        'company_name' => 'Existing',
        'email' => 'dup@example.com',
        'phone_number' => '8000000001',
    ]);

    $upload = makeCsvUpload([
        'company_name,email,phone_number',
        'New Company,new@example.com,8000000002',
        'Dup Company,dup@example.com,8000000003',
    ]);

    $resp = $this->actingAs($user)->post(route('client-management.import'), [
        'csv_file' => $upload,
    ]);

    $resp->assertRedirect(); // redirect to review page with token

    $reviewUrl = $resp->headers->get('Location');
    expect($reviewUrl)->toContain('/admin/client-management/import/review/');

    $this->get($reviewUrl)
        ->assertOk()
        ->assertSee('Duplicate Rows')
        ->assertSee('dup@example.com');
});

it('imports only non-duplicates after confirm', function () {
    $user = User::factory()->create();

    Client::create([
        'company_name' => 'Existing',
        'email' => 'dup@example.com',
        'phone_number' => '8000000001',
    ]);

    $upload = makeCsvUpload([
        'company_name,email,phone_number',
        'New Company,new@example.com,8000000002',
        'Dup Company,dup@example.com,8000000003',
    ]);

    $resp = $this->actingAs($user)->post(route('client-management.import'), [
        'csv_file' => $upload,
    ]);

    $reviewUrl = $resp->headers->get('Location');
    $token = basename($reviewUrl);

    $this->actingAs($user)->post(route('client-management.import.confirm', $token))
        ->assertRedirect(route('client-management'));

    expect(Client::where('email', 'new@example.com')->exists())->toBeTrue();
    // The duplicate email should still be only one record in DB.
    expect(Client::where('email', 'dup@example.com')->count())->toBe(1);
});

it('does nothing when import is cancelled', function () {
    $user = User::factory()->create();

    Client::create([
        'company_name' => 'Existing',
        'email' => 'dup@example.com',
        'phone_number' => '8000000001',
    ]);

    $upload = makeCsvUpload([
        'company_name,email,phone_number',
        'New Company,new@example.com,8000000002',
        'Dup Company,dup@example.com,8000000003',
    ]);

    $resp = $this->actingAs($user)->post(route('client-management.import'), [
        'csv_file' => $upload,
    ]);

    $reviewUrl = $resp->headers->get('Location');
    $token = basename($reviewUrl);

    $this->actingAs($user)->post(route('client-management.import.cancel', $token))
        ->assertRedirect(route('client-management'));

    expect(Client::where('email', 'new@example.com')->exists())->toBeFalse();
    expect(Client::count())->toBe(1);
});

it('exports all clients as csv', function () {
    $user = User::factory()->create();

    Client::create([
        'company_name' => 'Acme Inc',
        'email' => 'acme@example.com',
        'phone_number' => '7000000001',
    ]);

    $resp = $this->actingAs($user)->get(route('client-management.export.all'));
    //check there is two row
    $this->assertCount(1, explode("\n", $resp->getContent()));
    expect(explode("\n", $resp->getContent()))->toHaveCount(1);
    $resp->assertOk();
    $resp->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

