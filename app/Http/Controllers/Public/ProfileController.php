<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function index(Request $request){
        $unit = $request->user()->unit()->first();
        $groups = $request->user()->groups()->pluck('name')->join(', ');

        $reservations = $request->user()
            ->reservations()
            ->orderBy('start_at', 'desc')
            ->where('start_at', '>=', now())
            ->with('room')
            ->paginate(5)
            ->withQueryString();

        return Inertia::render('Profile', ['unit' => $unit, 'groups' => $groups, 'reservations' => $reservations]);
    }
}
