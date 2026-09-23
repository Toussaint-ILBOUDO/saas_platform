<?php

namespace App\Policies;

use App\Models\FaqQuestion;
use App\Models\FaqSection;
use App\Models\User;

class FaqPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('faq.view');
    }

    public function view(User $user, FaqSection|FaqQuestion $model): bool
    {
        return $user->can('faq.view');
    }

    public function create(User $user): bool
    {
        return $user->can('faq.create');
    }

    public function update(User $user, FaqSection|FaqQuestion $model): bool
    {
        return $user->can('faq.update');
    }

    public function delete(User $user, FaqSection|FaqQuestion $model): bool
    {
        return $user->can('faq.delete');
    }
}
