<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesMediaUploads;
use App\Http\Controllers\Controller;
use App\Models\CompanyContent;
use Illuminate\Http\{Request, RedirectResponse};
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Small repeatable content blocks, one table, told apart by `type`. Each type names its own
 * fields here so the admin form only shows what that block actually uses.
 */
class CompanyContentController extends Controller
{
    use HandlesMediaUploads;

    /** type => [list title, item noun, field labels (absent = field hidden)] */
    public const TYPES = [
        'hero_slide' => ['Homepage carousel', 'slide', ['title' => 'Place / slide title', 'subtitle' => 'Caption', 'description' => 'Alternative text — describes the photo for screen readers', 'image' => 'Slide image (wide photo, at least 1920 px)', 'url' => 'Link, e.g. /tours/niagara-falls']],
        'private_place' => ['Private tour places', 'place', ['title' => 'Place name', 'subtitle' => 'Short tagline', 'description' => 'Description', 'image' => 'Photo', 'url' => 'Link (optional)']],
        'team' => ['Team members', 'team member', ['title' => 'Name', 'subtitle' => 'Role', 'description' => 'Short bio', 'image' => 'Photo', 'pinned' => 'Always first (Founder / CEO)']],
        'partner' => ['Licences & partners', 'partner', ['title' => 'Short name', 'subtitle' => 'Full name', 'description' => 'Note', 'image' => 'Logo', 'url' => 'Link']],
        'statistic' => ['Statistics', 'statistic', ['title' => 'Label', 'value' => 'Value, e.g. 2,500+']],
        'advantage' => ['Advantages', 'advantage', ['title' => 'Title', 'description' => 'Description', 'image' => 'Icon / image', 'url' => 'Link']],
        'value' => ['Company values', 'value', ['title' => 'Title', 'description' => 'Description']],
        'licence' => ['Licences', 'licence', ['title' => 'Title', 'subtitle' => 'Number', 'description' => 'Note', 'image' => 'Image']],
        'private_step' => ['Private tour steps', 'step', ['title' => 'Title', 'description' => 'Description']],
        'visa_step' => ['Visa support steps', 'step', ['title' => 'Title', 'description' => 'Description']],
    ];

    public function index(Request $r): View
    {
        abort_unless($r->user()->can('list-company-content'), 403);
        $items = CompanyContent::with('translations')
            ->when($r->filled('q'), fn ($q) => $q->whereTranslationLike('title', '%'.$r->q.'%'))
            ->when($r->filled('type'), fn ($q) => $q->where('type', $r->type))
            ->when($r->filled('status'), fn ($q) => $q->where('is_active', $r->boolean('status')))
            ->orderBy('type')->orderByDesc('pinned')->orderBy('sort_order')->paginate(30)->withQueryString();

        return view('admin.company_content.index', ['items' => $items, 'types' => self::TYPES, 'type' => $this->type($r->type)]);
    }

    public function create(Request $r): View
    {
        abort_unless($r->user()->can('create-company-content'), 403);
        $item = new CompanyContent(['type' => $this->type($r->type) ?? 'team']);

        return view('admin.company_content.form', ['item' => $item, 'types' => self::TYPES]);
    }

    public function store(Request $r): RedirectResponse
    {
        abort_unless($r->user()->can('create-company-content'), 403);
        $item = CompanyContent::create($this->data($r));

        return redirect()->route('company-content.index', ['type' => $item->type])->with('message', 'Created.');
    }

    public function edit(Request $r, CompanyContent $company_content): View
    {
        abort_unless($r->user()->can('edit-company-content'), 403);

        return view('admin.company_content.form', ['item' => $company_content, 'types' => self::TYPES]);
    }

    public function update(Request $r, CompanyContent $company_content): RedirectResponse
    {
        abort_unless($r->user()->can('edit-company-content'), 403);
        $company_content->update($this->data($r, $company_content));

        return back()->with('message', 'Updated.');
    }

    public function destroy(Request $r, CompanyContent $company_content): RedirectResponse
    {
        abort_unless($r->user()->can('delete-company-content'), 403);
        $company_content->delete();

        return back()->with('message', 'Deleted.');
    }

    private function type(?string $type): ?string
    {
        return array_key_exists((string) $type, self::TYPES) ? $type : null;
    }

    private function data(Request $r, ?CompanyContent $item = null): array
    {
        $d = $r->validate($this->mediaRules('image') + [
            'type' => ['required', Rule::in(array_keys(self::TYPES))],
            'url' => ['nullable', 'string', 'max:500'],
            'value' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'pinned' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'en.title' => ['required', 'string', 'max:255'],
            'en.subtitle' => ['nullable', 'string', 'max:255'],
            'en.description' => ['nullable', 'string'],
        ]);
        $this->resolveMedia($r, $d, 'image', $item?->getRawOriginal('image'));
        $d['is_active'] = $r->boolean('is_active');
        $d['pinned'] = $r->boolean('pinned');
        // A new item goes to the end of its list unless an order is given.
        $d['sort_order'] = $r->filled('sort_order')
            ? (int) $d['sort_order']
            : ($item?->sort_order ?? (int) CompanyContent::where('type', $d['type'])->max('sort_order') + 1);

        return $d;
    }
}
