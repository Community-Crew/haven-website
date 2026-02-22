<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AgendaItem;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $agendaItems = AgendaItem::query()
            ->select('agenda_items.*', 'agendas.slug as agenda_slug')
            ->with(['agenda', 'organisation'])
            ->where('start_date', '>=', now())
            ->join('agendas', 'agenda_items.agenda_id', '=', 'agendas.id')
            ->where(function ($query) use ($request) {
                $query->where('agendas.public', '=', true)
                    ->orWhere(function ($q) use ($request) {
                        if ($request->user()) {
                            $q->where('agendas.public', '=', false);
                        }
                    });
            })
            ->orderBy('start_date')
            ->limit(10)
            ->get();

        return Inertia::render('Home', ['agendaItems' => $agendaItems]);

    }
}
