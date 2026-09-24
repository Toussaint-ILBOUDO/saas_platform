<?php

namespace App\Modules\Landlord\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function index(): View
    {
        return view('landlord.dashboard', []);
    }
}