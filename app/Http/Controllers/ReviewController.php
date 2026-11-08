<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewRequest;
use App\Models\Activity;
use App\Models\Review;
use Illuminate\Http\Request;

/**
 * Ziyaretçi yorumları: onay bekleyenler, onaylılar; genel ve aktivite yorumları.
 */
class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search'));
        $status = in_array($request->get('status'), ['pending', 'approved'], true) ? $request->get('status') : null;
        $activityId = $request->get('activity');
        $rating = in_array((int) $request->get('rating'), [1, 2, 3, 4, 5], true) ? (int) $request->get('rating') : null;

        $reviews = Review::with('activity')
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('comment', 'like', "%{$search}%")))
            ->when($status === 'pending', fn ($q) => $q->pending())
            ->when($status === 'approved', fn ($q) => $q->approved())
            ->when($activityId === 'general', fn ($q) => $q->whereNull('activity_id'))
            ->when(is_numeric($activityId), fn ($q) => $q->where('activity_id', (int) $activityId))
            ->when($rating, fn ($q) => $q->where('rating', $rating))
            ->orderBy('approved') // önce onay bekleyenler
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.reviews.index', [
            'reviews' => $reviews,
            'search' => $search,
            'status' => $status,
            'activityId' => $activityId,
            'rating' => $rating,
            'activities' => Activity::whereHas('reviews')->orderBy('sort_order')->get(),
            'counts' => [
                'pending' => Review::pending()->count(),
                'approved' => Review::approved()->count(),
            ],
            'average' => round((float) Review::approved()->avg('rating'), 1),
            'distribution' => Review::approved()->selectRaw('rating, count(*) as n')->groupBy('rating')->pluck('n', 'rating'),
            'pageId' => \App\Models\Page::where('key', 'reviews')->value('id'),
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.reviews.form', $this->formData(new Review([
            'rating' => 5,
            'approved' => true,
            'activity_id' => $request->integer('activity') ?: null,
        ])));
    }

    public function store(ReviewRequest $request)
    {
        $data = $request->validated();
        $date = \Illuminate\Support\Arr::pull($data, 'review_date');
        $review = new Review($data);
        $this->setDate($review, $date);
        $review->save();

        return redirect()->route('reviews.index')->with('success', __('Yorum eklendi.'));
    }

    public function edit(Review $review)
    {
        return view('admin.reviews.form', $this->formData($review));
    }

    public function update(ReviewRequest $request, Review $review)
    {
        $data = $request->validated();
        $date = \Illuminate\Support\Arr::pull($data, 'review_date');
        $review->fill($data);
        $this->setDate($review, $date);
        $review->save();

        return redirect()->route('reviews.index')->with('success', __('Yorum güncellendi.'));
    }

    /** Yorum tarihi (sitede "Kasım 2026" gibi görünür): gün değiştiyse o güne, saati korunarak */
    private function setDate(Review $review, ?string $date): void
    {
        if (!$date) {
            return;
        }
        $current = $review->created_at ?? now();
        if ($current->toDateString() !== $date) {
            $review->created_at = \Carbon\Carbon::parse($date)->setTimeFrom($current);
        }
    }

    public function destroy(Review $review)
    {
        $review->delete();

        return back()->with('success', __('Yorum silindi.'));
    }

    /**
     * Onayla / onayı kaldır (anahtar).
     */
    public function toggle(Request $request, Review $review)
    {
        $review->update(['approved' => !$review->approved]);
        $message = __($review->approved ? ':name adlı kişinin yorumu onaylandı, sitede görünür.' : ':name adlı kişinin yorumu yayından kaldırıldı.', ['name' => $review->name]);

        return $request->expectsJson()
            ? response()->json(['active' => $review->approved, 'message' => $message])
            : back()->with('success', $message);
    }

    private function formData(Review $review): array
    {
        return [
            'review' => $review,
            'activities' => Activity::with('city')->orderBy('city_id')->orderBy('sort_order')->get()
                ->groupBy(fn (Activity $activity) => $activity->city->name),
        ];
    }
}
