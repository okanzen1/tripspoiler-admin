<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Activity;
use App\Models\Blog;
use Illuminate\Http\Request;

class ReviewController extends Controller
{

    public const SOURCES = [

        'activity-show' => 'Activity Ürün Sayfası',
        'blog-show' => 'Blog Ürün Sayfası',
        'home' => 'Home Sayfası',
        'activity' => 'Activity Sayfası',
        'cities' => 'Cities Sayfası',
        'blog' => 'Blog Sayfası',

    ];


    public function index()
    {

        $reviews = Review::latest()->paginate(20);

        return view('admin.reviews.index', compact('reviews'));
    }


    public function create(Request $request)
    {
        return view('admin.reviews.create', [
            'sources' => self::SOURCES,
            'defaultSource' => $request->get('source'),
            'defaultSourceId' => $request->get('source_id'),
        ]);
    }


    public function store(Request $request)
    {

        $data = $request->validate([

            'name' => 'required|string|max:255',
            'source' => 'required|string',
            'source_id' => 'nullable|integer',

        ]);


        $review = Review::create([

            'name' => $data['name'],
            'email' => $data['email'] ?? null,

            'source' => $data['source'],
            'source_id' => $data['source_id'] ?? null,

            'rating' => 5,
            'comment' => null,
            'approved' => false,
        ]);

        if ($request->filled('return_to')) {
            return redirect($request->return_to);
        }

        return redirect()->route('reviews.edit', $review);
    }


    public function edit(Review $review)
    {
        $activities = collect();
        $blogs = collect();

        if ($review->source === 'activity-show') {
            $activities = Activity::select('id', 'name')
                ->orderBy('id', 'desc')
                ->get();
        }

        if ($review->source === 'blog-show') {
            $blogs = Blog::select('id', 'title')
                ->orderBy('id', 'desc')
                ->get();
        }

        return view('admin.reviews.edit', [

            'review' => $review,
            'sources' => self::SOURCES,
            'activities' => $activities,
            'blogs' => $blogs

        ]);
    }


    public function update(Request $request, Review $review)
    {

        $data = $request->validate([

            'name' => 'required|string|max:255',
            'email' => 'nullable|email',

            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string',

            'source' => 'required|string',
            'source_id' => 'nullable|integer',

            'approved' => 'nullable|boolean'

        ]);

        if (isset($data['email'])) {
            $data['email_hash'] = hash('sha256', $data['email']);
        }

        $review->update($data);

        if ($request->filled('return_to')) {
            return redirect($request->return_to);
        }
    
        return redirect()
            ->route('reviews.edit', $review)
            ->with('success', 'Yorum güncellendi');
    }


    public function destroy(Request $request, Review $review)
    {

        $review->delete();

        if ($request->filled('return_to')) {
            return redirect($request->return_to);
        }

        return redirect()->route('reviews.index');
    }
}
