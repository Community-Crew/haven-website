<?php

namespace App\Http\Controllers\Api\Agenda;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Agenda\AgendaItemsIndexRequest;
use App\Http\Resources\Api\AgendaResource;
use App\Models\Agenda;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AgendaIndexController extends Controller
{
    /**
     * List all agendas.
     *
     * Fetch a list of all agendas with bundled items. If the user is authenticated, non-public agendas will also be shown.
     */
    #[Group('Agenda')]
    public function __invoke(AgendaItemsIndexRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        $fromDate = $validated['from_date'] ?? now()->subDay()->toDateString();

        $agendas = Agenda::query()
            ->with(['items' => function ($query) use ($fromDate) {
                $query->where('start_date', '>=', $fromDate)
                    ->orderBy('start_date', 'asc');
            }])
            ->when(! $request->user(), function ($query) {
                $query->where('public', true);
            })
            ->whereHas('items', function ($query) use ($fromDate) {
                $query->where('start_date', '>=', $fromDate);
            })
            ->when(! empty($validated['ids']), function ($query) use ($validated) {
                $query->whereIn('id', $validated['ids']);
            })
            ->simplePaginate(10);

        $resourceCollection = AgendaResource::collection($agendas);
        $resourceCollection->toResponse($request);

        return $resourceCollection;
    }
}
