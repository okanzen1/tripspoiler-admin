<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\SettingKeys;
use App\Models\Setting;
use App\Services\ImageUploader;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Site settings with their current values and meaning: home hero image/crop/darkening/cards, which home sections are hidden, home blog posts, page hero crop/darkening, blog arches, mailbox signature.')]
class GetSettings extends PanelTool
{
    protected function run(Request $request): array
    {
        $values = Setting::pluck('value', 'key');
        $out = [];
        foreach (SettingKeys::KNOWN as $key => $about) {
            $value = $values[$key] ?? null;
            $out[$key] = ['value' => isset(SettingKeys::IMAGES[$key]) ? ImageUploader::url($value) : $value, 'about' => $about];
        }
        foreach ($values as $key => $value) {
            if (!isset($out[$key]) && ($about = SettingKeys::describe($key))) {
                $out[$key] = ['value' => $value, 'about' => $about];
            }
        }
        $out['_more'] = SettingKeys::PATTERNS;

        return $out;
    }
}
