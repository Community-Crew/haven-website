<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\AgendaItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AgendaItemController extends Controller
{
    public function index()
    {

    }

    public function show(Request $request, Agenda $agenda, AgendaItem $agendaItem): Response
    {
        if (!($request->user() || $agenda->public)){
            abort(404);
        }

        $agendaItem->load(['agenda', 'organisation']);
        return Inertia::render('agendaItems/Show', ['agenda' => $agenda, 'agendaItem' => $agendaItem]);
    }
}
