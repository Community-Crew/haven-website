<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

trait HasS3Image
{
    public function initializeS3Image()
    {
        $this->appends[] = 'image_url';
    }

    protected function imageUrl()
    {
        return Attribute::make(
            get: function () {
                if (empty($this->image_path)) {
                    return Storage::disk('hetzner')->temporaryUrl("placeholder.gif", now()->addMinutes(5));
                }

                return Storage::disk('hetzner')->temporaryUrl($this->image_path, now()->addMinutes(5));
            }
        );
    }
}
