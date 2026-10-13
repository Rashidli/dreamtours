<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesMediaUploads;
use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\TourCategory;
use App\Models\Transportation;
use App\Models\PickupPoint;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TourCatalogueController extends Controller
{
    use HandlesMediaUploads;

    private const MAP = [
        'categories' => [TourCategory::class, 'tour-categories', ['type']],
        'destinations' => [Destination::class, 'destinations', ['type', 'parent_id']],
        'transportations' => [Transportation::class, 'transportations', ['type', 'seat_count']],
        'pickup-points' => [PickupPoint::class, 'pickup-points', ['address', 'map_url']],
    ];

    public function index(Request $request, string $resource): View
    {
        [$class, $permission] = $this->definition($resource);
        abort_unless($request->user()->can('list-'.$permission), 403);
        $items = $class::query()->with('translations')
            ->when($request->filled('q'), fn($q) => $q->whereTranslationLike('name', '%'.$request->string('q').'%'))
            ->when($request->filled('status'), fn($q) => $q->where('is_active', $request->boolean('status')))
            ->orderBy('sort_order')->paginate(20)->withQueryString();
        return view('admin.tour_catalogue.index', compact('items', 'resource', 'permission'));
    }

    public function create(Request $request, string $resource): View
    {
        [$class, $permission] = $this->definition($resource); abort_unless($request->user()->can('create-'.$permission), 403);
        return $this->form($resource, new $class, $permission);
    }

    public function store(Request $request, string $resource): RedirectResponse
    {
        [$class, $permission] = $this->definition($resource); abort_unless($request->user()->can('create-'.$permission), 403);
        $class::create($this->validated($request, $resource));
        return redirect()->route('tour-catalogue.index', $resource)->with('message', 'Item created successfully.');
    }

    public function edit(Request $request, string $resource, int $item): View
    {
        [$class, $permission] = $this->definition($resource); abort_unless($request->user()->can('edit-'.$permission), 403);
        return $this->form($resource, $class::findOrFail($item), $permission);
    }

    public function update(Request $request, string $resource, int $item): RedirectResponse
    {
        [$class, $permission] = $this->definition($resource); abort_unless($request->user()->can('edit-'.$permission), 403);
        $model=$class::findOrFail($item); $model->update($this->validated($request, $resource, $model));
        return back()->with('message', 'Item updated successfully.');
    }

    public function destroy(Request $request, string $resource, int $item): RedirectResponse
    {
        [$class, $permission] = $this->definition($resource); abort_unless($request->user()->can('delete-'.$permission), 403);
        $class::findOrFail($item)->delete(); return back()->with('message', 'Item deleted successfully.');
    }

    private function form(string $resource, Model $item, string $permission): View
    {
        $parents=$resource==='destinations'?Destination::whereNull('parent_id')->whereKeyNot($item->id)->withTranslation()->get():collect();
        return view('admin.tour_catalogue.form', compact('resource','item','permission','parents'));
    }

    private function definition(string $resource): array { abort_unless(isset(self::MAP[$resource]),404); return self::MAP[$resource]; }

    private function validated(Request $request, string $resource, ?Model $item=null): array
    {
        $rules=['type'=>['required','string','max:80'],'is_active'=>['nullable','boolean'],'sort_order'=>['nullable','integer','min:0']]+$this->mediaRules('image');
        // One block of rules per configured locale, so adding a language needs no change here.
        foreach(config('translatable.locales') as $locale){ $rules["$locale.name"]=['required','string','max:255']; $rules["$locale.description"]=['nullable','string']; }
        if($resource==='destinations') $rules['parent_id']=['nullable','exists:destinations,id'];
        if($resource==='transportations') $rules['seat_count']=['nullable','integer','min:1'];
        if($resource==='pickup-points') { unset($rules['type'], $rules['image'], $rules['image_path']); $rules['address']=['required','string','max:255']; $rules['map_url']=['nullable','url','max:500'];
            foreach(config('translatable.locales') as $locale){ unset($rules["$locale.description"]); $rules["$locale.instructions"]=['nullable','string']; } }
        $data=$request->validate($rules);
        if($resource!=='pickup-points') $this->resolveMedia($request, $data, 'image', $item?->getRawOriginal('image'));
        $data['is_active']=$request->boolean('is_active'); $data['sort_order']=$data['sort_order']??0; return $data;
    }
}
