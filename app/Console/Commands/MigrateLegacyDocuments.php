<?php

namespace App\Console\Commands;

use App\Models\Content;
use Illuminate\Console\Command;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class MigrateLegacyDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'walhi:migrate-documents
        {--dry-run : Report what would happen without copying, deleting or updating anything}
        {--delete-legacy : Delete a legacy public copy once an identical private copy is verified}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate legacy documents from public storage to private storage and normalize database records';

    /**
     * Execute the console command.
     *
     * Safe to re-run: a private file that already exists is never overwritten, and a
     * public copy is only deleted after its SHA-256 matches the private copy. Any
     * conflict leaves both files untouched and makes the command exit non-zero.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $deleteLegacy = (bool) $this->option('delete-legacy');
        $this->info('Starting legacy document migration to private storage'.($dryRun ? ' (dry run)' : '').'...');

        $publicDisk = Storage::disk('public');
        $localDisk = Storage::disk('local');

        $copied = 0;
        $alreadyPresent = 0;
        $removed = 0;
        $conflicts = 0;
        $dbUpdatedCount = 0;

        // 1. Scan files in public disk 'documents/' folder
        $files = $publicDisk->exists('documents') ? $publicDisk->files('documents') : [];
        $this->info(sprintf('Found %d file(s) in public/documents.', count($files)));

        foreach ($files as $publicPath) {
            $filename = basename($publicPath);
            $privatePath = 'documents/'.$filename;
            $publicHash = $this->sha256($publicDisk, $publicPath);

            if ($localDisk->exists($privatePath)) {
                if ($this->sha256($localDisk, $privatePath) !== $publicHash) {
                    $conflicts++;
                    $this->error("  [CONFLICT] {$filename}: a different private file already exists; both copies kept.");

                    continue;
                }
                $alreadyPresent++;
                $this->line("  [VERIFIED] {$filename} sha256={$publicHash}");
            } elseif ($dryRun) {
                $this->line("  [WOULD COPY] {$filename} sha256={$publicHash}");
            } else {
                $localDisk->writeStream($privatePath, $publicDisk->readStream($publicPath));
                if ($this->sha256($localDisk, $privatePath) !== $publicHash) {
                    $conflicts++;
                    $this->error("  [COPY FAILED] {$filename}: checksum mismatch after copy; public copy kept.");

                    continue;
                }
                $copied++;
                $this->line("  [COPIED] {$filename} sha256={$publicHash}");
            }

            if ($deleteLegacy) {
                if ($dryRun) {
                    $this->line("  [WOULD REMOVE] public {$publicPath}");
                } else {
                    $publicDisk->delete($publicPath);
                    $removed++;
                    $this->line("  [REMOVED] public {$publicPath}");
                }
            }
        }

        // 2. Normalize database records
        $legacyContents = Content::withTrashed()
            ->whereNotNull('image_url')
            ->where(function ($q) {
                $q->where('image_url', 'like', '/storage/documents/%')
                    ->orWhere('image_url', 'like', 'storage/documents/%');
            })
            ->get();

        foreach ($legacyContents as $item) {
            $raw = (string) $item->getRawOriginal('image_url');
            $normalizedPath = 'documents/'.basename($raw);

            if ($dryRun) {
                $this->line("  [WOULD NORMALIZE] content #{$item->id}: {$raw} -> {$normalizedPath}");
            } else {
                $item->image_url = $normalizedPath;
                $item->saveQuietly();
                $this->line("  [NORMALIZED] content #{$item->id}: {$raw} -> {$normalizedPath}");
            }
            $dbUpdatedCount++;
        }

        $this->table(['Result', 'Count'], [
            ['Copied to private storage', $copied],
            ['Already present (checksum verified)', $alreadyPresent],
            ['Legacy public copies removed', $removed],
            ['Conflicts (left untouched)', $conflicts],
            [$dryRun ? 'Database records to normalize' : 'Database records normalized', $dbUpdatedCount],
        ]);

        if ($conflicts > 0) {
            $this->error('Migration finished with conflicts; resolve them manually before re-running.');

            return Command::FAILURE;
        }

        $this->info($dryRun ? 'Dry run completed; nothing was changed.' : 'Migration completed successfully!');

        return Command::SUCCESS;
    }

    private function sha256(FilesystemAdapter $disk, string $path): string
    {
        return (string) $disk->checksum($path, ['checksum_algo' => 'sha256']);
    }
}
