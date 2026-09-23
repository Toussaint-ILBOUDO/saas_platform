<?php

namespace App\Modules\Communication\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FaqQuestion;
use App\Models\FaqSection;
use App\Modules\Communication\Services\FaqService;
use App\Modules\Communication\Http\Requests\StoreFaqQuestionRequest;
use App\Modules\Communication\Http\Requests\UpdateFaqQuestionRequest;

class FaqQuestionController extends Controller
{
    public function __construct(
        protected FaqService $service
    ) {}

    public function index(FaqSection $section)
    {
        $this->authorize('viewAny', FaqQuestion::class);

        $questions = $this->service->listQuestions($section);

        return view('communication.faq.questions.index', compact('section', 'questions'));
    }

    public function create(FaqSection $section)
    {
        $this->authorize('create', FaqQuestion::class);

        $sections = FaqSection::orderBy('order_index')->get();

        return view('communication.faq.questions.create', compact('section', 'sections'));
    }

    public function store(StoreFaqQuestionRequest $request, FaqSection $section)
    {
        $this->authorize('create', FaqQuestion::class);

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $this->service->createQuestion($data);

        return redirect()
            ->route('admin.faq.questions.index', $section)
            ->with('success', 'Question ajoutée avec succès.');
    }

    public function edit(FaqSection $section, FaqQuestion $question)
    {
        $this->authorize('update', $question);

        $sections = FaqSection::orderBy('order_index')->get();

        return view('communication.faq.questions.edit', compact('section', 'question', 'sections'));
    }

    public function update(UpdateFaqQuestionRequest $request, FaqSection $section, FaqQuestion $question)
    {
        $this->authorize('update', $question);

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $this->service->updateQuestion($question, $data);

        return redirect()
            ->route('admin.faq.questions.index', $section)
            ->with('success', 'Question modifiée avec succès.');
    }

    public function toggle(FaqSection $section, FaqQuestion $question)
    {
        $this->authorize('update', $question);

        $this->service->toggleQuestion($question);

        return redirect()
            ->route('admin.faq.questions.index', $section)
            ->with('success', $question->fresh()->is_active
                ? 'Question activée.'
                : 'Question désactivée.');
    }

    public function destroy(FaqSection $section, FaqQuestion $question)
    {
        $this->authorize('delete', $question);

        $this->service->deleteQuestion($question);

        return redirect()
            ->route('admin.faq.questions.index', $section)
            ->with('success', 'Question supprimée.');
    }
}
