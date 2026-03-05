<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\AgendaItem;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AgendaController extends Controller
{
    public function index(Request $request)
    {
        $agendas = Agenda::query()
            ->when(!$request->user(), function ($query) {
                $query->where('public', true);
            })
            ->get();
        return Inertia::render('agenda/Index', compact('agendas'), []);
    }

    public function show(Request $request, Agenda $agenda)
    {
        if (!($request->user() || $agenda->public)) {
            abort(404);
        }
        $agendaItems = $agenda->agendaItems()
            ->with(['agenda', 'organisation'])
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('agenda/Show', [
            'agenda' => $agenda,
            'agendaItems' => $agendaItems,
        ]);

    }
}
