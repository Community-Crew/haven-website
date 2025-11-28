<?php

namespace App\Http\Controllers\AdminDashboard;

use App\Http\Controllers\Controller;
use App\Models\RegistrationCode;
use App\Models\Unit;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Rawilk\Printing\Facades\Printing;

class RegistrationCodeController extends Controller
{
    use AuthorizesRequests;


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', RegistrationCode::class);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', RegistrationCode::class);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', RegistrationCode::class);
        $validated = $request->validate([
            'unit_id' => 'required|exists:units,id',
        ]);

        $unit = Unit::findOrFail($validated['unit_id']);

        $unit->registrationCodes()->create();
        return Redirect::back()->with('success', 'New Registration Code has been created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(RegistrationCode $registrationCode)
    {
        $this->authorize('view', $registrationCode);
        $unit = $registrationCode->unit()->first();
        return Inertia::render('dashboard/registrationCodes/Show', ['regCode' => $registrationCode, 'unit' => $unit]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RegistrationCode $registrationCode)
    {
        $this->authorize('update', $registrationCode);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RegistrationCode $registrationCode)
    {
        $this->authorize('update', $registrationCode);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RegistrationCode $registrationCode)
    {
        $this->authorize('delete', $registrationCode);
        $registrationCode->delete();
        return Redirect::back()->with('success', 'Registration Code has been deleted.');
    }

    /**
     * Print the registration code.
     */
    public function print(RegistrationCode $registrationCode)
    {
        $this->authorize('print', $registrationCode);
        $printers = Printing::printers();
        @dd($printers);

        Printing::newPrintTask()
            ->printer(Printing::defaultPrinter())
            ->url(route('admin.registration-codes.show', $registrationCode))
            ->jobTitle($registrationCode['code'])
            ->send();
    }
}
