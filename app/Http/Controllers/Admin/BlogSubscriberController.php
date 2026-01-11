<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogSubscriber;
use Illuminate\Http\Request;

class BlogSubscriberController extends Controller
{
    /**
     * Subscriber listesi
     */
    public function index()
    {
        $subscribers = BlogSubscriber::latest()->paginate(20);
        return view('admin.blog-subscribers.index', compact('subscribers'));
    }

    /**
     * Yeni subscriber ekleme (ADMIN)
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // Aynı email var mı? (decrypt ederek kontrol)
        $exists = BlogSubscriber::all()
            ->contains(fn ($sub) => $sub->email === $request->email);

        if ($exists) {
            return back()->withErrors([
                'email' => 'This email is already subscribed.'
            ]);
        }

        BlogSubscriber::create([
            'email' => $request->email,
        ]);

        return back()->with('success', 'Subscriber added successfully.');
    }

    /**
     * Aktif / Pasif toggle
     */
    public function update(Request $request, BlogSubscriber $blogSubscriber)
    {
        $request->validate([
            'status' => 'required|boolean',
        ]);

        $blogSubscriber->update([
            'status' => $request->status,
        ]);

        return back()->with('success', 'Subscriber status updated.');
    }

    /**
     * Subscriber silme
     */
    public function destroy(BlogSubscriber $blogSubscriber)
    {
        $blogSubscriber->delete();

        return back()->with('success', 'Subscriber deleted successfully.');
    }
}
