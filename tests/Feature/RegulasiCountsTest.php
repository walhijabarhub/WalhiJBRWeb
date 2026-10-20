<?php

namespace Tests\Feature;

use App\Enums\ContentCategory;
use App\Models\Content;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegulasiCountsTest extends TestCase
{
    use RefreshDatabase;

    private function regulasi(string $tags, string $status = 'published'): Content
    {
        static $n = 0;
        $n++;

        return Content::create([
            'title' => "Regulasi {$n}",
            'slug' => "regulasi-{$n}",
            'category' => ContentCategory::Regulasi->value,
            'status' => $status,
            'body' => 'Isi',
            'tags' => $tags,
        ]);
    }

    public function test_counts_per_type_come_from_published_entries_only(): void
    {
        $this->regulasi('undang-undang, Pemerintah RI, berlaku');
        $this->regulasi('undang-undang, Pemerintah RI, berlaku');
        $this->regulasi('peraturan pemerintah, Pemerintah RI, berlaku');
        $this->regulasi('peraturan daerah, Pemprov Jabar, berlaku');
        $this->regulasi('keputusan menteri, KLHK, berlaku');
        $this->regulasi('peraturan menteri, KLHK, berlaku');
        $this->regulasi('undang-undang, Pemerintah RI, berlaku', 'draft');

        $this->get(route('regulasi'))
            ->assertOk()
            ->assertViewHas('totalRegulasi', 6)
            ->assertViewHas('countUU', 2)
            ->assertViewHas('countPP', 1)
            ->assertViewHas('countPD', 1)
            ->assertViewHas('countKM', 2);
    }

    public function test_counts_use_a_single_query_and_are_cached(): void
    {
        $this->regulasi('undang-undang, Pemerintah RI, berlaku');

        DB::enableQueryLog();
        $this->get(route('regulasi'))->assertOk();
        $first = collect(DB::getQueryLog())->filter(fn ($q) => str_contains(strtolower($q['query']), 'sum(case'));
        DB::flushQueryLog();
        $this->get(route('regulasi'))->assertOk();
        $second = collect(DB::getQueryLog())->filter(fn ($q) => str_contains(strtolower($q['query']), 'sum(case'));

        $this->assertCount(1, $first);
        $this->assertCount(0, $second, 'Counts must be served from the cache on the second request.');
    }

    public function test_saving_content_refreshes_the_counts(): void
    {
        $this->regulasi('undang-undang, Pemerintah RI, berlaku');
        $this->get(route('regulasi'))->assertViewHas('countUU', 1);

        $this->regulasi('undang-undang, Pemerintah RI, berlaku');

        $this->get(route('regulasi'))->assertViewHas('countUU', 2)->assertViewHas('totalRegulasi', 2);
    }
}
