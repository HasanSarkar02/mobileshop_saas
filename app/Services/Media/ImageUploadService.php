<?php
namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;

class ImageUploadService
{
    protected ImageManager $manager;
    protected string $disk = 'public';

    public function __construct()
    {
        // Intervention Image v4 driver setup
        $this->manager = ImageManager::usingDriver(Driver::class);
        $this->disk = config('filesystems.default', 'public');
    }

    /**
     * Resize, compress (JPEG 80%), and store an uploaded image.
     */
    public function store(UploadedFile $file, string $directory): string
    {
        $filename = Str::uuid() . '.jpg';
        $path = trim($directory, '/') . '/' . $filename;

        // Decode uploaded file
        $image = $this->manager->decode($file->getRealPath());

        // Scale down to max 1200px width while preserving aspect ratio
        $image->scaleDown(width: 1200);

        // Encode to JPEG @ 80% quality using Intervention v4 Format enum
        $encodedImage = $image->encodeUsingFormat(Format::JPEG, quality: 80);

        // Store raw binary string into Laravel Storage
        Storage::disk($this->disk)->put($path, (string) $encodedImage);

        return $path;
    }

    /**
     * Replace an existing image file and delete the old one.
     */
    public function replace(UploadedFile $file, ?string $oldPath, string $directory): string
    {
        if ($oldPath) {
            $this->delete($oldPath);
        }

        return $this->store($file, $directory);
    }

    /**
     * Delete an image from storage.
     */
    public function delete(?string $path): void
    {
        if ($path && Storage::disk($this->disk)->exists($path)) {
            Storage::disk($this->disk)->delete($path);
        }
    }
}