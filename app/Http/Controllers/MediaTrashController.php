<?php

namespace App\Http\Controllers;

use App\Support\MediaTrash;
use Illuminate\Http\Request;

/**
 * Yönetim › Silinen resimler: çöp kutusundaki resimler (30 gün). Geri al: resim eski yerine döner.
 */
class MediaTrashController extends Controller
{
    public function index()
    {
        return view('admin.media-trash.index', ['items' => MediaTrash::items()]);
    }

    public function restore(Request $request)
    {
        $data = $request->validate(['file' => ['required', 'string', 'max:500']]);
        $restored = MediaTrash::restore($data['file']);

        return back()->with($restored ? 'success' : 'error', $restored
            ? __('Resim geri alındı; eski yerinde.')
            : __('Resim geri alınamadı (aynı yerde başka bir dosya olabilir).'));
    }
}
