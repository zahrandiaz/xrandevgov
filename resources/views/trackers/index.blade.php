{{-- resources/views/trackers/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Manajemen Pantauan Pengumuman')

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-800 leading-tight">
            {{ __('Manajemen Pantauan Pengumuman') }}
        </h2>
        <a href="{{ route('trackers.create') }}" class="bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 focus:outline-none focus:ring focus:border-blue-300">
            Buat Pantauan Baru
        </a>
    </div>

    <div class="overflow-x-auto shadow-md sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Judul Pantauan</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kata Kunci</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($trackers as $tracker)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $tracker->title }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            @foreach($tracker->keywords as $keyword)
                                <span class="inline-block bg-gray-200 rounded-full px-2 py-1 text-xs font-semibold text-gray-700 mr-1">{{ $keyword }}</span>
                            @endforeach
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $tracker->status == 'Aktif' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ $tracker->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('trackers.show', $tracker) }}" class="text-blue-600 hover:text-blue-900 mr-3">Dashboard</a>
                            <a href="{{ route('trackers.edit', $tracker) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                            <form action="{{ route('trackers.destroy', $tracker) }}" method="POST" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pantauan \'{{ addslashes($tracker->title) }}\'?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                            Belum ada pantauan yang dibuat.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-8 text-center">
        <a href="{{ route('dashboard') }}" class="text-blue-500 hover:underline">Kembali ke Dashboard</a>
    </div>
</div>
<style>
    .disabled-link {
        color: #9ca3af;
        pointer-events: none;
        cursor: not-allowed;
    }
</style>
@endsection