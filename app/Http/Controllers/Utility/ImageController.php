<?php

namespace App\Http\Controllers\Utility;

use App\Http\Controllers\Controller;
use App\Http\Requests\Utility\StoreImageRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ImageController extends Controller
{
    public function store(StoreImageRequest $request): RedirectResponse
    {

        $file = $request->file('image');
        $filename = 'images/'.uniqid().'_'.'webp';

        $image = Image::read($file)
            ->scaleDown(width: 1200)
            ->toWebp(quality: 80);

        Storage::disk('local')->put($filename, (string) $image, 'public');

        return redirect()->back()->with('success', 'Image uploaded successfully');

    }
}
