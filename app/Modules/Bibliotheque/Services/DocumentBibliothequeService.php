<?php

namespace App\Modules\Bibliotheque\Services;

use App\Models\DocumentBibliotheque;
use App\Models\DocumentCommentaire;
use App\Models\DocumentNote;
use App\Models\DocumentSignalement;
use App\Models\FavoriBibliotheque;
use App\Models\DocumentAccessLog;
use App\Models\Tag;
use App\Models\TypeDocument;
use App\Models\PeriodeDocument;
use App\Models\Classe;
use App\Models\Matiere;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DocumentBibliothequeService
{
    public function paginatePublic(array $filters = [], int $perPage = 12)
    {
        return DocumentBibliotheque::query()
            ->when(Auth::check(), function ($q) {
                $q->where('statut', 'publie');
            }, function ($q) {
                $q->public();
            })
            ->withFavoriForUser(Auth::id())
            ->with(['auteur', 'typeDocument', 'classe', 'matiere', 'periodeRef', 'tags', 'media'])
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->search($search);
            })
            ->when($filters['type_document_id'] ?? null, function ($q, $typeId) {
                $q->where('type_document_id', $typeId);
            })
            ->when($filters['classe_id'] ?? null, function ($q, $classeId) {
                $q->where('classe_id', $classeId);
            })
            ->when($filters['matiere_id'] ?? null, function ($q, $matiereId) {
                $q->where('matiere_id', $matiereId);
            })
            ->when($filters['periode_id'] ?? null, function ($q, $periodeId) {
                $q->where('periode_id', $periodeId);
            })
            ->when($filters['tag'] ?? null, function ($q, $tag) {
                $q->whereHas('tags', function ($q2) use ($tag) {
                    $q2->where('slug', $tag);
                });
            })
            ->when($filters['sort'] ?? null, function ($q, $sort) {
                match ($sort) {
                    'populaires' => $q->orderByDesc('nb_vues'),
                    'telecharges' => $q->orderByDesc('nb_telechargements'),
                    'notes' => $q->orderByDesc('note_moyenne'),
                    'recents' => $q->latest(),
                    default => $q->latest(),
                };
            }, function ($q) {
                $q->latest();
            })
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginateForUser(int $userId, array $filters = [], int $perPage = 15)
    {
        return DocumentBibliotheque::query()
            ->forUser($userId)
            ->withFavoriForUser($userId)
            ->with(['typeDocument', 'classe', 'matiere', 'tags', 'media'])
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->search($search);
            })
            ->when($filters['statut'] ?? null, function ($q, $statut) {
                $q->statut($statut);
            })
            ->when($filters['is_public'] ?? null, function ($q, $isPublic) {
                $q->where('is_public', $isPublic);
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginateAdmin(array $filters = [], int $perPage = 15)
    {
        return DocumentBibliotheque::query()
            ->with(['auteur', 'typeDocument', 'classe', 'matiere', 'periodeRef', 'media'])
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->search($search);
            })
            ->when($filters['statut'] ?? null, function ($q, $statut) {
                $q->statut($statut);
            })
            ->when($filters['user_id'] ?? null, function ($q, $userId) {
                $q->where('user_id', $userId);
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getById(int $id): DocumentBibliotheque
    {
        return DocumentBibliotheque::with([
            'auteur',
            'typeDocument',
            'classe',
            'matiere',
            'periodeRef',
            'tags',
            'commentaires.user',
            'commentaires.replies.user',
            'notes',
            'media',
        ])
        ->withFavoriForUser(Auth::id())
        ->findOrFail($id);
    }

    public function getBySlug(string $slug): DocumentBibliotheque
    {
        return DocumentBibliotheque::with([
            'auteur',
            'typeDocument',
            'classe',
            'matiere',
            'periodeRef',
            'tags',
            'commentaires.user',
            'commentaires.replies.user',
            'media',
        ])
        ->withFavoriForUser(Auth::id())
        ->where('slug', $slug)->firstOrFail();
    }

    public function create(array $data, $file = null): DocumentBibliotheque
    {
        return DB::transaction(function () use ($data, $file) {
            $document = DocumentBibliotheque::create([
                'user_id' => Auth::id(),
                'titre' => $data['titre'],
                'description' => $data['description'] ?? null,
                'resume' => $data['resume'] ?? null,
                'type_document_id' => $data['type_document_id'],
                'classe_id' => $data['classe_id'] ?? null,
                'matiere_id' => $data['matiere_id'] ?? null,
                'periode_id' => $data['periode_id'] ?? null,
                'is_public' => $data['is_public'] ?? false,
                'statut' => $data['statut'] ?? 'brouillon',
            ]);

            if (!empty($data['tags']) && is_array($data['tags'])) {
                $this->syncTags($document, $data['tags']);
            }

            if ($file) {
                $this->attachFile($document, $file);
            }

            return $document;
        });
    }

    public function update(DocumentBibliotheque $document, array $data, $file = null): DocumentBibliotheque
    {
        return DB::transaction(function () use ($document, $data, $file) {
            $document->update([
                'titre' => $data['titre'] ?? $document->titre,
                'description' => $data['description'] ?? $document->description,
                'resume' => $data['resume'] ?? $document->resume,
                'type_document_id' => $data['type_document_id'] ?? $document->type_document_id,
                'classe_id' => $data['classe_id'] ?? $document->classe_id,
                'matiere_id' => $data['matiere_id'] ?? $document->matiere_id,
                'periode_id' => $data['periode_id'] ?? $document->periode_id,
                'is_public' => $data['is_public'] ?? $document->is_public,
                'statut' => $data['statut'] ?? $document->statut,
            ]);

            if (isset($data['tags']) && is_array($data['tags'])) {
                $this->syncTags($document, $data['tags']);
            }

            if ($file) {
                $document->clearMediaCollection('document');
                $this->attachFile($document, $file);
            }

            return $document->refresh();
        });
    }

    public function delete(DocumentBibliotheque $document): bool
    {
        return DB::transaction(function () use ($document) {
            $document->clearMediaCollection('document');
            return $document->delete();
        });
    }

    public function valider(DocumentBibliotheque $document): DocumentBibliotheque
    {
        $document->update(['statut' => 'publie']);
        return $document->refresh();
    }

    public function refuser(DocumentBibliotheque $document): DocumentBibliotheque
    {
        $document->update(['statut' => 'refuse']);
        return $document->refresh();
    }

    public function archiver(DocumentBibliotheque $document): DocumentBibliotheque
    {
        $document->update(['statut' => 'archive']);
        return $document->refresh();
    }

    public function toggleFavori(DocumentBibliotheque $document): bool
    {
        $existant = FavoriBibliotheque::where('user_id', Auth::id())
            ->where('document_bibliotheque_id', $document->id)
            ->first();

        if ($existant) {
            $existant->delete();
            $document->decrement('nombre_favoris');
            return false;
        }

        FavoriBibliotheque::create([
            'user_id' => Auth::id(),
            'document_bibliotheque_id' => $document->id,
        ]);
        $document->increment('nombre_favoris');
        return true;
    }

    public function getFavoris(int $userId, array $filters = [], int $perPage = 15)
    {
        return DocumentBibliotheque::query()
            ->whereHas('favoris', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->withFavoriForUser($userId)
            ->with(['auteur', 'typeDocument', 'classe', 'matiere', 'periodeRef', 'tags', 'media'])
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->search($search);
            })
            ->when($filters['type_document_id'] ?? null, function ($q, $typeId) {
                $q->where('type_document_id', $typeId);
            })
            ->when($filters['classe_id'] ?? null, function ($q, $classeId) {
                $q->where('classe_id', $classeId);
            })
            ->when($filters['matiere_id'] ?? null, function ($q, $matiereId) {
                $q->where('matiere_id', $matiereId);
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function addNote(DocumentBibliotheque $document, int $note): void
    {
        if ($note < 1 || $note > 5) {
            throw ValidationException::withMessages([
                'note' => 'La note doit être entre 1 et 5.',
            ]);
        }

        DocumentNote::updateOrCreate(
            ['user_id' => Auth::id(), 'document_bibliotheque_id' => $document->id],
            ['note' => $note]
        );

        $stats = DocumentNote::where('document_bibliotheque_id', $document->id)
            ->selectRaw('AVG(note) as moyenne, COUNT(*) as total')
            ->first();

        $document->update([
            'note_moyenne' => round($stats->moyenne, 2),
            'nb_notes' => $stats->total,
        ]);
    }

    public function addCommentaire(DocumentBibliotheque $document, array $data): DocumentCommentaire
    {
        return DocumentCommentaire::create([
            'user_id' => Auth::id(),
            'document_bibliotheque_id' => $document->id,
            'parent_id' => $data['parent_id'] ?? null,
            'contenu' => $data['contenu'],
        ]);
    }

    public function addSignalement(DocumentBibliotheque $document, array $data): DocumentSignalement
    {
        $existant = DocumentSignalement::where('user_id', Auth::id())
            ->where('document_bibliotheque_id', $document->id)
            ->where('statut', 'en_attente')
            ->exists();

        if ($existant) {
            throw ValidationException::withMessages([
                'motif' => 'Vous avez déjà un signalement en cours pour ce document.',
            ]);
        }

        return DocumentSignalement::create([
            'user_id' => Auth::id(),
            'document_bibliotheque_id' => $document->id,
            'motif' => $data['motif'],
            'description' => $data['description'] ?? null,
        ]);
    }

    public function logAccess(DocumentBibliotheque $document, string $action): void
    {
        DocumentAccessLog::create([
            'user_id' => Auth::check() ? Auth::id() : null,
            'document_bibliotheque_id' => $document->id,
            'action' => $action,
            'ip_address' => request()->ip(),
        ]);

        if ($action === 'view') {
            $document->increment('nb_vues');
        } elseif ($action === 'download') {
            $document->increment('nb_telechargements');
        }
    }

    public function getAuteurStats(int $userId): array
    {
        $documents = DocumentBibliotheque::where('user_id', $userId);

        return [
            'total' => (clone $documents)->count(),
            'brouillons' => (clone $documents)->where('statut', 'brouillon')->count(),
            'en_attente' => (clone $documents)->where('statut', 'en_attente')->count(),
            'publies' => (clone $documents)->where('statut', 'publie')->count(),
            'refuses' => (clone $documents)->where('statut', 'refuse')->count(),
            'archives' => (clone $documents)->where('statut', 'archive')->count(),
            'total_vues' => (clone $documents)->sum('nb_vues'),
            'total_telechargements' => (clone $documents)->sum('nb_telechargements'),
            'total_favoris' => FavoriBibliotheque::whereHas('document', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })->count(),
            'note_moyenne' => (clone $documents)->where('nb_notes', '>', 0)->avg('note_moyenne') ?? 0,
            'derniers_commentaires' => DocumentCommentaire::whereHas('document', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })->with('user', 'document')->latest()->take(5)->get(),
        ];
    }

    public function getAdminStats(): array
    {
        return [
            'total' => DocumentBibliotheque::count(),
            'en_attente' => DocumentBibliotheque::where('statut', 'en_attente')->count(),
            'publies' => DocumentBibliotheque::where('statut', 'publie')->count(),
            'refuses' => DocumentBibliotheque::where('statut', 'refuse')->count(),
            'signalements_en_attente' => DocumentSignalement::where('statut', 'en_attente')->count(),
        ];
    }

    public function getHomepageStats(): array
    {
        return [
            'total_documents' => DocumentBibliotheque::query()
                ->when(Auth::check(), fn($q) => $q->where('statut', 'publie'), fn($q) => $q->public())
                ->count(),
            'total_vues' => DocumentBibliotheque::sum('nb_vues'),
            'total_telechargements' => DocumentBibliotheque::sum('nb_telechargements'),
        ];
    }

    public function getTypesDocument()
    {
        return TypeDocument::orderBy('nom')->get();
    }

    public function getPeriodes()
    {
        return PeriodeDocument::orderBy('nom')->get();
    }

    public function getClasses()
    {
        return Classe::orderBy('nom')->get();
    }

    public function getMatieres()
    {
        return Matiere::orderBy('nom')->get();
    }

    public function getTagsPopulaires(int $limit = 20)
    {
        return Tag::orderByDesc('usage_count')->take($limit)->get();
    }

    public function getDocumentsRecents(int $limit = 6)
    {
        return DocumentBibliotheque::query()
            ->when(Auth::check(), function ($q) {
                $q->where('statut', 'publie');
            }, function ($q) {
                $q->public();
            })
            ->withFavoriForUser(Auth::id())
            ->with(['auteur', 'typeDocument', 'classe', 'matiere', 'periodeRef', 'media'])
            ->latest()
            ->take($limit)
            ->get();
    }

    public function getDocumentsPopulaires(int $limit = 6)
    {
        return DocumentBibliotheque::query()
            ->when(Auth::check(), function ($q) {
                $q->where('statut', 'publie');
            }, function ($q) {
                $q->public();
            })
            ->withFavoriForUser(Auth::id())
            ->with(['auteur', 'typeDocument', 'classe', 'matiere', 'periodeRef', 'media'])
            ->orderByDesc('nb_vues')
            ->take($limit)
            ->get();
    }

    public function getDocumentsMieuxNotes(int $limit = 6)
    {
        return DocumentBibliotheque::query()
            ->when(Auth::check(), function ($q) {
                $q->where('statut', 'publie');
            }, function ($q) {
                $q->public();
            })
            ->withFavoriForUser(Auth::id())
            ->with(['auteur', 'typeDocument', 'classe', 'matiere', 'periodeRef', 'media'])
            ->where('nb_notes', '>', 0)
            ->orderByDesc('note_moyenne')
            ->take($limit)
            ->get();
    }

    public function getDocumentsPlusTelecharges(int $limit = 6)
    {
        return DocumentBibliotheque::query()
            ->when(Auth::check(), function ($q) {
                $q->where('statut', 'publie');
            }, function ($q) {
                $q->public();
            })
            ->withFavoriForUser(Auth::id())
            ->with(['auteur', 'typeDocument', 'classe', 'matiere', 'periodeRef', 'media'])
            ->orderByDesc('nb_telechargements')
            ->take($limit)
            ->get();
    }

    public function getDocumentsSimilaires(DocumentBibliotheque $document, int $limit = 4)
    {
        return DocumentBibliotheque::query()
            ->when(Auth::check(), function ($q) {
                $q->where('statut', 'publie');
            }, function ($q) {
                $q->public();
            })
            ->withFavoriForUser(Auth::id())
            ->with(['auteur', 'typeDocument', 'classe', 'matiere', 'periodeRef', 'media'])
            ->where('id', '!=', $document->id)
            ->where(function ($q) use ($document) {
                $q->where('matiere_id', $document->matiere_id)
                    ->orWhere('classe_id', $document->classe_id)
                    ->orWhere('type_document_id', $document->type_document_id);
            })
            ->take($limit)
            ->get();
    }

    private function syncTags(DocumentBibliotheque $document, array $tagNames): void
    {
        $tagIds = [];
        foreach ($tagNames as $tagName) {
            $tagName = trim($tagName);
            if (empty($tagName)) continue;

            $tag = Tag::firstOrCreate(
                ['slug' => Str::slug($tagName)],
                ['nom' => $tagName]
            );
            $tag->increment('usage_count');
            $tagIds[] = $tag->id;
        }

        $document->tags()->sync($tagIds);
    }

    private function attachFile(DocumentBibliotheque $document, $file): void
    {
        $document->addMedia($file)
            ->usingName($file->getClientOriginalName())
            ->usingFileName($file->getClientOriginalName())
            ->toMediaCollection('document');
    }
}
