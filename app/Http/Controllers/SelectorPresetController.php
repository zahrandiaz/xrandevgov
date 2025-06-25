<?php

namespace App\Http\Controllers;

use App\Models\SelectorPreset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // Tambahkan ini untuk logging

class SelectorPresetController extends Controller
{
    /**
     * Display a listing of the selector presets.
     */
    public function index()
    {
        $presets = SelectorPreset::orderBy('name')->get();
        return view('selector-presets.index', compact('presets'));
    }

    /**
     * Show the form for creating a new selector preset.
     */
    public function create()
    {
        return view('selector-presets.create');
    }

    /**
     * Store a newly created selector preset in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:selector_presets,name',
            'selector_title' => 'required|string',
            'selector_date' => 'nullable|string',
            'selector_link' => 'nullable|string',
        ]);

        SelectorPreset::create($validatedData);

        return redirect()->route('selector-presets.index')
                         ->with('success', 'Preset Selector berhasil ditambahkan!');
    }

    /**
     * Show the form for editing the specified selector preset.
     */
    public function edit(SelectorPreset $selectorPreset)
    {
        return view('selector-presets.edit', compact('selectorPreset'));
    }

    /**
     * Update the specified selector preset in storage.
     */
    public function update(Request $request, SelectorPreset $selectorPreset)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:selector_presets,name,' . $selectorPreset->id,
            'selector_title' => 'required|string',
            'selector_date' => 'nullable|string',
            'selector_link' => 'nullable|string',
        ]);

        $selectorPreset->update($validatedData);

        return redirect()->route('selector-presets.index')
                         ->with('success', 'Preset Selector berhasil diperbarui!');
    }

    /**
     * Remove the specified selector preset from storage.
     */
    public function destroy(SelectorPreset $selectorPreset)
    {
        try {
            $selectorPreset->delete();
            return redirect()->route('selector-presets.index')
                             ->with('success', 'Preset Selector berhasil dihapus!');
        } catch (\Exception $e) {
            Log::error('Gagal menghapus preset selector ' . $selectorPreset->id . ': ' . $e->getMessage());
            return redirect()->route('selector-presets.index')
                             ->with('error', 'Gagal menghapus Preset Selector. Mungkin sedang digunakan.');
        }
    }
}