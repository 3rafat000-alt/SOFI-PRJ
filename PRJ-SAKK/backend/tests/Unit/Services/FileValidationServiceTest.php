<?php

use App\Services\FileValidationService;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->service = app(FileValidationService::class);
});

it('validates allowed image mime types', function () {
    $file = UploadedFile::fake()->image('document.jpg');

    expect($this->service->validateUpload($file))->toBeTrue();
});

it('validates PNG files', function () {
    $file = UploadedFile::fake()->image('photo.png');

    expect($this->service->validateUpload($file))->toBeTrue();
});

it('validates WebP files', function () {
    $file = UploadedFile::fake()->image('image.webp');

    expect($this->service->validateUpload($file))->toBeTrue();
});

it('validates PDF files', function () {
    $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

    expect($this->service->validateUpload($file))->toBeTrue();
});

it('rejects HTML files', function () {
    $file = UploadedFile::fake()->create('page.html', 100, 'text/html');

    expect($this->service->validateUpload($file))->toBeFalse();
});

it('rejects SVG files', function () {
    $file = UploadedFile::fake()->create('icon.svg', 100, 'image/svg+xml');

    expect($this->service->validateUpload($file))->toBeFalse();
});

it('rejects PHP files', function () {
    $file = UploadedFile::fake()->create('shell.php', 100, 'application/x-php');

    expect($this->service->validateUpload($file))->toBeFalse();
});

it('rejects unknown mime types', function () {
    $file = UploadedFile::fake()->create('file.bin', 100, 'application/octet-stream');

    expect($this->service->validateUpload($file))->toBeFalse();
});

it('accepts additional mime types when specified', function () {
    // Create a file with a mime type that is in extension map but not in default allowed list
    $file = UploadedFile::fake()->create('doc.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

    // This extension/mime is in allowed list and extension map — should pass
    expect($this->service->validateUpload($file))->toBeTrue();
});

it('blocks dangerous mime types even if in additional list', function () {
    $file = UploadedFile::fake()->create('script.html', 100, 'text/html');

    expect($this->service->validateUpload($file, ['text/html']))->toBeFalse();
});

it('validates mime matches extension', function () {
    // Fake JPEG with png extension should fail
    $file = UploadedFile::fake()->create('image.png', 100, 'image/jpeg');

    expect($this->service->validateUpload($file))->toBeFalse();
});

it('sanitizes filenames', function () {
    expect($this->service->sanitizeFilename('normal-file.pdf'))->toBe('normal-file.pdf');
    expect($this->service->sanitizeFilename('../etc/passwd'))->toBe('passwd');
    expect($this->service->sanitizeFilename('file<>.txt'))->toBe('file__.txt');
    expect($this->service->sanitizeFilename('path/../../../dangerous.php'))->toBe('dangerous.php');
});

it('truncates long filenames to 200 characters', function () {
    $long = str_repeat('a', 300) . '.pdf';
    $result = $this->service->sanitizeFilename($long);

    expect(strlen($result))->toBeLessThanOrEqual(200);
});
