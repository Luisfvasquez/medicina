<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class StorageController extends Controller
{
    /**
     * Handle generic file uploads.
     * Expects a request with a 'files' array of file uploads.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'file|max:10240', // max 10MB per file
        ]);

        $urls = [];
        $user = auth('user_api')->user();
        $prefix = $user ? $user->id : 'guest';

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                // Generate a safe unique name
                $extension = $file->getClientOriginalExtension();
                $filename = Str::uuid() . '.' . $extension;
                
                // Store in the 'r2_documents' disk so it can be accessed via signed URLs later
                $path = $file->storeAs("uploads/documents/{$prefix}", $filename, 'r2_documents');
                
                // Return the internal path to the frontend
                $urls[] = $path;
            }
        }

        return response()->json(['urls' => $urls]);
    }
}
