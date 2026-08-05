<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait HandlesImageUploads
{
    /**
     * Decodes a base64 image and saves it to the public disk.
     *
     * @param string $base64Image
     * @param string $folder
     * @return string|null The public URL of the stored file, or null on failure.
     */
    protected function uploadBase64Image(string $base64Image, string $folder = 'avatars'): ?string
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
            $data = substr($base64Image, strpos($base64Image, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, gif, webp

            if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png', 'webp'])) {
                return null;
            }

            $data = base64_decode($data);
            if ($data === false) {
                return null;
            }

            $fileName = Str::uuid() . '.' . $type;
            $path = $folder . '/' . $fileName;

            // Force r2_images disk for image uploads
            $diskName = 'r2_images';
            Storage::disk($diskName)->put($path, $data);

            return $path;
        }

        return null;
    }
}
