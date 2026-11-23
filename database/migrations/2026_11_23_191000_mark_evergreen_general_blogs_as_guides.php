<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Canlı veri düzeltmesi (23.11.2026, şehirsiz 40 yazı tek tek incelendi): şehre bağlı olmayan ama haber değil,
 * her yerde geçerli REHBER olan yazılar "guide" olur (City Passes Explained, Skip-the-Line Tickets Explained,
 * 3-Day City Break, Audio Guide vs Guided Tour, How Far in Advance to Book, 20 Smart Travel Tips, Avoid Tourist
 * Traps, Religious Sites Etiquette, Traveling With Kids, Packing List, Sightseeing Budget, Passenger Rights,
 * Plan Your Trip with TripSpoiler). Diğer şehirsiz yazılar güncel haber (news) kalır. Slug ile eşleşir:
 * başka veritabanında (yerel, test) bu yazılar yoksa hiçbir şey değişmez.
 */
return new class extends Migration
{
    public function up(): void
    {
        $ids = [10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 65, 126];
        $titles = ['City Passes Explained', 'Skip-the-Line Tickets Explained', 'How to Plan a 3-Day City Break', 'Audio Guide vs Guided Tour',
            'How Far in Advance Should You Book', 'First Time in a New City', 'How to Avoid Tourist Traps', 'Visiting Mosques, Churches',
            'Traveling With Kids', 'The Ultimate City Sightseeing Packing List', 'How to Budget for Sightseeing', 'Flight Cancelled? Your Passenger Rights',
            'Plan Your Trip with TripSpoiler'];

        foreach (DB::table('blogs')->whereIn('id', $ids)->get(['id', 'title']) as $blog) {
            $title = (string) (json_decode((string) $blog->title, true)['en'] ?? '');
            // id ile birlikte başlık da tutsun: yanlış yazıya dokunulmasın
            if (collect($titles)->contains(fn ($t) => str_starts_with($title, $t))) {
                DB::table('blogs')->where('id', $blog->id)->update(['kind' => 'guide']);
            }
        }
    }

    public function down(): void {}
};
