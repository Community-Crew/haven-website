<?php

namespace App\Http\Controllers\AdminDashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function index(Response $response)
    {
        return Inertia::render('dashboard/Index', []);
    }
}
