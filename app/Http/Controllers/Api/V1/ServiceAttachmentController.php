<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ServiceAttachmentController extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:jpeg,png,jpg,pdf,doc,docx'], // 10MB max
        ]);

        $file = $request->file('file');
        $user = auth('user_api')->user();
        
        $mimeType = $file->getMimeType();
        $isImage = str_starts_with($mimeType, 'image/');
        $diskName = $isImage ? 'r2_images' : 'r2_documents';

        $safeName = Str::uuid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
        $path = sprintf('consultations/services_attachments/%s', $user->id ?? 'guest');

        $storedPath = Storage::disk($diskName)->putFileAs($path, $file, $safeName);

        if (!$storedPath) {
            return response()->json(['message' => 'Failed to upload file'], 500);
        }

        $url = Storage::disk($diskName)->temporaryUrl($storedPath, now()->addMinutes(60));

        return response()->json([
            'message' => 'File uploaded successfully',
            'path'    => $storedPath,
            'url'     => $url,
        ]);
    }
}
