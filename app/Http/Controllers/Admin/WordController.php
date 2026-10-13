<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Word;
use Illuminate\Http\{Request, RedirectResponse};
use Illuminate\View\View;

/**
 * Site copy lifted out of the blade templates by UiCopySeeder. Each row is matched against the
 * page by its stored source text, so the key and that source are internal bookkeeping — editors
 * only ever change the wording, and rows are never hand-created or deleted here.
 */
class WordController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:list-words')->only('index');
        $this->middleware('permission:edit-words')->only(['edit', 'update']);
    }

    public function index(Request $request): View
    {
        $words = Word::with('translations')
            ->when($request->filled('q'), fn ($query) => $query->whereTranslationLike('title', '%'.$request->string('q').'%'))
            ->orderBy('id')->paginate(30)->withQueryString();

        return view('admin.words.index', compact('words'));
    }

    public function edit(Word $word): View
    {
        return view('admin.words.form', compact('word'));
    }

    public function update(Request $request, Word $word): RedirectResponse
    {
        $word->update($request->validate($this->localeRules()));

        return back()->with('message', 'Text updated.');
    }

    /** One rule per configured locale, so adding a language needs no change here. */
    private function localeRules(): array
    {
        return collect(config('translatable.locales'))
            ->mapWithKeys(fn (string $locale) => ["$locale.title" => ['required', 'string', 'max:1000']])
            ->all();
    }
}
