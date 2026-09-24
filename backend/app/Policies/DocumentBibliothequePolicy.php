<?php

namespace App\Policies;

use App\Models\User;
use App\Models\DocumentBibliotheque;

class DocumentBibliothequePolicy
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
        return true;
    }

    public function view(User $user, DocumentBibliotheque $document): bool
    {
        if ($document->statut == 'publie') {
            return true;
        }

        if ($document->user_id == $user->id) {
            return true;
        }

        if ($user->can('bibliotheque.moderate')) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('bibliotheque.create');
    }

    public function update(User $user, DocumentBibliotheque $document): bool
    {
        if ($user->hasAnyRole(['admin', 'super-admin'])) {
            return true;
        }

        return $document->user_id == $user->id
            && in_array($document->statut, ['brouillon', 'refuse']);
    }

    public function delete(User $user, DocumentBibliotheque $document): bool
    {
        if ($user->hasAnyRole(['admin', 'super-admin'])) {
            return true;
        }

        return $document->user_id == $user->id
            && in_array($document->statut, ['brouillon', 'refuse']);
    }

    public function moderate(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super-admin'])
            || $user->can('bibliotheque.moderate');
    }

    public function download(User $user, DocumentBibliotheque $document): bool
    {
        return $this->view($user, $document);
    }

    public function comment(User $user, DocumentBibliotheque $document): bool
    {
        return $this->view($user, $document);
    }

    public function favorite(User $user, DocumentBibliotheque $document): bool
    {
        return $this->view($user, $document);
    }

    public function rate(User $user, DocumentBibliotheque $document): bool
    {
        return $this->view($user, $document);
    }

    public function report(User $user, DocumentBibliotheque $document): bool
    {
        return $document->user_id !== $user->id;
    }
}
