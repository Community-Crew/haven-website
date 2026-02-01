<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Response $response): \Inertia\Response
    {
        return Inertia::render('dashboard/Index', []);
    }
}
