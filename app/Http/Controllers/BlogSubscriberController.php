<?php

namespace App\Http\Controllers;

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
        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        // 2️⃣ Normalize + hash
        $normalizedEmail = mb_strtolower(trim($validated['email']));
        $emailHash = hash('sha256', $normalizedEmail);

        if (BlogSubscriber::where('email_hash', $emailHash)->exists()) {
            return back()
                ->withErrors([
                    'email' => 'This email is already subscribed.',
                ])
                ->withInput();
        }

        BlogSubscriber::create([
            'email' => $validated['email'],
        ]);

        return back()->with('success', 'Subscriber added successfully.');
    }

    /**
     * Aktif / Pasif toggle
     */
    public function update(Request $request, BlogSubscriber $blogSubscriber)
    {
        $validated = $request->validate([
            'status' => 'required|boolean',
        ]);

        $blogSubscriber->update([
            'status' => $validated['status'],
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
