<?php

namespace App\Http\Controllers;

use App\Models\Region;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $provinces = Region::where('type', 'Provinsi')
                            ->with(['children' => function ($query) {
                                $query->orderBy('name', 'asc');
                            }])
                            ->orderBy('name', 'asc')
                            ->get();
        return view('regions.index', compact('provinces'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $provinces = Region::where('type', 'Provinsi')->orderBy('name')->get();
        return view('regions.create', compact('provinces'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:regions,name',
            'type' => 'required|in:Provinsi,Kabupaten/Kota',
            'parent_id' => 'required_if:type,Kabupaten/Kota|nullable|exists:regions,id',
        ], [
            'name.unique' => 'Nama wilayah ini sudah ada.',
            'parent_id.required_if' => 'Anda harus memilih wilayah induk untuk Kabupaten/Kota.',
        ]);

        $region = Region::create($validatedData);

        // [BARU] Catat aktivitas
        log_activity("Wilayah baru '{$region->name}' ({$region->type}) telah ditambahkan.", 'success', 'region-management');

        return redirect()->route('regions.index')
                         ->with('success', 'Wilayah baru berhasil ditambahkan!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Region $region)
    {
        $provinces = Region::where('type', 'Provinsi')->where('id', '!=', $region->id)->orderBy('name')->get();
        return view('regions.edit', compact('region', 'provinces'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Region $region)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:regions,name,' . $region->id,
            'type' => 'required|in:Provinsi,Kabupaten/Kota',
            'parent_id' => 'required_if:type,Kabupaten/Kota|nullable|exists:regions,id',
        ], [
            'name.unique' => 'Nama wilayah ini sudah ada.',
            'parent_id.required_if' => 'Anda harus memilih wilayah induk untuk Kabupaten/Kota.',
        ]);

        if ($validatedData['type'] === 'Provinsi') {
            $validatedData['parent_id'] = null;
        }

        $oldName = $region->name;
        $region->update($validatedData);

        // [BARU] Catat aktivitas
        log_activity("Wilayah '{$oldName}' telah diperbarui menjadi '{$region->name}'.", 'info', 'region-management');

        return redirect()->route('regions.index')
                         ->with('success', 'Wilayah berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage with protection.
     */
    public function destroy(Region $region)
    {
        // Logika Proteksi
        if ($region->monitoringSources()->exists()) {
            // [BARU] Catat aktivitas gagal
            log_activity("Gagal menghapus wilayah '{$region->name}' karena masih digunakan.", 'error', 'region-management');
            return redirect()->route('regions.index')
                             ->with('error', "Gagal menghapus '{$region->name}' karena masih terhubung dengan situs monitoring.");
        }

        if ($region->type === 'Provinsi' && $region->children()->whereHas('monitoringSources')->exists()) {
            // [BARU] Catat aktivitas gagal
            log_activity("Gagal menghapus provinsi '{$region->name}' karena salah satu anaknya masih digunakan.", 'error', 'region-management');
             return redirect()->route('regions.index')
                             ->with('error', "Gagal menghapus Provinsi '{$region->name}' karena salah satu kabupaten/kota di bawahnya masih terhubung dengan situs monitoring.");
        }

        $regionName = $region->name;
        $region->delete();

        // [BARU] Catat aktivitas sukses
        log_activity("Wilayah '{$regionName}' telah dihapus.", 'warning', 'region-management');

        return redirect()->route('regions.index')
                         ->with('success', 'Wilayah berhasil dihapus!');
    }
}