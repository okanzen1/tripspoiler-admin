<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\SettingKeys;
use App\Models\Setting;
use App\Services\ImageUploader;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Http;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Change site settings (see get-settings for keys and meaning), e.g. {"sections_disabled": "social,partners"} or {"hero_overlay": "soft"} or {"hero_image": "https://…jpg"}. null clears a setting. Only change what the user asked.')]
class SaveSettings extends PanelTool
{
    public function schema(JsonSchema $schema): array
    {
        return ['values' => $schema->object()->required()->description('{"key": value, …}')];
    }

    protected function run(Request $request): array
    {
        $values = (array) $request->get('values', []);
        $changed = [];

        foreach ($values as $key => $value) {
            if (!SettingKeys::describe((string) $key)) {
                throw new \RuntimeException("Unknown setting \"{$key}\". See get-settings.");
            }
            $value = is_array($value) ? implode(',', $value) : (is_bool($value) ? ($value ? '1' : '0') : ($value === null ? null : (string) $value));

            if ($method = SettingKeys::IMAGES[$key] ?? null) {
                $uploader = app(ImageUploader::class);
                if (blank($value)) {
                    $uploader->delete(Setting::get($key));
                } else {
                    $response = Http::timeout(30)->withHeaders(['User-Agent' => 'Mozilla/5.0 TripSpoiler'])->get($value);
                    if (!$response->successful() || !str_starts_with((string) $response->header('Content-Type'), 'image/')) {
                        throw new \RuntimeException("Could not download an image from {$value}.");
                    }
                    $value = $uploader->{$method}($response->body(), Setting::get($key));
                }
            }

            Setting::put((string) $key, blank($value) ? null : $value);
            $changed[$key] = isset(SettingKeys::IMAGES[$key]) ? ImageUploader::url($value) : $value;
        }

        return ['result' => 'saved', 'changed' => $changed];
    }
}
