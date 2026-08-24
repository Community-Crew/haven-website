<?php

namespace App\Http\Controllers\Api\Agenda;

use App\Http\Resources\Api\AgendaResource;
use App\Models\Agenda;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;

/**
 * View an agenda
 *
 * Retrieve a single agenda with its items via its slug. Non-public agendas
 * are only visible to authenticated users.
 *
 * @param  Agenda  $agenda
 * @return AgendaResource
 */
class AgendaShowController
{
    #[Group('Agenda')]
    public function __invoke(Request $request, Agenda $agenda): AgendaResource
    {
        if (! $agenda->public && ! $request->user()) {
            abort(404);
        }

        $agenda->load(['items' => function ($query) {
            $query->orderBy('start_date', 'asc');
        }]);

        return new AgendaResource($agenda);
    }
}
