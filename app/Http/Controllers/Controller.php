<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected function validateFileType(string $fileData, string $filename): ?string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($fileData);

        $allowedMimeTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain',
            'text/markdown',
        ];

        if (str_starts_with($mimeType, 'image/') || in_array($mimeType, $allowedMimeTypes)) {
            return null;
        }

        return "Invalid file type for file '{$filename}'. Allowed types: Images, PDF, DOC, DOCX, TXT, MD.";
    }
}
