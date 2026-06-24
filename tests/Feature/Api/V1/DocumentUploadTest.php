<?php

use App\Models\MedicalDocument;
use App\Models\Patient;
use App\Models\PatientAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function docCreateAuthUser(): User
{
    return User::create([
        'full_name'     => 'Dr. DocTest',
        'email'         => 'drdoctest@test.com',
        'password_hash' => bcrypt('password'),
        'role'          => 'DOCTOR',
        'is_active'     => true,
        'phone'         => '+580009876543',
    ]);
}

function docSeedDocument(User $user, string $uuid, array $overrides = []): MedicalDocument
{
    $account = PatientAccount::create([
        'email'         => 'patdoctest@patient.test',
        'password_hash' => bcrypt('password'),
        'full_name'     => 'Doc Patient',
        'phone'         => '+580001111111',
    ]);

    $patient = Patient::create([
        'user_id'            => $user->id,
        'patient_account_id' => $account->id,
        'first_name'         => 'Doc',
        'last_name'          => 'Patient',
        'birth_date'         => '1990-01-01',
    ]);

    return MedicalDocument::create(array_merge([
        'uuid'           => $uuid,
        'user_id'        => $user->id,
        'patient_id'     => $patient->id,
        'type'           => 'REPORT',
        'content'        => 'Test document',
        'public_token'   => 'pub_' . uniqid(),
        'pending_upload' => true,
    ], $overrides));
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

test('upload requires authentication', function () {
    $response = $this->postJson('/api/v1/documents/upload', [
        'uuid' => 'some-uuid',
    ]);

    $response->assertStatus(401);
});

test('upload with valid file and uuid updates pending_upload to false', function () {
    Storage::fake('local');

    $user = docCreateAuthUser();
    $uuid = 'b0000001-0000-4000-8000-000000000001';

    docSeedDocument($user, $uuid);

    $file = UploadedFile::fake()->create('report.pdf', 512, 'application/pdf');

    $response = $this->actingAs($user, 'user_api')
        ->postJson('/api/v1/documents/upload', [
            'uuid' => $uuid,
            'file' => $file,
        ]);

    $response->assertOk()
        ->assertJsonPath('message', 'File uploaded successfully')
        ->assertJsonPath('uuid', $uuid);

    $document = MedicalDocument::where('uuid', $uuid)->first();
    expect($document->pending_upload)->toBeFalse()
        ->and($document->file_path)->not->toBeNull()
        ->and($document->file_type)->toBe('application/pdf')
        ->and($document->file_size)->toBe(524288); // 512 KB

    Storage::disk('local')->assertExists($document->file_path);
});

test('upload stores file in correct path structure', function () {
    Storage::fake('local');

    $user = docCreateAuthUser();
    $uuid = 'b0000001-0000-4000-8000-000000000002';

    docSeedDocument($user, $uuid);

    $file = UploadedFile::fake()->image('photo.jpg');

    $response = $this->actingAs($user, 'user_api')
        ->postJson('/api/v1/documents/upload', [
            'uuid' => $uuid,
            'file' => $file,
        ]);

    $response->assertOk();

    $document = MedicalDocument::where('uuid', $uuid)->first();
    $expectedPrefix = "medical_documents/{$user->id}/{$uuid}/";
    expect($document->file_path)->toStartWith($expectedPrefix)
        ->and($document->file_path)->toEndWith('.jpg');

    Storage::disk('local')->assertExists($document->file_path);
});

test('upload fails with invalid file type', function () {
    Storage::fake('local');

    $user = docCreateAuthUser();
    $uuid = 'b0000001-0000-4000-8000-000000000003';

    docSeedDocument($user, $uuid);

    $file = UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload');

    $response = $this->actingAs($user, 'user_api')
        ->postJson('/api/v1/documents/upload', [
            'uuid' => $uuid,
            'file' => $file,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['file']);
});

test('upload fails with non-existent uuid', function () {
    Storage::fake('local');

    $user = docCreateAuthUser();
    $file = UploadedFile::fake()->create('orphan.pdf', 100, 'application/pdf');

    $response = $this->actingAs($user, 'user_api')
        ->postJson('/api/v1/documents/upload', [
            'uuid' => 'nonexistent-uuid-000000000000',
            'file' => $file,
        ]);

    $response->assertStatus(404)
        ->assertJsonPath('message', 'Document not found');
});

test('upload fails when document belongs to another user', function () {
    Storage::fake('local');

    $owner = docCreateAuthUser();
    $uuid  = 'b0000001-0000-4000-8000-000000000004';
    docSeedDocument($owner, $uuid);

    $attacker = User::create([
        'full_name'     => 'Dr. Attacker',
        'email'         => 'attacker@test.com',
        'password_hash' => bcrypt('password'),
        'role'          => 'DOCTOR',
        'is_active'     => true,
        'phone'         => '+580008888888',
    ]);

    $file = UploadedFile::fake()->create('stolen.pdf', 100, 'application/pdf');

    $response = $this->actingAs($attacker, 'user_api')
        ->postJson('/api/v1/documents/upload', [
            'uuid' => $uuid,
            'file' => $file,
        ]);

    $response->assertStatus(403)
        ->assertJsonPath('message', 'Forbidden');

    $document = MedicalDocument::where('uuid', $uuid)->first();
    expect($document->pending_upload)->toBeTrue();
});

test('upload sanitizes filename', function () {
    Storage::fake('local');

    $user = docCreateAuthUser();
    $uuid = 'b0000001-0000-4000-8000-000000000005';

    docSeedDocument($user, $uuid);

    $file = UploadedFile::fake()->create('my report (1).pdf', 100, 'application/pdf');

    $response = $this->actingAs($user, 'user_api')
        ->postJson('/api/v1/documents/upload', [
            'uuid' => $uuid,
            'file' => $file,
        ]);

    $response->assertOk();

    $document = MedicalDocument::where('uuid', $uuid)->first();
    expect($document->file_path)->toContain('my_report__1_.pdf');
});

test('upload accepts valid image types', function (string $extension, string $mime) {
    Storage::fake('local');

    $user = docCreateAuthUser();
    $uuid = 'b0000001-0000-4000-8000-000000000006';

    docSeedDocument($user, $uuid);

    $file = UploadedFile::fake()->create("image.{$extension}", 100, $mime);

    $response = $this->actingAs($user, 'user_api')
        ->postJson('/api/v1/documents/upload', [
            'uuid' => $uuid,
            'file' => $file,
        ]);

    $response->assertOk();
})->with([
    ['jpg', 'image/jpeg'],
    ['jpeg', 'image/jpeg'],
    ['png', 'image/png'],
    ['pdf', 'application/pdf'],
]);

test('upload rejects file exceeding max size', function () {
    Storage::fake('local');

    $user = docCreateAuthUser();
    $uuid = 'b0000001-0000-4000-8000-000000000007';

    docSeedDocument($user, $uuid);

    // 10MB + 1KB (10241 KB > 10240 KB limit)
    $file = UploadedFile::fake()->create('huge.pdf', 10241, 'application/pdf');

    $response = $this->actingAs($user, 'user_api')
        ->postJson('/api/v1/documents/upload', [
            'uuid' => $uuid,
            'file' => $file,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['file']);
});
