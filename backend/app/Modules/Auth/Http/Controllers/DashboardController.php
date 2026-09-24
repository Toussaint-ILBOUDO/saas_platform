<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        $role = strtolower(session('active_role', ''));

        if (!$role) {
            return redirect()->route('role.select');
        }

        $allowedRoles = [
            'super-admin',
            'admin',
            'enseignant',
            'parent',
            'eleve',
        ];

        if (!in_array($role, $allowedRoles)) {
            return redirect()->route('role.select');
        }

        return view(
            "panel.dashboard.{$role}",
            [
                'hasMultipleRoles' => auth()->user()
                    ->getRoleNames()
                    ->count() > 1
            ]
        );
    }
}