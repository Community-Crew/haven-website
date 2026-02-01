<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Enums\ReservationStatus;
use App\Http\Requests\Admin\StoreReservationRequest;
use App\Http\Requests\Admin\UpdateReservationRequest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use ReflectionEnum;

class ReservationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Reservation::class);

        $query = Reservation::query()->with('user', 'room');

        $query->whereDate('start_at', '>=', $request->input('date') ?: now());

        $query->when($request->input('room'), function ($query, $room) {
            $query->whereRelation('room', 'name', $room);
        });

        $query->when($request->input('status'), function ($query, $status) {
            $query->where('status', $status);
        });

        $query->orderBy('start_at');

        $reservations = $query->paginate(25)->withQueryString();
        $enumReflectionStatues = new ReflectionEnum(ReservationStatus::class);
        $statuses = array_map(fn($case) => $case->getValue()->value, $enumReflectionStatues->getCases());

        return Inertia::render('dashboard/reservations/Index',
            [
                'reservations' => $reservations,
                'filters' => $request->only('room', 'status', 'date'),
                'rooms' => Room::all()->pluck('name'),
                'statuses' => $statuses,

            ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('create', Reservation::class);

        return Inertia::render('dashboard/reservations/Create', ['rooms' => Room::all()]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReservationRequest $request): RedirectResponse
    {
        Gate::authorize('create', Reservation::class);
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        $startAt = Carbon::parse($validated['start_time'], 'Europe/Amsterdam');
        $endAt = Carbon::parse($validated['end_time'], 'Europe/Amsterdam');

        if (!$user) {
            return redirect()->route('admin.reservations.create')->with('error', 'No user with this email address.');
        }

        Reservation::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'room_id' => $validated['room'],
            'status' => 'approved',
            'start_at' => $startAt,
            'end_at' => $endAt,
        ]);

        return redirect()->route('admin.reservations.create')
            ->with('success', "Reservation created and linked to user: $user->name.");

    }

    /**
     * Display the specified resource.
     */
    public function show(Reservation $reservation): Response
    {
        Gate::authorize('view', $reservation);
        $reservation = $reservation->load('user', 'room');
        $enumReflectionStatues = new ReflectionEnum(ReservationStatus::class);
        $statuses = array_map(fn($case) => $case->getValue()->value, $enumReflectionStatues->getCases());

        return Inertia::render('dashboard/reservations/Show', ['reservation' => $reservation, 'rooms' => Room::all(), 'statuses' => $statuses]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Reservation $reservation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateReservationRequest $request, Reservation $reservation): RedirectResponse
    {
        Gate::authorize('update', $reservation);
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        $startAt = Carbon::parse($validated['start_time'], 'Europe/Amsterdam');
        $endAt = Carbon::parse($validated['end_time'], 'Europe/Amsterdam');

        if (!$user) {
            return redirect()->route('admin.reservations.create')->with('error', 'No user with this email address.');
        }

        $reservation->update([
            'name' => $validated['name'],
            'user_id' => $user->id,
            'room_id' => $validated['room'],
            'start_at' => $startAt,
            'end_at' => $endAt,
            'status' => $validated['status'],
        ]);

        return redirect()->route('admin.reservations.show', ['reservation' => $reservation]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reservation $reservation)
    {
        //
    }
}
