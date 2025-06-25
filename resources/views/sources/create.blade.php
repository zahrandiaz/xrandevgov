<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Xrandev - Tambah Situs Monitoring</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script> {{-- TAMBAHKAN BARIS INI --}}
    </head>
    <body class="antialiased bg-gray-100 min-h-screen">
        <div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h2 class="text-2xl font-semibold text-gray-800 leading-tight mb-6">
                    {{ __('Tambah Situs Monitoring Baru') }}
                </h2>

                @if ($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <strong class="font-bold">Oops!</strong>
                        <span class="block sm:inline">Ada beberapa masalah dengan input Anda:</span>
                        <ul class="mt-3 list-disc list-inside text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('monitoring.sources.store') }}">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block font-medium text-sm text-gray-700">Nama Situs (misal: Kemendagri)</label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" required
                                class="block w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            @error('name')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="url" class="block font-medium text-sm text-gray-700">URL Utama Situs (misal: https://www.kemendagri.go.id)</label>
                            <input type="url" id="urlInput" name="url" value="{{ old('url') }}" required
                                class="block w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            @error('url')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror
                        </div>

                        {{-- [BARU] Dropdown Wilayah --}}
                        <div class="md:col-span-2">
                            <label for="region_id" class="block font-medium text-sm text-gray-700">Wilayah</label>
                            <select id="region_id" name="region_id" required
                                class="block w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">-- Pilih Wilayah --</option>
                                @foreach($regions->where('type', 'Provinsi') as $provinsi)
                                    <optgroup label="{{ $provinsi->name }}">
                                        @foreach($regions->where('parent_id', $provinsi->id) as $kabkota)
                                            <option value="{{ $kabkota->id }}" {{ old('region_id') == $kabkota->id ? 'selected' : '' }}>
                                                {{ $kabkota->name }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Pilih Kabupaten/Kota tempat situs ini berada.</p>
                            @error('region_id')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror
                        </div>

                        <div class="md:col-span-2">
                            <label for="crawl_url" class="block font-medium text-sm text-gray-700">URL Crawl Spesifik (misal: /berita/ atau /blog/page/1)</label>
                            <input type="text" id="crawlUrlInput" name="crawl_url" value="{{ old('crawl_url', '/') }}"
                                class="block w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <p class="mt-1 text-xs text-gray-500">Path relatif dari URL Utama jika ada daftar berita spesifik. Default: /</p>
                            @error('crawl_url')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror
                        </div>

                        {{-- Preset Selector --}}
                        <div class="md:col-span-2">
                            <label for="preset_selector" class="block font-medium text-sm text-gray-700">Pilih Preset Selector</label>
                            <select id="presetSelector" class="block w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">-- Pilih atau biarkan kosong untuk manual --</option>
                                @foreach($presets as $preset)
                                    <option value="{{ $preset->id }}"
                                            data-title="{{ $preset->selector_title }}"
                                            data-date="{{ $preset->selector_date }}"
                                            data-link="{{ $preset->selector_link }}">
                                        {{ $preset->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Pilih preset untuk mengisi otomatis selector di bawah.</p>
                        </div>
                        {{-- Akhir Preset Selector --}}

                        <div class="md:col-span-2">
                            <label for="selector_title" class="block font-medium text-sm text-gray-700">Selector CSS Judul Berita (misal: h3 a, .post-title a)</label>
                            <input type="text" id="selectorTitleInput" name="selector_title" value="{{ old('selector_title') }}" required
                                class="block w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <p class="mt-1 text-xs text-gray-500">Contoh: `h3 a` atau `.entry-header h2 a`</p>
                            @error('selector_title')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="selector_date" class="block font-medium text-sm text-gray-700">Selector CSS Tanggal Berita (misal: .post-date, time)</label>
                            <input type="text" id="selectorDateInput" name="selector_date" value="{{ old('selector_date') }}"
                                class="block w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <p class="mt-1 text-xs text-gray-500">Contoh: `.info-meta li` atau `time[datetime]`</p>
                            @error('selector_date')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="selector_link" class="block font-medium text-sm text-gray-700">Selector CSS Link Berita (Opsional, jika berbeda dari Judul)</label>
                            <input type="text" id="selectorLinkInput" name="selector_link" value="{{ old('selector_link') }}"
                                class="block w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <p class="mt-1 text-xs text-gray-500">Contoh: `p.read-more a` atau biarkan kosong jika link ada di selector judul.</p>
                            @error('selector_link')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    {{-- Tombol Uji Selector dan Area Hasil --}}
                        <div class="md:col-span-2 mt-4">
                            <button type="button" id="testSelectorBtn" class="bg-indigo-500 text-white py-2 px-4 rounded-md hover:bg-indigo-600 focus:outline-none focus:ring focus:border-indigo-300">
                                Uji Selector
                            </button>
                            <div id="testResultArea" class="mt-4 p-4 border rounded-md hidden">
                                <div id="testLoadingIndicator" class="hidden flex items-center text-gray-700">
                                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>Menguji selector, harap tunggu...</span>
                                </div>
                                <div id="testResultMessage" class="text-sm font-semibold mb-2"></div>
                                <ul id="testArticleList" class="list-disc list-inside text-sm text-gray-700"></ul>
                            </div>
                        </div>
                    </div> {{-- Tutup div grid grid-cols-1 md:grid-cols-2 gap-6 --}}

                    <div class="flex items-center justify-end mt-6">
                        <a href="{{ route('monitoring.sources.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">Batal</a>
                        <button type="submit" class="bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 focus:outline-none focus:ring focus:border-blue-300">
                            Simpan Situs
                        </button>
                    </div>
                </form>

                <div class="mt-8 text-center">
                    <a href="{{ route('dashboard') }}" class="text-blue-500 hover:underline">Kembali ke Dashboard</a>
                </div>
            </div>
        </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const urlInput = document.getElementById('urlInput');
            const crawlUrlInput = document.getElementById('crawlUrlInput');
            const selectorTitleInput = document.getElementById('selectorTitleInput');
            const selectorDateInput = document.getElementById('selectorDateInput');
            const selectorLinkInput = document.getElementById('selectorLinkInput');
            const testSelectorBtn = document.getElementById('testSelectorBtn');
            const testResultArea = document.getElementById('testResultArea');
            const testLoadingIndicator = document.getElementById('testLoadingIndicator');
            const testResultMessage = document.getElementById('testResultMessage');
            const testArticleList = document.getElementById('testArticleList');

            // [BARU] Variabel untuk Preset Selector
            const presetSelector = document.getElementById('presetSelector');

            // [BARU] Event Listener untuk Preset Selector
            presetSelector.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const title = selectedOption.dataset.title || '';
                const date = selectedOption.dataset.date || '';
                const link = selectedOption.dataset.link || '';

                selectorTitleInput.value = title;
                selectorDateInput.value = date;
                selectorLinkInput.value = link;
            });

            testSelectorBtn.addEventListener('click', function() {
                const url = urlInput.value;
                const crawl_url = crawlUrlInput.value;
                const selector_title = selectorTitleInput.value;
                const selector_date = selectorDateInput.value;
                const selector_link = selectorLinkInput.value;

                // Reset area hasil
                testResultArea.classList.add('hidden');
                testResultMessage.textContent = '';
                testArticleList.innerHTML = '';
                testLoadingIndicator.classList.remove('hidden');
                testSelectorBtn.disabled = true;

                // Validasi input minimal sebelum mengirim request
                if (!url || !selector_title) {
                    testResultMessage.textContent = 'URL Utama Situs dan Selector Judul Berita wajib diisi untuk pengujian.';
                    testResultArea.classList.remove('hidden', 'bg-green-100', 'bg-red-100');
                    testResultArea.classList.add('bg-yellow-100', 'border-yellow-400', 'text-yellow-700');
                    testLoadingIndicator.classList.add('hidden');
                    testSelectorBtn.disabled = false;
                    return;
                }
                
                // Pastikan URL memiliki skema
                let fullUrl = url;
                if (!/^https?:\/\//i.test(fullUrl)) {
                    fullUrl = "https://" + fullUrl;
                }

                axios.post('{{ route('monitoring.sources.testSelector') }}', {
                    url: fullUrl,
                    crawl_url: crawl_url,
                    selector_title: selector_title,
                    selector_date: selector_date,
                    selector_link: selector_link
                }, {
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : ''
                    }
                })
                .then(function (response) {
                    testLoadingIndicator.classList.add('hidden');
                    testResultArea.classList.remove('hidden');
                    testResultArea.classList.remove('bg-yellow-100', 'bg-red-100');
                    testResultArea.classList.add('bg-green-100', 'border-green-400', 'text-green-700');

                    if (response.data.success && response.data.articles.length > 0) {
                        testResultMessage.textContent = `Ditemukan ${response.data.articles.length} artikel sampel:`;
                        response.data.articles.forEach(article => {
                            const listItem = document.createElement('li');
                            listItem.innerHTML = `<strong>${article.title}</strong> (${article.date}) - <a href="${article.link}" target="_blank" class="text-blue-700 hover:underline">Lihat</a>`;
                            testArticleList.appendChild(listItem);
                        });
                    } else if (response.data.success && response.data.articles.length === 0) {
                        testResultMessage.textContent = 'Tidak ditemukan artikel dengan selector yang diberikan. Mohon periksa kembali selector atau URL.';
                        testResultArea.classList.remove('bg-green-100');
                        testResultArea.classList.add('bg-yellow-100', 'border-yellow-400', 'text-yellow-700');
                    } else {
                        testResultMessage.textContent = 'Terjadi kesalahan tidak terduga saat pengujian.';
                        testResultArea.classList.remove('bg-green-100');
                        testResultArea.classList.add('bg-red-100', 'border-red-400', 'text-red-700');
                    }
                })
                .catch(function (error) {
                    testLoadingIndicator.classList.add('hidden');
                    testResultArea.classList.remove('hidden');
                    testResultArea.classList.remove('bg-green-100', 'bg-yellow-100');
                    testResultArea.classList.add('bg-red-100', 'border-red-400', 'text-red-700');
                    if (error.response && error.response.data && error.response.data.message) {
                        testResultMessage.textContent = `Gagal menguji selector: ${error.response.data.message}`;
                    } else {
                        testResultMessage.textContent = `Gagal menguji selector: ${error.message}.`;
                    }
                })
                .finally(function() {
                    testSelectorBtn.disabled = false;
                });
            });
        });
    </script>
    </body>
</html>