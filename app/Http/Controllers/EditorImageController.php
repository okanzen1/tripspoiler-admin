<?php

namespace App\Http\Controllers;

use App\Services\ImageUploader;
use Illuminate\Http\Request;

/**
 * Zengin metin editöründen eklenen yazı içi görseller: dosya media diskine kaydedilir,
 * içeriğe sadece adresi girer (veritabanına base64 resim gömülmez).
 */
class EditorImageController extends Controller
{
    public function store(Request $request, ImageUploader $uploader)
    {
        $request->validate(['image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120']]);

        $image = $uploader->content($request->file('image'));

        return response()->json(['url' => ImageUploader::url($image['path']), 'width' => $image['width'], 'height' => $image['height']]);
    }
}
