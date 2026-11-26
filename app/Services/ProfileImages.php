<?php

namespace App\Services;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

class ProfileImages
{
    private ?S3Client $client = null;

    public function usesDirectUploads(): bool
    {
        return match (config('filesystems.profile_storage')) {
            'local' => false,
            'supabase' => true,
            default => throw new InvalidArgumentException('Unsupported profile image storage.'),
        };
    }

    public function url(string $path): string
    {
        if (! $this->usesDirectUploads()) {
            return asset('storage/'.$path);
        }

        $config = config('filesystems.disks.s3');
        $base = $config['url'] ?: preg_replace('~/s3/?$~', '/object/public/'.rawurlencode((string) $config['bucket']), (string) $config['endpoint']);

        if (! $base || $base === $config['endpoint']) {
            throw new RuntimeException('The Supabase public image URL is not configured.');
        }

        return rtrim($base, '/').'/'.implode('/', array_map(rawurlencode(...), explode('/', $path)));
    }

    public function temporaryUploadUrl(string $path, string $mime): string
    {
        $client = $this->client();
        $command = $client->getCommand('PutObject', [
            'Bucket' => $this->uploadBucket(),
            'Key' => $path,
            'ContentType' => $mime,
        ]);

        return (string) $client->createPresignedRequest($command, '+5 minutes')->getUri();
    }

    public function downloadTemporary(string $path, string $field): string
    {
        try {
            $object = $this->client()->getObject([
                'Bucket' => $this->uploadBucket(),
                'Key' => $path,
                'Range' => 'bytes=0-2097152',
            ]);
        } catch (S3Exception $exception) {
            if (in_array($exception->getStatusCode(), [404, 416], true)) {
                throw ValidationException::withMessages([$field => 'The uploaded image is missing or empty. Please select it again.']);
            }

            throw $exception;
        }

        $contents = $object['Body']->getContents();
        if (strlen($contents) > 2 * 1024 * 1024) {
            throw ValidationException::withMessages([$field => 'The image must be no larger than 2 MB.']);
        }

        return $contents;
    }

    public function deleteTemporary(string $path): void
    {
        $this->client()->deleteObject(['Bucket' => $this->uploadBucket(), 'Key' => $path]);
    }

    public function cleanupTemporary(int|string $userId): void
    {
        $client = $this->client();
        $bucket = $this->uploadBucket();
        $cutoff = now()->subHour()->timestamp;

        foreach ($client->getPaginator('ListObjectsV2', ['Bucket' => $bucket, 'Prefix' => $userId.'/']) as $page) {
            foreach ($page['Contents'] ?? [] as $object) {
                if ($object['LastModified'] instanceof DateTimeInterface && $object['LastModified']->getTimestamp() < $cutoff) {
                    $client->deleteObject(['Bucket' => $bucket, 'Key' => $object['Key']]);
                }
            }
        }
    }

    public function store(UploadedFile $file, string $directory): string
    {
        if (! $this->usesDirectUploads()) {
            $path = $file->store($directory, 'public');
            if ($path === false) {
                throw new RuntimeException('Failed to store profile image.');
            }

            return $path;
        }

        $path = $directory.'/'.Str::random(40).'.'.$file->extension();
        $this->client()->putObject([
            'Bucket' => config('filesystems.disks.s3.bucket'),
            'Key' => $path,
            'Body' => $file->getContent(),
            'ContentType' => $file->getMimeType(),
        ]);

        return $path;
    }

    public function delete(string $path): bool
    {
        if (! $this->usesDirectUploads()) {
            return Storage::disk('public')->delete($path);
        }

        $this->client()->deleteObject(['Bucket' => config('filesystems.disks.s3.bucket'), 'Key' => $path]);

        return true;
    }

    private function uploadBucket(): string
    {
        $bucket = config('filesystems.profile_upload_bucket');
        if (! is_string($bucket) || $bucket === '' || $bucket === config('filesystems.disks.s3.bucket')) {
            throw new RuntimeException('A separate private profile upload bucket is required.');
        }

        return $bucket;
    }

    private function client(): S3Client
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $config = config('filesystems.disks.s3');
        foreach (['key', 'secret', 'region', 'endpoint', 'bucket'] as $name) {
            if (empty($config[$name])) {
                throw new RuntimeException("Supabase image storage is missing config key: {$name}");
            }
        }

        return $this->client = new S3Client([
            'version' => 'latest',
            'region' => $config['region'],
            'endpoint' => $config['endpoint'],
            'use_path_style_endpoint' => true,
            'credentials' => ['key' => $config['key'], 'secret' => $config['secret']],
            'request_checksum_calculation' => 'when_required',
            'response_checksum_validation' => 'when_required',
            'http' => ['connect_timeout' => 5, 'timeout' => 30],
        ]);
    }
}
