<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreReservationPolicyRequest;
use App\Http\Requests\Admin\UpdateReservationPolicyRequest;
use App\Models\ReservationPolicy;
use App\Models\Room;
use Inertia\Inertia;

class ReservationPolicyController extends Controller
{
    public function index()
    {
        $reservationPolicies = ReservationPolicy::query()->paginate(20)->withQueryString()->through(fn($policy) => [
            'id' => $policy->id,
            'role_name' => $policy->role_name,
            'max_days_in_advance' => $policy->max_days_in_advance,
            'rooms' => $policy->rooms->pluck('name'),
        ]);

        return Inertia::render('dashboard/reservationPolicy/Index', [
            'policies' => $reservationPolicies
        ]);
    }

    public function edit(ReservationPolicy $reservationPolicy)
    {
        return Inertia::render('dashboard/reservationPolicy/Edit', [
            'reservationPolicy' => [
                'id' => $reservationPolicy->id,
                'role_name' => $reservationPolicy->role_name,
                'max_days_in_advance' => $reservationPolicy->max_days_in_advance,
                'room_ids' => $reservationPolicy->rooms()->pluck('rooms.id'),
            ],
            'rooms' => Room::all(),
            'reservationPolicyEntries' => $reservationPolicy->reservationPolicyEntries->sortBy('day_of_week')->values(),
        ]);
    }


    public function create()
    {
        return Inertia::render('dashboard/reservationPolicy/Create', ['rooms' => Room::all()]);
    }

    public function store(StoreReservationPolicyRequest $request)
    {
        $validated = $request->validated();

        $ReservationPolicy = ReservationPolicy::create([
            'role_name' => $validated['role_name'],
            'max_days_in_advance' => $validated['max_days_in_advance']
        ]);

        $ReservationPolicy->rooms()->sync($validated['room_ids']);

        return redirect()->route('admin.reservation-policies.index')->with('success', 'Policy created');
    }

    public function update(UpdateReservationPolicyRequest $request, ReservationPolicy $reservationPolicy)
    {
        $validated = $request->validated();
        $reservationPolicy->update([
            'role_name' => $validated['role_name'],
            'max_days_in_advance' => $validated['max_days_in_advance']
        ]);

        $reservationPolicy->rooms()->sync($validated['room_ids']);

        return redirect()->route('admin.reservation-policies.index')->with('success', 'Policy updated');
    }

    public function destroy(ReservationPolicy $reservationPolicy)
    {
        $reservationPolicy->delete();
        return redirect()->route('admin.reservation-policies.index')->with('success', 'Reservation Policy has been deleted successfully.');
    }
}
