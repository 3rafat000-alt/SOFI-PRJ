<?php
namespace App\Services;

class FileValidationService
{
    private const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    private const BLOCKED_MIMES = [
        'image/svg+xml',
        'text/html',
        'application/x-php',
        'application/x-javascript',
    ];

    public function validateUpload(\Illuminate\Http\UploadedFile $file, array $additionalMimes = []): bool
    {
        $mime = $file->getMimeType();
        $allowed = array_merge(self::ALLOWED_MIMES, $additionalMimes);

        if (in_array($mime, self::BLOCKED_MIMES)) {
            return false;
        }

        if (!in_array($mime, $allowed)) {
            return false;
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $extensionMap = [
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];

        return isset($extensionMap[$extension]) && $extensionMap[$extension] === $mime;
    }

    public function sanitizeFilename(string $filename): string
    {
        $filename = basename($filename);
        $filename = preg_replace('/[^\w\.\-]/u', '_', $filename);
        return mb_substr($filename, 0, 200);
    }
}
