<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function index(Request $request){
        $unit = $request->user()->unit()->first();
        return Inertia::render('Profile', ['unit' => $unit]);
    }
}
