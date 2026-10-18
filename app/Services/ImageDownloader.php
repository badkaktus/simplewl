<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Network\SafeHttpClient;
use App\Services\Network\SafeHttpResponse;
use Illuminate\Support\Facades\Storage;

/**
 * Downloads user-provided images and stores only real image files.
 */
class ImageDownloader
{
    public const MAX_SIZE_BYTES = 5 * 1024 * 1024;

    private const EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    public function __construct(private readonly SafeHttpClient $httpClient) {}

    /**
     * @return string|null path of the stored file on the public disk
     */
    public function download(string $url, string $directory): ?string
    {
        $response = $this->httpClient->get($url, self::MAX_SIZE_BYTES);
        if (! $response instanceof SafeHttpResponse) {
            return null;
        }

        $body = $response->body;

        // The content is checked instead of the Content-Type header, so only real images are stored
        $imageInfo = @getimagesizefromstring($body);
        $extension = self::EXTENSIONS[$imageInfo['mime'] ?? ''] ?? null;
        if ($extension === null) {
            return null;
        }

        $filename = $directory.'/'.uniqid('image_', true).'.'.$extension;
        Storage::disk('public')->put($filename, $body);

        return $filename;
    }
}
