<?php

namespace Omnics\FileManagement\Http\Controllers\FileManager\General;

use Illuminate\Routing\Controller;
use Aws\S3\S3Client;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class S3FileManagementController extends Controller
{
    /**
     * Method allow to get the file path and expiry time
     * @param $name
     * @return array
     */
    public function getFilePathFromHetzner($name, $resource = null): array
    {
        try {
            if ($resource != null) {
                $uri = config('filesystems.disks.hetznerPublic.endpoint').'/'.config('filesystems.disks.hetznerPublic.bucket').'/'.$name;
                $expires_at = null;
            } else {
                $expiry = now()->addDay();

                //adapter '[getAdapter()]' driver is not working in laravel latest versions,so we need to add client details like this.
                $client = new S3Client([
                    'version' => 'latest',
                    'region' => env('HETZNER_REGION', 'eu-central'),
                    'endpoint' => env('HETZNER_ENDPOINT'),
                    'use_path_style_endpoint' => true,
                    'credentials' => [
                        'key' => env('HETZNER_ACCESS_KEY'),
                        'secret' => env('HETZNER_SECRET_KEY'),
                    ],
                ]);

                $command = $client->getCommand('GetObject', [
                    'Bucket' => env('HETZNER_PRIVATE_BUCKET'),
                    'Key' => $name,
                ]);

                $request = $client->createPresignedRequest($command, $expiry);
                $uri = (string) $request->getUri();
                $expires_at = $expiry->toDateTimeString();
            }

            return  [
                'path' => $uri,
                'expires_at' => $expires_at,
            ];
        } catch (\Exception $exception) {
            return [
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ];
        }
    }//End Function

    /**
     * Method allow to extract the filename from the existing file of s3
     * @param string $url
     * @param string $bucket_type
     */
    public function extractS3KeyFromUrl(string $url, string $bucket_type): ?string
    {
        $parts = parse_url($url);

        if (! isset($parts['path'])) {
            return null;
        }

        // Example path: /s3-bk-desk-genie-stage/Profile-Photos/microsoft-avatar---xxx.jpg
        $path = ltrim($parts['path'], '/'); // remove starting slash

        // If your bucket name is part of path, remove it
        // bucket: s3-bk-desk-genie-stage
        if ($bucket_type === 'private') {
            $bucket_name = env('HETZNER_PRIVATE_BUCKET');
        } else {
            $bucket_name = env('HETZNER_PUBLIC_BUCKET');
        }

        if (Str::startsWith($path, $bucket_name . '/')) {
            $path = Str::after($path, $bucket_name . '/');
        }

        // Now $path should be: Profile-Photos/microsoft-avatar---xxx.jpg
        return urldecode($path);
    } // End Function

    public function getNewPresignedURLForExistingFiles($path)
    {
        $new_url = null;
        if (!empty($path)) {
            if (Str::startsWith($path, env('HETZNER_ENDPOINT'))) {
                $file_name = $this->extractS3KeyFromUrl($path, 'private');
                $new_path_details = $this->getFilePathFromHetzner($file_name);
                if ($new_path_details['path'] != null) {
                    $new_url = $new_path_details['path'];
                }
            }
        }
        return $new_url;
    } // End Function
}
