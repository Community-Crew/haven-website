<?php

namespace App\Http\Controllers\AdminDashboard;

use App\Http\Controllers\Controller;
use App\Models\RegistrationCode;
use App\Models\Unit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

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
        $unit = $registrationCode->unit()->first();

        $pdf = PDF::loadView('pdfs.label', ['regCode' => $registrationCode, 'unit' => $unit]);
        $pdfContent = $pdf->output();

        try {
            $agentUrl = config('printing.agent_url');
            $apiKey = config('printing.agent_api_key');

            $response = Http::withHeaders([
                'Content-Type' => 'application/pdf',
                'X-PRINT-API-KEY' => $apiKey,
            ])->timeout(10)->send('POST', $agentUrl, [
                'body' => $pdfContent
            ]);

            if (!$response->successful()) {
                Log::error('Print Agent Error: ' . $response->body());
                // Return with an error flash message
                return back()->with('error', 'Could not connect to the print agent.');
            }

        } catch (\Exception $e) {
            Log::error('Print Agent Connection Exception: ' . $e->getMessage());
            return back()->with('error', 'Error connecting to the print agent.');
        }

        // --- THIS IS THE KEY ---
        // Redirect back with a success flash message. Inertia will handle this gracefully.
        return back()->with('success', 'Print job sent to the label printer!');

    }
}
