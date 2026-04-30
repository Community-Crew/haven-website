<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreReservationPolicyEntryRequest;
use App\Models\ReservationPolicy;
use App\Models\ReservationPolicyEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReservationPolicyEntryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ReservationPolicy $reservationPolicy)
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(ReservationPolicy $reservationPolicy)
    {
        return Inertia::render('dashboard/reservationPolicy/entries/Create',
            ['reservationPolicy' => $reservationPolicy]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReservationPolicyEntryRequest $request, ReservationPolicy $reservationPolicy)
    {
        $validated = $request->validated();
        $reservationPolicy->reservationPolicyEntries()->create($validated);

        return redirect()->route('admin.reservation-policies.edit', $reservationPolicy->id)
            ->with('success', 'Rule created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ReservationPolicyEntry $reservationPolicyEntry, ReservationPolicy $reservationPolicy)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ReservationPolicyEntry $reservationPolicyEntry, ReservationPolicy $reservationPolicy)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ReservationPolicyEntry $reservationPolicyEntry, ReservationPolicy $reservationPolicy)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ReservationPolicy $reservation_policy, ReservationPolicyEntry $entry)
    {
        $entry->delete();

        return redirect()->route('admin.reservation-policies.edit', $reservation_policy->id)->with('success', 'Reservation Policy Entry has been deleted successfully.');
    }
}
