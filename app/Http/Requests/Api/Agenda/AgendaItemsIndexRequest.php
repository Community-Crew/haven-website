<?php

namespace App\Http\Requests\Api\Agenda;

use Illuminate\Foundation\Http\FormRequest;

class AgendaItemsIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => 'nullable|array',
            'ids.*' => 'integer|exists:agendas,id',
            'page' => 'nullable|integer|min:1',
            'from_date' => 'nullable|date|date_format:Y-m-d',
        ];
    }
}
