<?php

namespace App\Http\Controllers;

use App\Models\SuggestionSelector; // [BARU] Impor model kita
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache; // [BARU] Impor cache untuk menghapusnya saat ada perubahan
use Illuminate\Validation\Rule; // [BARU] Impor Rule untuk validasi unik

class SuggestionSelectorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Ambil semua selector dan kelompokkan berdasarkan tipe
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

        SuggestionSelector::create($validatedData);

        // [BARU] Hapus cache yang relevan agar perubahan langsung terbaca
        Cache::forget('suggestion_selectors_' . $validatedData['type']);

        return redirect()->route('suggestion-selectors.index')
                         ->with('success', 'Selector saran berhasil ditambahkan!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SuggestionSelector $suggestionSelector)
    {
        // Laravel's Route Model Binding akan otomatis menemukan data berdasarkan ID
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
        
        $suggestionSelector->update($validatedData);

        // [BARU] Hapus cache lama dan baru jika tipenya berubah
        Cache::forget('suggestion_selectors_' . $oldType);
        Cache::forget('suggestion_selectors_' . $validatedData['type']);

        return redirect()->route('suggestion-selectors.index')
                         ->with('success', 'Selector saran berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SuggestionSelector $suggestionSelector)
    {
        $type = $suggestionSelector->type;
        $suggestionSelector->delete();

        // [BARU] Hapus cache yang relevan
        Cache::forget('suggestion_selectors_' . $type);

        return redirect()->route('suggestion-selectors.index')
                         ->with('success', 'Selector saran berhasil dihapus.');
    }
}