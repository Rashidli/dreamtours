<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhyUs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WhyUsController extends Controller
{
    public function index()
    {
        $items = WhyUs::ordered()->paginate(10);
        return view('admin.why_us.index', compact('items'));
    }

    public function create()
    {
        return view('admin.why_us.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'az_title' => 'required',
        ]);

        DB::beginTransaction();
        try {
            WhyUs::create([
                'icon' => $request->icon,
                'is_active' => $request->boolean('is_active', true),
                'order' => $request->order ?? 0,
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
            ]);

            DB::commit();
            return redirect()->route('why_us.index')->with('message', 'Uğurla əlavə edildi');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function edit(WhyUs $why_u)
    {
        return view('admin.why_us.edit', ['item' => $why_u]);
    }

    public function update(Request $request, WhyUs $why_u)
    {
        $request->validate([
            'az_title' => 'required',
        ]);

        DB::beginTransaction();
        try {
            $why_u->update([
                'icon' => $request->icon,
                'is_active' => $request->boolean('is_active'),
                'order' => $request->order ?? 0,
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
            ]);

            DB::commit();
            return redirect()->back()->with('message', 'Uğurla yeniləndi');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(WhyUs $why_u)
    {
        $why_u->delete();
        return redirect()->route('why_us.index')->with('message', 'Uğurla silindi');
    }
}
