<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRegistrationCodeRequest;
use App\Models\RegistrationCode;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationCodeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', RegistrationCode::class);

        $building = $request->get('building');
        $floor = $request->get('floor');
        $units = Unit::with('registrationCodes')
            ->where('building', $building)
            ->where('floor', $floor)
            ->get();

        return Inertia::render('dashboard/registrationCodes/Index', ['units' => $units]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', RegistrationCode::class);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRegistrationCodeRequest $request): RedirectResponse
    {
        Gate::authorize('create', RegistrationCode::class);
        $validated = $request->validated();

        $unit = Unit::findOrFail($validated['unit_id']);

        $unit->registrationCodes()->create();

        return redirect()->back()->with('success', 'New Registration Code has been created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(RegistrationCode $registrationCode): Response
    {
        Gate::authorize('view', $registrationCode);
        $unit = $registrationCode->unit()->first();

        return Inertia::render('dashboard/registrationCodes/Show', ['regCode' => $registrationCode, 'unit' => $unit]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RegistrationCode $registrationCode)
    {
        Gate::authorize('update', $registrationCode);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RegistrationCode $registrationCode)
    {
        Gate::authorize('update', $registrationCode);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RegistrationCode $registrationCode): RedirectResponse
    {
        Gate::authorize('delete', $registrationCode);
        $registrationCode->delete();

        return redirect()->back()->with('success', 'Registration Code has been deleted.');
    }

    /**
     * Print the registration code.
     */
    public function print(RegistrationCode $registrationCode)
    {
        Gate::authorize('print', $registrationCode);
    }
}
