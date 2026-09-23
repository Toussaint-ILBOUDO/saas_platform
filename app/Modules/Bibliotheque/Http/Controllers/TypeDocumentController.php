<?php

namespace App\Modules\Bibliotheque\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TypeDocument;
use App\Modules\Bibliotheque\Http\Requests\StoreTypeDocumentRequest;
use App\Modules\Bibliotheque\Http\Requests\UpdateTypeDocumentRequest;

class TypeDocumentController extends Controller
{
    public function index()
    {
        $typesDocument = TypeDocument::withCount('documents')
            ->orderBy('nom')
            ->paginate(15);

        return view('bibliotheque.type-documents.index', compact('typesDocument'));
    }

    public function create()
    {
        return view('bibliotheque.type-documents.create');
    }

    public function store(StoreTypeDocumentRequest $request)
    {
        TypeDocument::create($request->validated());

        return redirect()
            ->route('admin.bibliotheque.type-documents.index')
            ->with('success', 'Type de document créé avec succès.');
    }

    public function edit(TypeDocument $typeDocument)
    {
        return view('bibliotheque.type-documents.edit', compact('typeDocument'));
    }

    public function update(UpdateTypeDocumentRequest $request, TypeDocument $typeDocument)
    {
        $typeDocument->update($request->validated());

        return redirect()
            ->route('admin.bibliotheque.type-documents.index')
            ->with('success', 'Type de document modifié avec succès.');
    }

    public function destroy(TypeDocument $typeDocument)
    {
        $typeDocument->delete();

        return redirect()
            ->route('admin.bibliotheque.type-documents.index')
            ->with('success', 'Type de document supprimé.');
    }
}
