<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAgendaItemRequest;
use App\Http\Requests\Admin\UpdateAgendaItemRequest;
use App\Models\Agenda;
use App\Models\AgendaItem;
use App\Models\Organisation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AgendaItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Agenda $agenda)
    {

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Agenda $agenda, Request $request): Response
    {
        Gate::authorize('create', AgendaItem::class);
        return Inertia::render('dashboard/agendaItems/Create',
            [
                'agenda' => $agenda,
                'organisations' => $request->user()->organisations()->get()
            ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Agenda $agenda, StoreAgendaItemRequest $request)
    {
        Gate::authorize('create', AgendaItem::class);
        $validatedData = $request->validated();

        $organisation = Organisation::findorFail($validatedData['organisation']);
        if (!$organisation->users()->where('user_id', $request->user()->id)->exists()
        ) {
            return redirect()->back()->withErrors(['membership' => 'You do not have permission to add items to this agenda.']);
        }

        if ($request->hasFile('image')) {
            $validatedData['image'] = $request->file('image')->store('images', 'public');
        }

        $path = $request->file('image')->store('rooms', 'hetzner');

        $agendaItem = AgendaItem::create([
            'title' => $validatedData['title'],
            'description' => $validatedData['description'],
            'short_description' => $validatedData['shortDescription'],
            'image_path' => $path,
            'start_date' => $validatedData['start_date'],
            'end_date' => $validatedData['end_date'],
            'user_id' => $request->user()->id,
            'organisation_id' => $organisation->id,
            'agenda_id' => $agenda->id,

        ]);

        return redirect()
            ->to(route('admin.agendas.items.show', ['agenda' => $agenda, 'agendaItem' => $agendaItem]))
            ->with('success', 'Agenda item created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Agenda $agenda, AgendaItem $agendaItem)
    {
        Gate::authorize('view', $agendaItem);
        $agendaItem->load('organisation', 'agenda');
        return Inertia::render('dashboard/agendaItems/Show', ['agendaItem' => $agendaItem]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Agenda $agenda, AgendaItem $agendaItem)
    {
        Gate::authorize('update', $agendaItem);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Agenda $agenda, UpdateAgendaItemRequest $request, AgendaItem $agendaItem)
    {
        Gate::authorize('update', $agendaItem);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Agenda $agenda, AgendaItem $agendaItem)
    {
        Gate::authorize('delete', $agendaItem);
        $agendaItem->delete();
        return redirect()->to(Route('admin.agendas.show', [$agenda->slug]))->with('success', 'Agenda item deleted successfully!');
    }
}
