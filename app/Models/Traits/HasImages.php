<?php

namespace App\Models\Traits;

use App\Models\Image;
use Illuminate\Support\Facades\Storage;

trait HasImages
{
    protected static function bootHasImages()
    {
        static::deleting(function ($model) {

            Image::where('source', $model->getImageSource())
                ->where('source_id', $model->id)
                ->get()
                ->each(function ($image) {
                    Storage::disk('private')->delete($image->path);
                    $image->delete();
                });

        });
    }

    /**
     * Her model bunu tanımlamak ZORUNDA
     */
    abstract public function getImageSource(): string;
}
