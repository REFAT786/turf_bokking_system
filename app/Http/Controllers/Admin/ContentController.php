<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\SiteContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Admin → Website content: edit the public site's texts, images and FAQ. */
class ContentController extends Controller
{
    public function edit(Request $request)
    {
        $section = array_key_exists($request->query('tab'), SiteContent::SECTIONS) ? $request->query('tab') : array_key_first(SiteContent::SECTIONS);

        return view('admin.content', ['active' => $section]);
    }

    public function update(Request $request)
    {
        $section = $request->input('section');
        abort_unless(isset(SiteContent::SECTIONS[$section]), 404);
        $fields = SiteContent::SECTIONS[$section][3];

        // Validation per field type. Inputs use dots → underscores (PHP turns "hero.title" into "hero_title").
        $rules = [];
        foreach ($fields as $key => [$label, $type]) {
            $name = self::input($key);
            $rules[$name] = match ($type) {
                'text' => ['nullable', 'string', 'max:200'],
                'textarea', 'lines' => ['nullable', 'string', 'max:2000'],
                'url' => ['nullable', 'string', 'max:300', 'regex:#^(https?://|/)#i'],
                'email' => ['nullable', 'email', 'max:150'],
                'date' => ['nullable', 'date_format:Y-m-d'],
                'image' => ['nullable', 'image', 'max:4096'],
                'toggle' => ['nullable', 'boolean'],
                'faq' => ['nullable', 'array', 'max:20'],
            };
            if ($type === 'faq') {
                $rules[$name.'.*.q'] = ['nullable', 'string', 'max:200'];
                $rules[$name.'.*.a'] = ['nullable', 'string', 'max:1000'];
            }
        }
        $request->validate($rules, ['*.regex' => 'Links must start with http://, https:// or /']);

        foreach ($fields as $key => [$label, $type]) {
            $name = self::input($key);
            match ($type) {
                'toggle' => Setting::put('content.'.$key, $request->boolean($name) ? '1' : '0'),
                'image' => $this->saveImage($request, $key, $name),
                'faq' => Setting::put('content.'.$key, json_encode(array_values(array_filter(
                    array_map(fn ($i) => ['q' => trim($i['q'] ?? ''), 'a' => trim($i['a'] ?? '')], $request->input($name, [])),
                    fn ($i) => $i['q'] !== '' && $i['a'] !== '',
                )), JSON_UNESCAPED_UNICODE)),
                default => Setting::put('content.'.$key, trim((string) $request->input($name, ''))),
            };
        }

        return redirect()->route('admin.content', ['tab' => $section])
            ->with('success', SiteContent::SECTIONS[$section][0].' saved. Changes are live on the website.');
    }

    /** Put a section back to the built-in defaults. */
    public function reset(string $section)
    {
        abort_unless(isset(SiteContent::SECTIONS[$section]), 404);
        foreach (SiteContent::SECTIONS[$section][3] as $key => [$label, $type]) {
            if ($type === 'image' && ($old = SiteContent::stored($key))) {
                Storage::disk('public')->delete($old);
            }
            Setting::where('key', 'content.'.$key)->delete();
        }
        Setting::put('content._touched', (string) now()->timestamp); // clears the settings cache

        return redirect()->route('admin.content', ['tab' => $section])
            ->with('success', SiteContent::SECTIONS[$section][0].' reset to the default content.');
    }

    private function saveImage(Request $request, string $key, string $name): void
    {
        $old = SiteContent::stored($key);
        if ($request->hasFile($name)) {
            Setting::put('content.'.$key, $request->file($name)->store(SiteContent::IMAGE_DIR, 'public'));
        } elseif ($request->boolean($name.'_reset')) {
            Setting::where('key', 'content.'.$key)->delete();
            Setting::put('content._touched', (string) now()->timestamp);
        } else {
            return;
        }
        if ($old) {
            Storage::disk('public')->delete($old);
        }
    }

    public static function input(string $key): string
    {
        return str_replace('.', '_', $key);
    }
}
