<?php

namespace App\Http\Controllers\Utility;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ImageController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:10240',
        ]);

        $file = $request->file('image');
        $filename = 'images/'.uniqid().'_'.'webp';

        $image = Image::read($file)
            ->scaleDown(width: 1200)
            ->toWebp(quality: 80);

        Storage::disk('local')->put($filename, (string) $image, 'public');

        return back()->with('success', 'Image uploaded successfully');

    }
}
