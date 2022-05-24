<?php

namespace Zencoreitservices\MediaManager\Console\Commands;

use Illuminate\Console\Command;

class DeleteMediaWithNoFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media-manager:delete-media-with-no-file';
 
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes media with no file.';
 
    /**
     * Execute the console command.
     *
     * @param  \App\Support\DripEmailer  $drip
     * @return mixed
     */
    public function handle()
    {
        $media = config('media-manager.classes.media')::take(50)->inRandomOrder()->get();
        foreach ($media as $mediaItem) {
            if (!$mediaItem->fileExists()) {
                $mediaItem->delete();
            }
        }
    }
}