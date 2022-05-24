<?php

namespace Zencoreitservices\MediaManager\Models;

use App\Jobs\MediaResizeMissingJob;
use App\Jobs\MediaResizeAllJob;
use Illuminate\Database\Eloquent\Model;
use Storage;
use Str;
use GuzzleHttp\Client;

class Media extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'image_type',
        'name',
        'extension',
    ];

    protected static function createObject($extension, $type, $imageType = null, $userId = null)
    {
        $name = self::generateName();

        return self::create([
            'name' => $name,
            'extension' => $extension,
            'type' => $type,
            'image_type' => $imageType,
            'user_id' => $userId,
        ]);
    }

    public static function add($file, $type, $imageType = null, $userId = null)
    {
        $storage = Storage::disk(config('media-manager.disk'));

        if (is_a($file, \Illuminate\Http\UploadedFile::class)) {
            $extension = $file->extension();
        } else {
            $fileSize = getimagesize($file);
            $extension = str_replace('.', '', image_type_to_extension($fileSize[2]));
        }

        $media = self::createObject($extension, $type, $imageType, $userId);

        if ($type == 'image') {
            if (!$imageType) {
                throw new \Exception('Image type is required');
            }

            if (!isset(config('media-manager.image-types')[$imageType])) {
                throw new \Exception('Image type is not defined in config');
            }

            $storage->makeDirectory($type . '/' . $imageType);
            $storage->putFileAs(
                sprintf('%s/%s', $type, $imageType),
                $file,
                sprintf('%s.%s', $media->name, $media->extension)
            );

            $originalFilePath = $storage->path($media->getFilePath());

            $imageProcessorClass = config('media-manager.classes.image-processor');
            $imageProcessor = new $imageProcessorClass($originalFilePath);
            foreach (config('media-manager.image-types')[$imageType] as $imageSizeSlug => $imageSize) {
                $imageProcessor
                    ->resize(
                        $imageSize['width'] ?? null,
                        $imageSize['height'] ?? null,
                        $imageSize['fit']
                    )
                    ->save($storage->path($media->getFilePath($imageSizeSlug)));

                $imageProcessor->newImage($originalFilePath);
            }
        } else {
            $storage->makeDirectory($type);
            $storage->putFileAs(
                sprintf('%s', $type),
                $file,
                sprintf('%s.%s', $media->name, $media->extension)
            );
        }

        return $media;
    }

    public static function addFromUrl($url, $type, $imageType = null, $userId = null)
    {
        $fileSize = getimagesize($url);
        $extension = str_replace('.', '', image_type_to_extension($fileSize[2]));

        $filePath = storage_path('app/tmp/downloadedFile.' . $extension);

        $client = new Client([
            'timeout'  => 10.0,
            'verify' => false,
        ]);

        Storage::makeDirectory('tmp');
        $client->request('GET', $url, ['sink' => $filePath]);

        $media = self::add($filePath, $type, $imageType, $userId);

        Storage::deleteDirectory('tmp');

        return $media;
    }

    public function fileExists($imageSize = '')
    {
        return Storage::disk(config('media-manager.disk'))
            ->exists(
                $this->getFilePath($imageSize)
            );
    }

    public function getFilePath($imageSize = '')
    {
        if ($this->type == 'image') {
            $name = $this->name;

            if ($imageSize) {
                $name .= '_' . $imageSize;
            }

            return sprintf(
                '%s/%s/%s.%s',
                $this->type,
                $this->image_type,
                $name,
                $this->extension
            );
        } else {
            return sprintf(
                '%s/%s.%s',
                $this->type,
                $this->name,
                $this->extension
            );
        }
    }

    public function deleteAllFiles()
    {
        if ($this->type == 'image') {
            foreach (array_keys(config('media-manager.image-types')[$this->image_type]) as $imageSizeSlug) {
                Storage::disk(config('media-manager.disk'))->delete(
                    $this->getFilePath($imageSizeSlug)
                );
            }
        }

        return Storage::disk(config('media-manager.disk'))->delete(
            $this->getFilePath()
        );
    }

    public function url($imageSize = '')
    {
        $storage = Storage::disk(config('media-manager.disk'));

        if ($this->file_type == 'image' && $imageSize) {
            if ($this->fileExists($imageSize)) {
                return $storage->url($this->getFilePath($imageSize));
            }
        }
        
        if ($this->fileExists()) {
            return $storage->url($this->getFilePath());
        }

        return null;
    }

    public function content($imageSize = '')
    {
        $storage = Storage::disk(config('media-manager.disk'));

        if ($this->file_type == 'image' && $imageSize) {
            if ($this->fileExists($imageSize)) {
                return utf8_encode(
                    file_get_contents(
                        $storage->path($this->getFilePath($imageSize))
                    )
                );
            }
        }

        if ($this->fileExists()) {
            return utf8_encode(
                file_get_contents(
                    $storage->path($this->getFilePath())
                )
            );
        }

        return null;
    }

    public function resizeMissingImages()
    {
        $storage = Storage::disk(config('media-manager.disk'));

        if ($this->type == 'image') {
            $originalFilePath = $storage->path($this->getFilePath());
            $imageProcessorClass = config('media-manager.classes.image-processor');
            $imageProcessor = new $imageProcessorClass($originalFilePath);

            foreach (config('media-manager.image-types.' . $this->image_type) as $imageSizeSlug => $imageSize) {
                if (!$this->fileExists($imageSizeSlug)) {
                    $imageProcessor
                        ->resize(
                            $imageSize['width'] ?? null,
                            $imageSize['height'] ?? null,
                            $imageSize['fit']
                        )
                        ->save($storage->path($this->getFilePath($imageSizeSlug)));
                    
                    $imageProcessor->newImage($originalFilePath);
                }
            }
        }
    }

    public function resizeAllImages()
    {
        $storage = Storage::disk(config('media-manager.disk'));

        if ($this->type == 'image') {
            $originalFilePath = $storage->path($this->getFilePath());
            $imageProcessorClass = config('media-manager.classes.image-processor');
            $imageProcessor = new $imageProcessorClass($originalFilePath);

            foreach (config('media-manager.image-types.' . $this->image_type) as $imageSizeSlug => $imageSize) {
                $imageProcessor
                    ->resize(
                        $imageSize['width'] ?? null,
                        $imageSize['height'] ?? null,
                        $imageSize['fit']
                    )
                    ->save($storage->path($this->getFilePath($imageSizeSlug)));

                $imageProcessor->newImage($originalFilePath);
            }
        }
    }

    public static function staticResizeMissingImages($imageType = '')
    {
        if ($imageType) {
            $media = Media::where([
                ['type', '=', 'image'],
                ['image_type', '=', $imageType],
            ])->get();
        } else {
            $media = Media::where([
                ['type', '=', 'image'],
            ])->get();
        }
        
        foreach ($media as $mediaItem) {
            MediaResizeMissingJob::dispatch($mediaItem);
        }
    }

    public static function staticResizeAllImages($imageType = '')
    {
        if ($imageType) {
            $media = Media::where([
                ['type', '=', 'image'],
                ['image_type', '=', $imageType],
            ])->get();
        } else {
            $media = Media::where([
                ['type', '=', 'image'],
            ])->get();
        }
        
        foreach ($media as $mediaItem) {
            MediaResizeAllJob::dispatch($mediaItem);
        }
    }

    protected static function generateName()
    {
        do {
            $name = Str::random(32);
        } while( self::where('name', $name)->first() );

        return $name;
    }
}
