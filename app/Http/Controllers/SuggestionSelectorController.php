<?php

namespace App\Http\Controllers;

use App\Models\SuggestionSelector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class SuggestionSelectorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $selectors = SuggestionSelector::orderBy('type')->orderBy('priority', 'desc')->get()->groupBy('type');
        return view('suggestion-selectors.index', compact('selectors'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('suggestion-selectors.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'type' => ['required', Rule::in(['title', 'date'])],
            'selector' => 'required|string|max:255|unique:suggestion_selectors,selector',
            'priority' => 'required|integer|min:0',
        ]);

        $selector = SuggestionSelector::create($validatedData);

        Cache::forget('suggestion_selectors_' . $validatedData['type']);
        
        // [BARU] Catat aktivitas
        log_activity("Selector saran baru '{$selector->selector}' telah ditambahkan.", 'success', 'suggestion-selector-management');

        return redirect()->route('suggestion-selectors.index')
                         ->with('success', 'Selector saran berhasil ditambahkan!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SuggestionSelector $suggestionSelector)
    {
        return view('suggestion-selectors.edit', compact('suggestionSelector'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SuggestionSelector $suggestionSelector)
    {
        $validatedData = $request->validate([
            'type' => ['required', Rule::in(['title', 'date'])],
            'selector' => 'required|string|max:255|unique:suggestion_selectors,selector,' . $suggestionSelector->id,
            'priority' => 'required|integer|min:0',
        ]);
        
        $oldType = $suggestionSelector->type;
        $oldSelectorName = $suggestionSelector->selector;

        $suggestionSelector->update($validatedData);
        
        Cache::forget('suggestion_selectors_' . $oldType);
        Cache::forget('suggestion_selectors_' . $validatedData['type']);
        
        // [BARU] Catat aktivitas
        log_activity("Selector saran '{$oldSelectorName}' telah diperbarui menjadi '{$suggestionSelector->selector}'.", 'info', 'suggestion-selector-management');

        return redirect()->route('suggestion-selectors.index')
                         ->with('success', 'Selector saran berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SuggestionSelector $suggestionSelector)
    {
        $type = $suggestionSelector->type;
        $selectorName = $suggestionSelector->selector; // Simpan nama sebelum dihapus
        
        $suggestionSelector->delete();
        
        Cache::forget('suggestion_selectors_' . $type);

        // [BARU] Catat aktivitas
        log_activity("Selector saran '{$selectorName}' telah dihapus.", 'warning', 'suggestion-selector-management');

        return redirect()->route('suggestion-selectors.index')
                         ->with('success', 'Selector saran berhasil dihapus.');
    }
}