<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Step;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StepController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:list-steps|create-steps|edit-steps|delete-steps', ['only' => ['index','show']]);
        $this->middleware('permission:create-steps', ['only' => ['create','store']]);
        $this->middleware('permission:edit-steps', ['only' => ['edit']]);
        $this->middleware('permission:delete-steps', ['only' => ['destroy']]);
    }

    public function index()
    {
        $steps = Step::latest()->paginate(10);
        return view('admin.steps.index', compact('steps'));
    }

    public function create()
    {
        return view('admin.steps.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'az_title' => 'required',
            'en_title' => 'required',
            'ru_title' => 'required',
        ]);

        $data = [
            'is_active' => $request->boolean('is_active', true),
            'az' => [
                'title' => $request->az_title,
                'description' => $request->az_description,
            ],
            'en' => [
                'title' => $request->en_title,
                'description' => $request->en_description,
            ],
            'ru' => [
                'title' => $request->ru_title,
                'description' => $request->ru_description,
            ],
        ];

        if ($request->hasFile('white_icon')) {
            $data['white_icon'] = $request->file('white_icon')->store('steps', 'public');
        }

        if ($request->hasFile('blue_icon')) {
            $data['blue_icon'] = $request->file('blue_icon')->store('steps', 'public');
        }

        Step::create($data);

        return redirect()->route('steps.index')->with('message', 'Step uğurla əlavə edildi');
    }

    public function show(Step $step)
    {
        //
    }

    public function edit(Step $step)
    {
        return view('admin.steps.edit', compact('step'));
    }

    public function update(Request $request, Step $step)
    {
        $request->validate([
            'az_title' => 'required',
            'en_title' => 'required',
            'ru_title' => 'required',
        ]);

        $data = [
            'is_active' => $request->boolean('is_active'),
            'az' => [
                'title' => $request->az_title,
                'description' => $request->az_description,
            ],
            'en' => [
                'title' => $request->en_title,
                'description' => $request->en_description,
            ],
            'ru' => [
                'title' => $request->ru_title,
                'description' => $request->ru_description,
            ],
        ];

        if ($request->hasFile('white_icon')) {
            if ($step->white_icon) {
                Storage::disk('public')->delete($step->white_icon);
            }
            $data['white_icon'] = $request->file('white_icon')->store('steps', 'public');
        }

        if ($request->hasFile('blue_icon')) {
            if ($step->blue_icon) {
                Storage::disk('public')->delete($step->blue_icon);
            }
            $data['blue_icon'] = $request->file('blue_icon')->store('steps', 'public');
        }

        $step->update($data);

        return redirect()->back()->with('message', 'Step uğurla yeniləndi');
    }

    public function destroy(Step $step)
    {
        if ($step->white_icon) {
            Storage::disk('public')->delete($step->white_icon);
        }
        if ($step->blue_icon) {
            Storage::disk('public')->delete($step->blue_icon);
        }
        $step->delete();
        return redirect()->route('steps.index')->with('message', 'Step uğurla silindi');
    }
}
