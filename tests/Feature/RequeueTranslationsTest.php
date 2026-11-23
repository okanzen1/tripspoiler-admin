<?php

namespace Tests\Feature;

use App\Jobs\TranslateModel;
use App\Models\Faq;
use App\Models\Language;
use Illuminate\Bus\UniqueLock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RequeueTranslationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_bulk_jobs_are_replaced_by_one_job_per_record(): void
    {
        config(['queue.default' => 'database']);
        foreach ([['en', 'English'], ['de', 'German']] as $i => [$code, $name]) {
            Language::create(['code' => $code, 'name' => $name, 'native_name' => $name, 'sort_order' => $i, 'active' => true]);
        }
        $faq = Faq::create(['question' => ['en' => 'Open?'], 'answer' => ['en' => 'Yes.']]);
        // eski tekrarlar + kalmış "zaten sırada" kilidi
        foreach (range(1, 5) as $_) {
            DB::table('jobs')->insert(['queue' => 'bulk', 'payload' => '{}', 'attempts' => 0, 'available_at' => time(), 'created_at' => time()]);
        }
        Cache::lock(UniqueLock::getKey(new TranslateModel($faq)), 0)->get();

        $this->artisan('translations:requeue')->assertSuccessful();

        $payloads = DB::table('jobs')->where('queue', 'bulk')->pluck('payload');
        $this->assertNotContains('{}', $payloads->all()); // tekrarlar silindi
        $models = $payloads->map(fn ($p) => unserialize(json_decode($p, true)['data']['command'])->model)
            ->map(fn ($m) => $m->getMorphClass() . ':' . $m->getKey());
        $this->assertSame($models->count(), $models->unique()->count()); // her kayıt bir kez
        $this->assertContains((new Faq)->getMorphClass() . ':' . $faq->id, $models->all()); // kilitli kayıt da sıraya girdi
    }
}
