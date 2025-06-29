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
                     x-data="formHandler(
                        '{{ route('monitoring.sources.suggest_selectors_ajax') }}',
                        '{{ route('monitoring.sources.testSelector') }}',
                        '{{ route('monitoring.sources.inspect_html') }}',
                        '{{ csrf_token() }}'
                     )">
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
                        <div class="md:col-span-2 space-y-2">
                            <label for="selector_title" class="block font-medium text-sm text-gray-700">Selector CSS Judul Berita</label>
                            <input type="text" id="selectorTitleInput" name="selector_title" value="{{ old('selector_title') }}" required class="block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div class="space-y-2">
                            <label for="selector_date" class="block font-medium text-sm text-gray-700">Selector CSS Tanggal Berita</label>
                            <input type="text" id="selectorDateInput" name="selector_date" value="{{ old('selector_date') }}" class="block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div class="space-y-2">
                            <label for="selector_link" class="block font-medium text-sm text-gray-700">Selector CSS Link Berita</label>
                            <input type="text" id="selectorLinkInput" name="selector_link" value="{{ old('selector_link') }}" class="block w-full border-gray-300 rounded-md shadow-sm">
                        </div>

                        <div class="md:col-span-2 mt-4 space-y-4">
                            <div class="flex items-center flex-wrap gap-4">
                                <button @click="getSuggestion('stable')" type="button" :disabled="loading" class="bg-teal-500 text-white py-2 px-4 rounded-md hover:bg-teal-600 focus:outline-none disabled:opacity-50">
                                    <span x-show="!loading">Sarankan Selector (Stabil)</span>
                                    <span x-show="loading && strategy === 'stable'">Menganalisis...</span>
                                </button>
                                <button @click="getSuggestion('experimental')" type="button" :disabled="loading" class="bg-orange-500 text-white py-2 px-4 rounded-md hover:bg-orange-600 focus:outline-none disabled:opacity-50">
                                    <span x-show="!loading">Coba AI Eksperimental (v4)</span>
                                    <span x-show="loading && strategy === 'experimental'">Menganalisis...</span>
                                </button>
                                <button @click="testSelectors()" type="button" :disabled="loading" class="bg-indigo-500 text-white py-2 px-4 rounded-md hover:bg-indigo-600 focus:outline-none disabled:opacity-50">
                                    <span x-show="!loading">Uji Selector</span>
                                    <span x-show="loading && strategy === 'test'">Menguji...</span>
                                </button>
                                <button @click="inspectHtml()" type="button" :disabled="loading" class="bg-gray-700 text-white py-2 px-4 rounded-md hover:bg-gray-800 focus:outline-none disabled:opacity-50">
                                    <span x-show="!loading">Tampilkan Cuplikan Kode</span>
                                    <span x-show="loading && strategy === 'inspect'">Menginspeksi...</span>
                                </button>
                            </div>
                            
                            <div x-show="statusMessage" x-text="statusMessage" :class="statusClass" class="text-sm p-3 rounded-md transition-all duration-300" style="display: none;"></div>
                            
                            <div id="testResultArea" class="mt-2 p-4 border rounded-md" x-show="showTestResult" style="display: none;">
                                <h4 id="testResultMessage" class="text-sm font-semibold mb-2"></h4>
                                <ul id="testArticleList" class="list-disc list-inside text-sm text-gray-700 space-y-1"></ul>
                            </div>

                            <div id="inspectorArea" class="mt-2" x-show="showInspectorResult" style="display: none;">
                                <h4 class="text-sm font-semibold mb-2 text-gray-800">Cuplikan Kode HTML dari Artikel Pertama:</h4>
                                <pre class="bg-gray-900 text-white text-sm rounded-md p-4 overflow-x-auto"><code id="inspectorCode"></code></pre>
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
    
    <script>
        function formHandler(suggestionUrl, testUrl, inspectUrl, csrfToken) {
            return {
                loading: false,
                strategy: '',
                statusMessage: '',
                statusClass: '',
                showTestResult: false,
                showInspectorResult: false,

                applyPreset(event) {
                    if (!event.target.value) return;
                    const preset = JSON.parse(event.target.value);
                    document.getElementById('selectorTitleInput').value = preset.selector_title || '';
                    document.getElementById('selectorDateInput').value = preset.selector_date || '';
                    document.getElementById('selectorLinkInput').value = preset.selector_link || '';
                },
                
                resetState() {
                    this.statusMessage = '';
                    this.showTestResult = false;
                    this.showInspectorResult = false;
                },

                getSuggestion(selectedStrategy) {
                    this.resetState();
                    const urlInput = document.getElementById('urlInput');
                    const crawlUrlInput = document.getElementById('crawlUrlInput');
                    const selectorTitleInput = document.getElementById('selectorTitleInput');
                    const selectorDateInput = document.getElementById('selectorDateInput');
                    
                    if (!urlInput.value) { alert('URL Utama Situs wajib diisi.'); return; }

                    this.loading = true;
                    this.strategy = selectedStrategy;
                    this.statusMessage = 'Menganalisis URL dengan strategi ' + selectedStrategy + ', harap tunggu...';
                    this.statusClass = 'bg-yellow-100 text-yellow-800';
                    
                    selectorTitleInput.value = '';
                    selectorDateInput.value = '';

                    axios.post(suggestionUrl, {
                        url: urlInput.value,
                        crawl_url: crawlUrlInput.value,
                        strategy: selectedStrategy,
                        _token: csrfToken
                    })
                    .then(response => {
                        const data = response.data;
                        selectorTitleInput.value = data.title_selectors[0] || '';
                        selectorDateInput.value = data.date_selectors[0] || '';
                        
                        let dateMsg = data.date_selectors.length > 0 ? 'Selector tanggal juga ditemukan.' : 'Selector tanggal tidak ditemukan.';
                        this.statusMessage = `Sukses (${data.message}): Selector judul ditemukan. ${dateMsg}`;
                        this.statusClass = 'bg-green-100 text-green-800';
                    })
                    .catch(error => {
                        this.statusMessage = `Gagal: ${error.response?.data?.message || 'Terjadi kesalahan.'}`;
                        this.statusClass = 'bg-red-100 text-red-800';
                    })
                    .finally(() => { this.loading = false; this.strategy = ''; });
                },

                // [PERBAIKAN v1.25.0] Mengimplementasikan logika testSelectors yang hilang
                testSelectors() {
                    this.resetState();
                    const urlInput = document.getElementById('urlInput');
                    const crawlUrlInput = document.getElementById('crawlUrlInput');
                    const selectorTitleInput = document.getElementById('selectorTitleInput');
                    const selectorDateInput = document.getElementById('selectorDateInput');
                    const selectorLinkInput = document.getElementById('selectorLinkInput');
                    const testResultMessage = document.getElementById('testResultMessage');
                    const testArticleList = document.getElementById('testArticleList');

                    if (!urlInput.value || !selectorTitleInput.value) {
                        alert('URL dan Selector Judul wajib diisi untuk pengujian.');
                        return;
                    }

                    this.loading = true;
                    this.strategy = 'test';
                    this.showTestResult = true;
                    testResultMessage.textContent = 'Menguji...';
                    testArticleList.innerHTML = '';
                    
                    axios.post(testUrl, {
                        url: urlInput.value,
                        crawl_url: crawlUrlInput.value,
                        selector_title: selectorTitleInput.value,
                        selector_date: selectorDateInput.value,
                        selector_link: selectorLinkInput.value,
                        _token: csrfToken
                    })
                    .then(response => {
                        testResultMessage.textContent = `Berhasil! ${response.data.message || 'Ditemukan ' + response.data.articles.length + ' artikel.'}`;
                        testArticleList.innerHTML = '';
                        response.data.articles.forEach(article => {
                            const listItem = document.createElement('li');
                            listItem.innerHTML = `<strong>${article.title || 'Tanpa Judul'}</strong> (${article.date || 'Tanpa Tanggal'})`;
                            testArticleList.appendChild(listItem);
                        });
                    })
                    .catch(error => {
                        testResultMessage.textContent = `Gagal: ${error.response?.data?.message || error.message}`;
                        testArticleList.innerHTML = '';
                    })
                    .finally(() => { this.loading = false; this.strategy = ''; });
                },
                
                inspectHtml() {
                    this.resetState();
                    const urlInput = document.getElementById('urlInput');
                    const crawlUrlInput = document.getElementById('crawlUrlInput');
                    const selectorTitleInput = document.getElementById('selectorTitleInput');
                    
                    if (!urlInput.value || !selectorTitleInput.value) {
                        alert('URL dan Selector Judul wajib diisi untuk inspeksi.');
                        return;
                    }
                    
                    this.loading = true;
                    this.strategy = 'inspect';
                    this.showInspectorResult = true;
                    document.getElementById('inspectorCode').textContent = 'Memuat kode HTML...';
                    
                    axios.post(inspectUrl, {
                        url: urlInput.value,
                        crawl_url: crawlUrlInput.value,
                        selector_title: selectorTitleInput.value,
                         _token: csrfToken
                    })
                    .then(response => {
                        document.getElementById('inspectorCode').textContent = response.data.html;
                    })
                    .catch(error => {
                        document.getElementById('inspectorCode').textContent = `Gagal memuat HTML: ${error.response?.data?.message || error.message}`;
                    })
                    .finally(() => { this.loading = false; this.strategy = ''; });
                }
            }
        }
    </script>
</body>
</html>