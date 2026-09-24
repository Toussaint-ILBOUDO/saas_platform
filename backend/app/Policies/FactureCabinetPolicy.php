<?php

namespace App\Policies;

use App\Models\FactureCabinet;
use App\Models\User;

class FactureCabinetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    public function view(User $user, FactureCabinet $factureCabinet): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super-admin');
    }

    public function update(User $user, FactureCabinet $factureCabinet): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    public function delete(User $user, FactureCabinet $factureCabinet): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    public function payer(User $user, FactureCabinet $factureCabinet): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin'])
            && $factureCabinet->statut !== 'annulee';
    }

    public function annuler(User $user, FactureCabinet $factureCabinet): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin'])
            && $factureCabinet->statut !== 'annulee';
    }
}
