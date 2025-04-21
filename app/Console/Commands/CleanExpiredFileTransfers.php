<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FileTransfer;
use Illuminate\Support\Facades\Storage;

class CleanExpiredFileTransfers extends Command
{
    protected $signature = 'app:clean-expired-file-transfers';
    protected $description = 'Deletes expired file transfers and their files (older than 30 days)';

    public function handle()
    {
        $expiredTransfers = FileTransfer::where('expires_at', '<=', now())->get();

        foreach ($expiredTransfers as $transfer) {
            $this->info("Cleaning transfer ID: {$transfer->id}");

            $files = explode(',', $transfer->file_path);

            foreach ($files as $file) {
                $path = "Transfer Files/" . ltrim($file, '/');
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                    $this->line("Deleted file: $path");
                } else {
                    $this->warn("File not found: $path");
                }
            }

            $transfer->delete();
            $this->info("Deleted transfer record: {$transfer->id}");
        }

        if ($expiredTransfers->isEmpty()) {
            $this->info('No expired transfers found.');
        }

        return Command::SUCCESS;
    }
}