// assets/js/search.js — Search Autocomplete
// PanteeThai.com v3

const PanteeSearch = {
    input: null,
    dropdown: null,
    debounceTimer: null,

    init() {
        this.input = document.getElementById('search-input');
        if (!this.input) return;

        // สร้าง dropdown
        this.dropdown = document.createElement('div');
        this.dropdown.id = 'search-dropdown';
        this.dropdown.className = [
            'absolute', 'bg-white', 'border', 'border-gray-200',
            'rounded-xl', 'shadow-lg', 'w-full', 'z-[2000]',
            'max-h-64', 'overflow-y-auto', 'hidden'
        ].join(' ');

        // วาง dropdown ใต้ input
        this.input.parentElement.style.position = 'relative';
        this.input.parentElement.appendChild(this.dropdown);

        // Events
        this.input.addEventListener('input', () => this.onInput());
        this.input.addEventListener('keydown', (e) => this.onKeydown(e));
        document.addEventListener('click', (e) => {
            if (!this.input.contains(e.target)) this.hide();
        });
    },

    onInput() {
        clearTimeout(this.debounceTimer);
        const q = this.input.value.trim();

        if (q.length < 2) {
            this.hide();
            return;
        }

        // Debounce 300ms
        this.debounceTimer = setTimeout(() => this.search(q), 300);
    },

    search(q) {
        fetch(`/api/search.php?q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success || !data.data.length) {
                    this.showEmpty();
                    return;
                }
                this.showResults(data.data);
            })
            .catch(err => console.error('Search error:', err));
    },

    showResults(results) {
        this.dropdown.innerHTML = '';

        results.forEach(item => {
            const div = document.createElement('div');
            div.className = 'px-4 py-3 hover:bg-green-50 cursor-pointer border-b border-gray-100 last:border-0';
            div.innerHTML = `
                <div class="flex items-center gap-2">
                    <span class="text-lg">${item.type === 'province' ? '🗺️' : '📍'}</span>
                    <div>
                        <div class="font-medium text-gray-800">${item.name_th}</div>
                        <div class="text-xs text-gray-500">${item.name_en || ''} 
                            ${item.province_name ? '· ' + item.province_name : ''}
                        </div>
                    </div>
                </div>
            `;

            div.addEventListener('click', () => this.selectResult(item));
            this.dropdown.appendChild(div);
        });

        this.show();
    },

    showEmpty() {
        this.dropdown.innerHTML = `
            <div class="px-4 py-3 text-gray-400 text-sm text-center">
                ไม่พบผลลัพธ์
            </div>
        `;
        this.show();
    },

    selectResult(item) {
        this.input.value = item.name_th;
        this.hide();

        // Fly map ไปที่ผลลัพธ์
        if (item.lat && item.lng) {
            const zoom = item.type === 'province' ? 11 : 14;
            PanteeMap.flyTo(item.lat, item.lng, zoom);

            // โหลด POI ถ้าเลือกจังหวัด
            if (item.type === 'province') {
                PanteeMap.loadPOI(item.slug);
            }
        }
    },

    onKeydown(e) {
        const items = this.dropdown.querySelectorAll('div[class*="cursor-pointer"]');
        const active = this.dropdown.querySelector('.bg-green-100');
        let idx = Array.from(items).indexOf(active);

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (active) active.classList.remove('bg-green-100');
            idx = (idx + 1) % items.length;
            items[idx]?.classList.add('bg-green-100');
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (active) active.classList.remove('bg-green-100');
            idx = (idx - 1 + items.length) % items.length;