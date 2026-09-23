<?php

namespace App\Modules\Communication\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FaqSection;
use App\Modules\Communication\Services\FaqService;
use App\Modules\Communication\Http\Requests\StoreFaqSectionRequest;
use App\Modules\Communication\Http\Requests\UpdateFaqSectionRequest;

class FaqSectionController extends Controller
{
    public function __construct(
        protected FaqService $service
    ) {}

    public function index()
    {
        $this->authorize('viewAny', FaqSection::class);

        $sections = $this->service->listSections();

        return view('communication.faq.sections.index', compact('sections'));
    }

    public function create()
    {
        $this->authorize('create', FaqSection::class);

        return view('communication.faq.sections.create');
    }

    public function store(StoreFaqSectionRequest $request)
    {
        $this->authorize('create', FaqSection::class);

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $this->service->createSection($data);

        return redirect()
            ->route('admin.faq.sections.index')
            ->with('success', 'Section FAQ créée avec succès.');
    }

    public function edit(FaqSection $section)
    {
        $this->authorize('update', $section);

        return view('communication.faq.sections.edit', compact('section'));
    }

    public function update(UpdateFaqSectionRequest $request, FaqSection $section)
    {
        $this->authorize('update', $section);

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $this->service->updateSection($section, $data);

        return redirect()
            ->route('admin.faq.sections.index')
            ->with('success', 'Section FAQ modifiée avec succès.');
    }

    public function toggle(FaqSection $section)
    {
        $this->authorize('update', $section);

        $this->service->toggleSection($section);

        return redirect()
            ->route('admin.faq.sections.index')
            ->with('success', $section->fresh()->is_active
                ? 'Section FAQ activée.'
                : 'Section FAQ désactivée.');
    }

    public function destroy(FaqSection $section)
    {
        $this->authorize('delete', $section);

        $this->service->deleteSection($section);

        return redirect()
            ->route('admin.faq.sections.index')
            ->with('success', 'Section FAQ supprimée.');
    }
}
