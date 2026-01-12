<?php

namespace App\Http\Controllers;

use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageController extends Controller
{
    private function ensureSuperAdmin(): void
    {
        abort_unless(auth()->check() && auth()->user()?->role === 'super_admin', 403);
    }

    private function folderForSource(string $source): string
    {
        return match ($source) {
            'activity' => 'activities',
            'venue' => 'venues',
            'museum' => 'museums',
            'city' => 'cities',
            default => $source,
        };
    }

    public function show(Image $image)
    {
        $fullPath = storage_path('app/private/' . $image->path);

        if (!file_exists($fullPath)) {
            abort(404);
        }

        return response()->file($fullPath, [
            'Content-Disposition' => 'inline',
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureSuperAdmin();

        $data = $request->validate([
            'file' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
            'source' => 'required|string|max:50',
            'source_id' => 'required|integer|min:1',
        ]);

        $folder = $this->folderForSource($data['source']);
        $filename = 'tripspoiler_'.Str::random(24).'.webp';

        $path = $request->file('file')->storeAs(
            "{$folder}/{$data['source_id']}",
            $filename,
            'private'
        );

        $maxOrder = Image::where('source', $data['source'])
            ->where('source_id', $data['source_id'])
            ->max('sort_order');

        $image = Image::create([
            'path' => $path,
            'source' => $data['source'],
            'source_id' => $data['source_id'],
            'sort_order' => is_null($maxOrder) ? 0 : ($maxOrder + 1),
        ]);

        return response()->json([
            'id' => $image->id,
            'url' => rtrim(config('media.front_url'), '/') . '/media/' . $image->id,
        ]);
    }

    public function sort(Request $request)
    {
        $this->ensureSuperAdmin();

        $order = $request->input('order', []);
        abort_unless(is_array($order), 422);

        foreach ($order as $index => $id) {
            Image::where('id', (int) $id)->update(['sort_order' => (int) $index]);
        }

        return response()->json(['success' => true]);
    }
   
   public function destroy(Image $image)
    {
        $this->ensureSuperAdmin();

        Storage::disk('private')->delete($image->path);
        $image->delete();

        return response()->json(['success' => true]);
    }

}
