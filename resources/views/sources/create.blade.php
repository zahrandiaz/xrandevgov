<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Xrandev - Tambah Situs Monitoring</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="antialiased bg-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div x-data="{ instansi: '{{ old('tipe_instansi', '') }}' }" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h2 class="text-2xl font-semibold text-gray-800 leading-tight mb-6">
                {{ __('Tambah Situs Monitoring Baru') }}
            </h2>

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">Oops! Ada masalah dengan input Anda:</strong>
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
                        <label for="name" class="block font-medium text-sm text-gray-700">Nama Situs</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required class="block w-full mt-1 border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label for="url" class="block font-medium text-sm text-gray-700">URL Utama Situs</label>
                        <input type="url" id="urlInput" name="url" value="{{ old('url') }}" required class="block w-full mt-1 border-gray-300 rounded-md shadow-sm">
                    </div>
                    
                    <div>
                        <label for="tipe_instansi" class="block font-medium text-sm text-gray-700">Tipe Instansi</label>
                        <select name="tipe_instansi" x-model="instansi" required class="block w-full mt-1 border-gray-300 rounded-md shadow-sm">
                            <option value="">-- Pilih Tipe --</option>
                            <option value="BKD">BKD (Provinsi)</option>
                            <option value="BKPSDM">BKPSDM (Kabupaten/Kota)</option>
                        </select>
                    </div>

                    <div>
                        <label for="region_id" class="block font-medium text-sm text-gray-700">Wilayah</label>
                        <select name="region_id" required class="block w-full mt-1 border-gray-300 rounded-md shadow-sm" :disabled="!instansi">
                            {{-- ... (Opsi wilayah tetap sama) ... --}}
                            <option value="">-- Pilih Tipe Instansi Dulu --</option>
                            <template x-if="instansi === 'BKD'">
                                <optgroup label="Provinsi">
                                @foreach($provinces as $province)
                                    <option value="{{ $province->id }}" {{ old('region_id') == $province->id ? 'selected' : '' }}>{{ $province->name }}</option>
                                @endforeach
                                </optgroup>
                            </template>
                            <template x-if="instansi === 'BKPSDM'">
                                <optgroup label="Kabupaten/Kota">
                                @foreach($kabkotas as $kabkota)
                                    <option value="{{ $kabkota->id }}" {{ old('region_id') == $kabkota->id ? 'selected' : '' }}>{{ $kabkota->name }}</option>
                                @endforeach
                                </optgroup>
                            </template>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label for="crawl_url" class="block font-medium text-sm text-gray-700">URL Crawl Spesifik (opsional)</label>
                        <input type="text" id="crawlUrlInput" name="crawl_url" value="{{ old('crawl_url', '/') }}" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm">
                    </div>
                </div>

                <div class="mt-6 pt-6 border-t" 
                     x-data="suggestionHandler('{{ route('monitoring.sources.suggest_selectors_ajax') }}', '{{ csrf_token() }}')">
                    <h3 class="text-lg font-medium">Konfigurasi Crawler</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                        <div class="md:col-span-2">
                            <label for="preset_selector" class="block font-medium text-sm text-gray-700">Pilih Preset Selector</label>
                            <select id="presetSelector" @change="applyPreset($event)" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm">
                                <option value="">-- Pilih atau biarkan kosong untuk manual --</option>
                                @foreach($presets as $preset)
                                    <option value="{{ json_encode($preset) }}">{{ $preset->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label for="selector_title" class="block font-medium text-sm text-gray-700">Selector CSS Judul Berita</label>
                            <input type="text" id="selectorTitleInput" name="selector_title" value="{{ old('selector_title') }}" required class="block w-full mt-1 border-gray-300 rounded-md shadow-sm">
                            
                            {{-- [BARU] Tombol dan Area Status Saran --}}
                            <div class="mt-2">
                                <button @click="getSuggestion()" type="button" :disabled="isLoading" class="bg-teal-500 text-white py-2 px-4 rounded-md hover:bg-teal-600 focus:outline-none disabled:opacity-50">
                                    <span x-show="!isLoading">Cari & Sarankan Selector</span>
                                    <span x-show="isLoading">Menganalisis...</span>
                                </button>
                                <div x-show="statusMessage" x-text="statusMessage" :class="statusClass" class="mt-2 text-sm p-2 rounded-md"></div>
                            </div>
                        </div>

                        <div>
                            <label for="selector_date" class="block font-medium text-sm text-gray-700">Selector CSS Tanggal Berita</glabel>
                            <input type="text" id="selectorDateInput" name="selector_date" value="{{ old('selector_date') }}" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label for="selector_link" class="block font-medium text-sm text-gray-700">Selector CSS Link Berita</label>
                            <input type="text" id="selectorLinkInput" name="selector_link" value="{{ old('selector_link') }}" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm">
                        </div>
                        
                        {{-- Fitur Uji Selector Tetap Ada --}}
                         <div class="md:col-span-2 mt-4">
                            <button type="button" id="testSelectorBtn" class="bg-indigo-500 text-white py-2 px-4 rounded-md hover:bg-indigo-600 focus:outline-none focus:ring focus:border-indigo-300">
                                Uji Selector
                            </button>
                            <div id="testResultArea" class="mt-4 p-4 border rounded-md hidden">
                                <div id="testLoadingIndicator" class="hidden flex items-center text-gray-700">
                                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <span>Menguji selector, harap tunggu...</span>
                                </div>
                                <div id="testResultMessage" class="text-sm font-semibold mb-2"></div>
                                <ul id="testArticleList" class="list-disc list-inside text-sm text-gray-700"></ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end mt-6">
                    <a href="{{ route('monitoring.sources.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">Batal</a>
                    <button type="submit" class="bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600">
                        Simpan Situs
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- [BARU] Logika JavaScript untuk Saran & Preset --}}
    <script>
        function suggestionHandler(suggestionUrl, csrfToken) {
            return {
                isLoading: false,
                statusMessage: '',
                statusClass: '',

                // Fungsi untuk menerapkan preset yang dipilih
                applyPreset(event) {
                    if (!event.target.value) return;
                    const preset = JSON.parse(event.target.value);
                    document.getElementById('selectorTitleInput').value = preset.selector_title || '';
                    document.getElementById('selectorDateInput').value = preset.selector_date || '';
                    document.getElementById('selectorLinkInput').value = preset.selector_link || '';
                },

                // Fungsi untuk mendapatkan saran selector via AJAX
                getSuggestion() {
                    const urlInput = document.getElementById('urlInput');
                    const crawlUrlInput = document.getElementById('crawlUrlInput');
                    const selectorTitleInput = document.getElementById('selectorTitleInput');
                    
                    if (!urlInput.value) {
                        alert('URL Utama Situs wajib diisi sebelum mencari saran.');
                        return;
                    }

                    this.isLoading = true;
                    this.statusMessage = 'Menganalisis URL, harap tunggu...';
                    this.statusClass = 'bg-yellow-100 text-yellow-800';

                    axios.post(suggestionUrl, {
                        url: urlInput.value,
                        crawl_url: crawlUrlInput.value,
                        _token: csrfToken
                    })
                    .then(response => {
                        const selectors = response.data.selectors;
                        if (selectors && selectors.length > 0) {
                            selectorTitleInput.value = selectors[0]; // Isi dengan saran terbaik
                            
                            let otherSuggestions = selectors.slice(1).join(', ');
                            this.statusMessage = `Sukses! Selector judul telah diisi.`;
                            if (otherSuggestions) {
                                this.statusMessage += ` Saran lain ditemukan: ${otherSuggestions}`;
                            }
                            this.statusClass = 'bg-green-100 text-green-800';
                        }
                    })
                    .catch(error => {
                        this.statusMessage = `Gagal: ${error.response?.data?.message || 'Terjadi kesalahan.'}`;
                        this.statusClass = 'bg-red-100 text-red-800';
                    })
                    .finally(() => {
                        this.isLoading = false;
                    });
                }
            }
        }

        // Event listener lama untuk Uji Selector tetap di sini
        document.addEventListener('DOMContentLoaded', function() {
            // Event listener untuk Preset Selector
            const presetSelector = document.getElementById('presetSelector');
            presetSelector.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                document.getElementById('selectorTitleInput').value = selectedOption.dataset.title || '';
                document.getElementById('selectorDateInput').value = selectedOption.dataset.date || '';
                document.getElementById('selectorLinkInput').value = selectedOption.dataset.link || '';
            });

            // Event listener untuk Tombol Uji Selector
            const testSelectorBtn = document.getElementById('testSelectorBtn');
            testSelectorBtn.addEventListener('click', function() {
                // ... (Semua konstanta dan logika axios untuk testSelector diletakkan di sini) ...
                const urlInput = document.getElementById('urlInput');
                const crawlUrlInput = document.getElementById('crawlUrlInput');
                const selectorTitleInput = document.getElementById('selectorTitleInput');
                const selectorDateInput = document.getElementById('selectorDateInput');
                const selectorLinkInput = document.getElementById('selectorLinkInput');
                const testResultArea = document.getElementById('testResultArea');
                const testLoadingIndicator = document.getElementById('testLoadingIndicator');
                const testResultMessage = document.getElementById('testResultMessage');
                const testArticleList = document.getElementById('testArticleList');

                const url = urlInput.value;
                const crawl_url = crawlUrlInput.value;
                const selector_title = selectorTitleInput.value;
                const selector_date = selectorDateInput.value;
                const selector_link = selectorLinkInput.value;

                testResultArea.classList.add('hidden');
                testResultMessage.textContent = '';
                testArticleList.innerHTML = '';
                testLoadingIndicator.classList.remove('hidden');
                testSelectorBtn.disabled = true;

                if (!url || !selector_title) {
                    testResultMessage.textContent = 'URL Utama Situs dan Selector Judul Berita wajib diisi untuk pengujian.';
                    testResultArea.classList.remove('hidden');
                    testLoadingIndicator.classList.add('hidden');
                    testSelectorBtn.disabled = false;
                    return;
                }

                let fullUrl = url;
                if (!/^https?:\/\//i.test(fullUrl)) {
                    fullUrl = "https://" + fullUrl;
                }

                axios.post('{{ route('monitoring.sources.testSelector') }}', {
                    url: fullUrl, crawl_url, selector_title, selector_date, selector_link
                })
                .then(function (response) {
                    testResultArea.classList.remove('hidden');
                    if (response.data.success && response.data.articles.length > 0) {
                        testResultMessage.textContent = `Berhasil! ${response.data.message || 'Ditemukan ' + response.data.articles.length + ' artikel.'}`;
                        response.data.articles.forEach(article => {
                            const listItem = document.createElement('li');
                            listItem.innerHTML = '<strong>' + (article.title || 'Tanpa Judul') + '</strong> (' + (article.date || 'Tanpa Tanggal') + ') - <a href="' + article.link + '" target="_blank" class="text-blue-700 hover:underline">Lihat</a>';
                            testArticleList.appendChild(listItem);
                        });
                    } else {
                        testResultMessage.textContent = `Selesai, namun tidak ada artikel ditemukan. Pesan: ${response.data.message || 'Periksa kembali selector atau URL.'}`;
                    }
                })
                .catch(function (error) {
                    testResultArea.classList.remove('hidden');
                    testResultMessage.textContent = `Gagal: ${error.response?.data?.message || error.message}`;
                })
                .finally(function() {
                    testLoadingIndicator.classList.add('hidden');
                    testSelectorBtn.disabled = false;
                });
            });
        });
    </script>
</body>
</html>