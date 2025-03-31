<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BucketTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:bucket-test-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $files = Storage::disk('s3')->allFiles('random');;
        foreach($files as $file) {
            $url = Storage::temporaryUrl(
                $file,
                now()->addMinutes(5)
            );
            $this->info("file: $url");
        }
        return Command::SUCCESS;
    }
}
    