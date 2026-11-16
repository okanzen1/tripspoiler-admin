<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\Language;
use App\Services\Translation\ModelTranslator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationHashTest extends TestCase
{
    use RefreshDatabase;

    public function test_formatting_change_keeps_translation_current(): void
    {
        $blog = Blog::create(['slug' => 'x', 'active' => false, 'title' => ['en' => 'Hello', 'de' => 'Hallo'], 'content' => ['en' => '<p>Hello  world</p><p>Bye</p>', 'de' => '<p>Hallo Welt</p>']]);
        $translator = app(ModelTranslator::class);
        Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English']);
        Language::create(['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch']);
        $locale = 'de';
        $translator->markCurrent($blog, $locale);

        $blog->setTranslation('content', 'en', "<p>Hello&nbsp;world</p>\n<p class=\"x\">Bye</p>")->save();
        $this->assertSame('ok', $translator->status($blog->fresh())[$locale]);

        $blog->setTranslation('content', 'en', '<p>Hello world</p><p>Goodbye</p>')->save();
        $this->assertNotSame('ok', $translator->status($blog->fresh())[$locale]);
    }
}
