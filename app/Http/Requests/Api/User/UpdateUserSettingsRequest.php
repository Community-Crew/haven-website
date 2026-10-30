<?php

namespace App\Http\Requests\Api\User;

use App\Http\Requests\Api\BaseApiRequest;
use App\Models\User;
use Illuminate\Validation\Rule;

class UpdateUserSettingsRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Partial update - only the settings sent are changed, so the app can
     * sync a single toggle without knowing the others.
     */
    public function rules(): array
    {
        return [
            /**
             * Language for the app and for mails sent to this user.
             *
             * @example en
             */
            'locale' => ['sometimes', 'string', Rule::in(User::LOCALES)],
            /**
             * Colour scheme. 'system' follows the device setting.
             *
             * @example dark
             */
            'theme' => ['sometimes', 'string', Rule::in(User::THEMES)],
        ];
    }
}
