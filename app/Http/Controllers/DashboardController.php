<?php

namespace App\Http\Controllers;

use App\Services\Ralan\DashboardService;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $service) {}

    public function index()
    {
        return view('dashboard', $this->service->data());
    }
}
