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
        // Ambil semua provinsi, dan muat relasi 'children' (kab/kota) untuk setiap provinsi.
        // Urutkan provinsi dan anaknya berdasarkan nama.
        $provinces = Region::where('type', 'Provinsi')
                            ->with(['children' => function ($query) {
                                $query->orderBy('name', 'asc');
                            }])
                            ->orderBy('name', 'asc')
                            ->get();

        // Teruskan data provinsi ke view yang akan kita buat
        return view('regions.index', compact('provinces'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Ambil semua wilayah yang tipenya 'Provinsi' untuk pilihan parent
        $provinces = Region::where('type', 'Provinsi')->orderBy('name')->get();

        return view('regions.create', compact('provinces'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Validasi data yang masuk
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:regions,name',
            'type' => 'required|in:Provinsi,Kabupaten/Kota',
            // 'parent_id' hanya wajib diisi jika tipenya adalah 'Kabupaten/Kota'
            'parent_id' => 'required_if:type,Kabupaten/Kota|nullable|exists:regions,id',
        ], [
            'name.unique' => 'Nama wilayah ini sudah ada.',
            'parent_id.required_if' => 'Anda harus memilih wilayah induk untuk Kabupaten/Kota.',
        ]);

        // 2. Buat data baru di database
        Region::create($validatedData);

        // 3. Kembali ke halaman daftar dengan pesan sukses
        return redirect()->route('regions.index')
                         ->with('success', 'Wilayah baru berhasil ditambahkan!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Region $region)
    {
        // Ambil semua provinsi untuk pilihan parent, KECUALI diri sendiri (jika yg diedit adalah provinsi)
        $provinces = Region::where('type', 'Provinsi')->where('id', '!=', $region->id)->orderBy('name')->get();

        return view('regions.edit', compact('region', 'provinces'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Region $region)
    {
        // Validasi data, pastikan nama unik kecuali untuk diri sendiri
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:regions,name,' . $region->id,
            'type' => 'required|in:Provinsi,Kabupaten/Kota',
            'parent_id' => 'required_if:type,Kabupaten/Kota|nullable|exists:regions,id',
        ], [
            'name.unique' => 'Nama wilayah ini sudah ada.',
            'parent_id.required_if' => 'Anda harus memilih wilayah induk untuk Kabupaten/Kota.',
        ]);

        // Jika tipe diubah dari Kab/Kota menjadi Provinsi, hapus parent_id
        if ($validatedData['type'] === 'Provinsi') {
            $validatedData['parent_id'] = null;
        }

        $region->update($validatedData);

        return redirect()->route('regions.index')
                         ->with('success', 'Wilayah berhasil diperbarui!');
    }

    /**
     * [MODIFIKASI] Remove the specified resource from storage with protection.
     */
    public function destroy(Region $region)
    {
        // [BARU] Logika Proteksi
        // Cek jika wilayah yang akan dihapus memiliki relasi dengan monitoring_sources.
        if ($region->monitoringSources()->exists()) {
            return redirect()->route('regions.index')
                             ->with('error', "Gagal menghapus '{$region->name}' karena masih terhubung dengan situs monitoring.");
        }

        // [BARU] Proteksi tambahan untuk Provinsi: cek juga anak-anaknya.
        if ($region->type === 'Provinsi' && $region->children()->whereHas('monitoringSources')->exists()) {
             return redirect()->route('regions.index')
                             ->with('error', "Gagal menghapus Provinsi '{$region->name}' karena salah satu kabupaten/kota di bawahnya masih terhubung dengan situs monitoring.");
        }

        // Jika semua pemeriksaan lolos, lanjutkan penghapusan.
        $region->delete();

        return redirect()->route('regions.index')
                         ->with('success', 'Wilayah berhasil dihapus!');
    }
}