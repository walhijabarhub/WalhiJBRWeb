<?php

namespace App\Console\Commands;

use App\Models\Content;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportCleanMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'walhi:import-clean-data 
                            {--file=database/data/data-migasi-clean.json : Path ke file JSON data bersih}
                            {--truncate : Kosongkan tabel contents sebelum mengimpor}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Impor data bersih hasil migrasi WordPress ke tabel contents';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = base_path($this->option('file'));
        $truncate = $this->option('truncate');

        $this->info('=== IMPOR DATA BERSIH WALHI JAWA BARAT ===');
        $this->line("Memuat data dari: {$filePath}");

        if (! file_exists($filePath)) {
            $this->error("File data tidak ditemukan pada path: {$filePath}");

            return Command::FAILURE;
        }

        $rawJson = file_get_contents($filePath);
        $articles = json_decode($rawJson, true);

        if (! is_array($articles) || empty($articles)) {
            $this->error('File JSON kosong atau format data tidak valid.');

            return Command::FAILURE;
        }

        $totalItems = count($articles);
        $this->line("Ditemukan {$totalItems} artikel terverifikasi.");

        // Jika opsi truncate dipilih, bersihkan kategori editorial namun pertahankan data struktural website (kontak, statistik, isu-kritis)
        if ($truncate) {
            $this->warn('Membersihkan artikel publikasi editorial lama...');
            $editorialCategories = ['blog', 'siaran-pers', 'infografis', 'kertas-posisi', 'catatan-kritis', 'laporan-tahunan'];
            Content::whereIn('category', $editorialCategories)->forceDelete();
            $this->info('Kategori editorial lama berhasil dibersihkan (kontak, statistik, dan tata letak struktural tetap aman).');
        }

        $this->line('Memulai proses penyimpanan ke database...');
        $bar = $this->output->createProgressBar($totalItems);
        $bar->start();

        $imported = 0;
        $failed = 0;
        $categoryCounts = [];

        foreach ($articles as $item) {
            try {
                $rawTitle = trim($item['title'] ?? '');
                if (empty($rawTitle)) {
                    $failed++;
                    $bar->advance();

                    continue;
                }

                $title = Str::limit($rawTitle, 250, '');
                $baseSlug = Str::limit(Str::slug($title), 180, '');
                $slug = $baseSlug;
                $counter = 1;

                while (Content::where('slug', $slug)->exists()) {
                    $slug = $baseSlug.'-'.$counter;
                    $counter++;
                }

                $category = $item['category'] ?? 'blog';

                Content::create([
                    'title' => $title,
                    'slug' => $slug,
                    'body' => $item['body'] ?? '',
                    'tags' => ! empty($item['tags']) ? $item['tags'] : null,
                    'status' => $item['status'] ?? 'published',
                    'image_url' => ! empty($item['image_url']) ? $item['image_url'] : null,
                    'publish_date' => ! empty($item['publish_date']) ? $item['publish_date'] : now()->format('Y-m-d'),
                    'category' => $category,
                    'is_promoted' => ! empty($item['is_promoted']),
                    'author' => $item['author'] ?? 'WALHI Jawa Barat',
                    'views' => isset($item['views']) ? (int) $item['views'] : rand(50, 300),
                ]);

                $imported++;
                $categoryCounts[$category] = ($categoryCounts[$category] ?? 0) + 1;
            } catch (\Exception $e) {
                $failed++;
                $this->error("\nGagal mengimpor: {$title} - ".$e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('=== PROSES IMPOR SELESAI ===');
        $this->info("Berhasil diimpor : {$imported} artikel");
        if ($failed > 0) {
            $this->warn("Gagal diimpor    : {$failed} artikel");
        }

        $this->newLine();
        $this->line('Distribusi Kategori Terimpor:');
        foreach ($categoryCounts as $cat => $count) {
            $this->line("  - {$cat}: {$count} artikel");
        }

        return Command::SUCCESS;
    }
}
