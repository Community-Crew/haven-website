<?php

namespace App\Http\Controllers\Admin;

use Inertia\Response;
use Illuminate\Support\Facades\Gate;
use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UnitController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @throws AuthorizationException
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Unit::class);

        $query = Unit::query()->withCount('users as residents');

        $query->when($request->input('building'), function ($query, $building) {
            $query->where('building', $building);
        });

        $query->when($request->input('floor'), function ($query, $floor) {
            $query->where('floor', $floor);
        });

        $units = $query->paginate(30)->withQueryString();

        return Inertia::render('dashboard/units/Index', [
            'units' => $units,
            'filters' => $request->only('building', 'floor'),
            'buildings' => Unit::query()
                ->select('building')
                ->distinct()
                ->orderBy('building')
                ->pluck('building'),
            'floors' => Unit::query()
                ->where('building', $request->input('building'))
                ->select('floor')
                ->distinct()
                ->orderBy('floor')
                ->pluck('floor'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Unit $unit): Response
    {
        return Inertia::render('dashboard/units/Show', [
            'unit' => $unit,
            'users' => $unit->users,
            'registrationCodes' => $unit->registrationCodes,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Unit $unit)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Unit $unit)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Unit $unit)
    {
        //
    }
}
