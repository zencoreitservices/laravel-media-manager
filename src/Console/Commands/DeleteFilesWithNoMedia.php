<?php

namespace Zencoreitservices\MediaManager\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DeleteFilesWithNoMedia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media-manager:delete-files-with-no-media';
 
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes files with no media.';
 
    /**
     * Execute the console command.
     *
     * @param  \App\Support\DripEmailer  $drip
     * @return mixed
     */
    public function handle()
    {
        $masterDirectories = [
            'image',
            'video',
            'audio',
        ];
        foreach ( $masterDirectories as $masterDirectory ) {
            if ($masterDirectory == 'image') {
                foreach (config('media-manager.image-types') as $imageTypeSlug => $imageType) {
                    foreach (Storage::disk(config('media-manager.disk'))->files($masterDirectory . '/' . $imageTypeSlug) as $file) {
                        $fileExploded = explode('/', $file);
                        $fileName = end($fileExploded);
                        list($name, $extension) = explode('.', $fileName);

                        if (
                            !config('media-manager.classes.media')::where([
                                ['type', '=', $masterDirectory],
                                ['image_type', '=', $imageTypeSlug],
                                ['name', '=', $name],
                                ['extension', '=', $extension],
                            ])->first()
                        ) {
                            Storage::disk(config('media-manager.disk'))->delete($file);
                        }
                    }
                }
            } else {
                foreach (Storage::disk(config('media-manager.disk'))->files($masterDirectory) as $file) {
                    $fileExploded = explode('/', $file);
                    $fileName = end($fileExploded);
                    list($name, $extension) = explode('.', $fileName);

                    if (
                        !config('media-manager.classes.media')::where([
                            ['type', '=', $masterDirectory],
                            ['image_type', '=', null],
                            ['name', '=', $name],
                            ['extension', '=', $extension],
                        ])->first()
                    ) {
                        Storage::disk(config('media-manager.disk'))->delete($file);
                    }
                }
            }
        }
    }
}