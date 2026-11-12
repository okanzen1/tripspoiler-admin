<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnnouncementRequest;
use App\Models\Announcement;
use Illuminate\Http\Request;

/**
 * Sitenin en üstündeki kayan duyuru şeridi: yayındaki duyurular sırayla döner.
 */
class AnnouncementController extends Controller
{
    public function index()
    {
        return view('admin.announcements.index', [
            'announcements' => Announcement::orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.announcements.form', ['announcement' => new Announcement(['active' => true])]);
    }

    public function store(AnnouncementRequest $request)
    {
        $announcement = new Announcement($request->safe()->only(['link', 'active']));
        $announcement->setTranslation('text', 'en', $request->validated('text'));
        $announcement->sort_order = (int) Announcement::max('sort_order') + 1;
        $announcement->save();

        // Eklenen kaydın sayfasında kal: hemen diğer dillere çevrilebilsin
        return redirect()->route('announcements.edit', $announcement)->with('success', __('Duyuru eklendi.') . ' ' . __('Şimdi yukarıdaki Çeviriler kutusundan diğer dillere çevirebilirsin.'));
    }

    public function edit(Announcement $announcement)
    {
        return view('admin.announcements.form', ['announcement' => $announcement]);
    }

    public function update(AnnouncementRequest $request, Announcement $announcement)
    {
        $announcement->fill($request->safe()->only(['link', 'active']));
        $announcement->setTranslation('text', 'en', $request->validated('text'));
        $announcement->save();

        return redirect()->route('announcements.index')->with('success', __('Duyuru güncellendi.'));
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return back()->with('success', __('Duyuru silindi.'));
    }

    public function toggle(Request $request, Announcement $announcement)
    {
        $announcement->update(['active' => !$announcement->active]);
        $message = __($announcement->active ? 'Duyuru yayına alındı.' : 'Duyuru yayından kaldırıldı.');

        return $request->expectsJson()
            ? response()->json(['active' => $announcement->active, 'message' => $message])
            : back()->with('success', $message);
    }
}
