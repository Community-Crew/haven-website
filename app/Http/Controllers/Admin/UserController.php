<?php

namespace App\Http\Controllers\Admin;


use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use ReflectionEnum;

class UserController extends Controller
{
    use AuthorizesRequests;

    public function index(): Response
    {
        Gate::authorize('viewAny', User::class);
        $users = User::query()->orderBy('name')->paginate(10)->withQueryString();

        return Inertia::Render('dashboard/users/Index', ['users' => $users]);
    }

    public function show(Request $request, User $user): Response
    {
        Gate::authorize('view', $user);

        $user->load('groups');
        $user->load('organisations');


        $reservations = $user->reservations()
            ->with('organisation')
            ->when($request->filled('date'), function ($query) use ($request) {
                $query->whereDate('reservations.start_at', '>=', $request->input('date'));
            }, function ($query) {
                $query->whereDate('reservations.start_at', '>=', now()->today());
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('reservations.status', $request->input('status'));
            })
            ->when($request->filled('room'), function ($query) use ($request) {
                $query->where('reservations.room_id', $request->input('room'));
            })
            ->orderBy('reservations.start_at')
            ->paginate(10)->withQueryString();

        $enumReflectionStatues = new ReflectionEnum(ReservationStatus::class);
        $statuses = array_map(fn($case) => $case->getValue()->value, $enumReflectionStatues->getCases());
        return Inertia::Render('dashboard/users/Show',
            [
                'target_user' => $user,
                'reservations' => $reservations,
                'filters' => $request->only('room', 'status', 'date'),
                'rooms' => Room::all(),
                'statuses' => $statuses,

            ]);
    }

    public function edit(User $user)
    {
        Gate::authorize('update', $user);
        return Inertia::Render('dashboard/users/Edit', ['target_user' => $user]);
    }
}
