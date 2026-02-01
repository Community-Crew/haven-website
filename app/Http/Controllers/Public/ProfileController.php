<?php

namespace App\Http\Controllers\Public;

use Inertia\Response;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function index(Request $request): Response
    {
        $unit = $request->user()->unit()->first();
        $groups = $request->user()->groups()->pluck('name')->join(', ');
        $organisations = $request->user()->organisations;

        $reservations = $request->user()
            ->reservations()
            ->orderByDesc('start_at')
            ->where('start_at', '>=', now())
            ->with(['room', 'organisation'])
            ->paginate(6)
            ->withQueryString();

        return Inertia::render('Profile',
            [
                'unit' => $unit,
                'groups' => $groups,
                'reservations' => $reservations,
                'organisations' => $organisations,
            ]);
    }
}
