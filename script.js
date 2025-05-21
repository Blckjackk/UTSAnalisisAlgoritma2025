// Fungsi untuk menampilkan/menyembunyikan breadcrumbs
async function toggleBreadcrumbs(pageId) {
    // Sembunyikan semua breadcrumbs terlebih dahulu
    document.querySelectorAll('.breadcrumbs').forEach(item => {
        item.style.display = 'none';
    });

    try {
        // Ambil breadcrumbs dari backend
        const response = await fetch(`http://localhost:5000/breadcrumbs/${pageId}`);
        const breadcrumbs = await response.json();

        // Tampilkan breadcrumbs
        const breadcrumbContainer = document.getElementById(`breadcrumbs-${pageId}`);
        if (breadcrumbContainer) {
            breadcrumbContainer.innerHTML = `
                <h3>
                    <i class="fas fa-route"></i>
                    Rute ke Halaman
                </h3>
                <ol>
                    ${breadcrumbs.map(breadcrumb => `
                        <li><a href="${breadcrumb.url}">${breadcrumb.title}</a> (${breadcrumb.url})</li>
                    `).join('')}
                </ol>
            `;
            breadcrumbContainer.style.display = 'block';
            breadcrumbContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    } catch (error) {
        console.error('Error fetching breadcrumbs:', error);
        alert('Terjadi kesalahan saat mengambil rute tautan.');
    }
}

// Fungsi untuk memperbarui jumlah hasil per halaman
function updateResultsCount() {
    const count = document.getElementById('resultsPerPage').value;
    console.log(`Menampilkan ${count} hasil per halaman`);

    // Di implementasi sebenarnya, ini akan memicu pencarian ulang dengan jumlah hasil yang berbeda
    if (document.getElementById('searchInput').value.trim() !== '') {
        doSearch();
    }
}

// Fungsi untuk melakukan pencarian
async function doSearch() {
    const searchTerm = document.getElementById('searchInput').value;
    const resultsPerPage = document.getElementById('resultsPerPage').value;

    if (searchTerm.trim() === '') {
        alert('Silakan masukkan kata kunci pencarian');
        return;
    }

    // Tampilkan loading
    document.getElementById('loadingIndicator').style.display = 'flex';
    document.getElementById('searchResults').style.opacity = '0.5';

    try {
        // Kirim permintaan ke backend
        const response = await fetch(`http://localhost:5000/search?query=${encodeURIComponent(searchTerm)}&limit=${resultsPerPage}`);
        const results = await response.json();

        // Perbarui hasil pencarian
        const resultList = document.getElementById('resultList');
        resultList.innerHTML = '';

        results.forEach(result => {
            const resultItem = document.createElement('div');
            resultItem.className = 'result-item';

            resultItem.innerHTML = `
                <div class="result-title">
                    <a href="${result.url}" target="_blank">${result.title}</a>
                    <a href="#" class="view-route-button" onclick="toggleBreadcrumbs('${result.pageId}')">
                        <i class="fas fa-route"></i> Lihat Rute Link
                    </a>
                </div>
                <div class="result-url">
                    <i class="fas fa-link"></i> ${result.url}
                </div>
                <div class="result-description">
                    ${result.description}
                </div>
                <div class="result-tags">
                    ${result.tags.map(tag => `<span class="result-tag">${tag}</span>`).join('')}
                </div>
            `;

            resultList.appendChild(resultItem);

            // Buat breadcrumb untuk hasil ini jika belum ada
            createBreadcrumb(result.pageId, result.title, result.url);
        });

        // Perbarui judul hasil pencarian
        document.querySelector('#searchResults h2').innerHTML = `
            <i class="fas fa-list-ul"></i> Hasil Pencarian untuk "${searchTerm}" (${results.length})
        `;
    } catch (error) {
        console.error('Error fetching search results:', error);
        alert('Terjadi kesalahan saat mengambil hasil pencarian.');
    } finally {
        // Sembunyikan loading
        document.getElementById('loadingIndicator').style.display = 'none';
        document.getElementById('searchResults').style.opacity = '1';
    }
}

// Fungsi simulasi pencarian (hanya untuk demo)
function simulateSearch(term, count) {
    // Waktu pencarian acak untuk demo
    const searchTime = (Math.random() * 0.7 + 0.2).toFixed(2);
    document.getElementById('searchTiming').textContent = `Ditemukan dalam ${searchTime} detik`;

    // Contoh hasil simulasi pencarian
    const simulatedResults = [
        {
            title: `Kurikulum ${term} FPMIPA`,
            url: `https://fpmipa.upi.edu/kurikulum/${term.toLowerCase()}`,
            description: `Informasi mengenai kurikulum ${term} di Fakultas Pendidikan Matematika dan Ilmu Pengetahuan Alam.`,
            tags: ['Akademik', 'Kurikulum', term]
        },
        {
            title: `Program Studi ${term}`,
            url: `https://fpmipa.upi.edu/program-studi/${term.toLowerCase()}`,
            description: `Daftar program studi yang terkait dengan ${term} di FPMIPA UPI beserta informasi pendaftaran.`,
            tags: ['Program Studi', term]
        },
        {
            title: `Penelitian ${term} di FPMIPA`,
            url: `https://fpmipa.upi.edu/penelitian/${term.toLowerCase()}`,
            description: `Informasi tentang penelitian ${term} yang sedang dilakukan oleh dosen dan mahasiswa FPMIPA.`,
            tags: ['Penelitian', 'Akademik']
        },
        {
            title: `Kegiatan ${term} FPMIPA`,
            url: `https://fpmipa.upi.edu/kegiatan/${term.toLowerCase()}`,
            description: `Jadwal dan informasi kegiatan ${term} yang diselenggarakan oleh FPMIPA.`,
            tags: ['Kegiatan', 'Event']
        },
        {
            title: `Berita ${term} FPMIPA`,
            url: `https://fpmipa.upi.edu/berita/${term.toLowerCase()}`,
            description: `Berita terkini mengenai ${term} di Fakultas Pendidikan Matematika dan Ilmu Pengetahuan Alam.`,
            tags: ['Berita', 'Update']
        }
    ];

    // Batasi jumlah hasil sesuai pilihan pengguna
    const limitedResults = simulatedResults.slice(0, count);

    // Buat elemen HTML untuk hasil pencarian
    const resultList = document.getElementById('resultList');
    resultList.innerHTML = '';

    limitedResults.forEach((result, index) => {
        const resultItem = document.createElement('div');
        resultItem.className = 'result-item';

        // Generate tags HTML
        let tagsHTML = '';
        if (result.tags && result.tags.length > 0) {
            tagsHTML = '<div class="result-tags">';
            result.tags.forEach(tag => {
                tagsHTML += `<span class="result-tag">${tag}</span>`;
            });
            tagsHTML += '</div>';
        }

        // Mengambil nama halaman dari URL untuk breadcrumb
        const pageName = result.url.split('/').pop();

        resultItem.innerHTML = `
            <div class="result-title">
                <a href="${result.url}">${result.title}</a>
                <a href="#" class="view-route-button" onclick="toggleBreadcrumbs('${pageName}')">
                    <i class="fas fa-route"></i> Lihat Rute Link
                </a>
            </div>
            <div class="result-url">
                <i class="fas fa-link"></i> ${result.url}
            </div>
            <div class="result-description">
                ${result.description}
            </div>
            ${tagsHTML}
        `;

        resultList.appendChild(resultItem);

        // Buat breadcrumb untuk hasil ini jika belum ada
        createBreadcrumb(pageName, result.title, result.url);
    });

    // Update judul hasil pencarian
    document.querySelector('#searchResults h2').innerHTML = `
        <i class="fas fa-list-ul"></i> Hasil Pencarian untuk "${term}" (${limitedResults.length})
    `;

    // Update pagination jika perlu
    updatePagination(Math.ceil(simulatedResults.length / count));
}

// Fungsi untuk membuat breadcrumb untuk hasil pencarian jika belum ada
function createBreadcrumb(pageId, title, url) {
    const breadcrumbsContainer = document.getElementById(`breadcrumbs-${pageId}`);

    // Jika breadcrumb sudah ada, tidak perlu membuat ulang
    if (breadcrumbsContainer) {
        return;
    }

    // Buat elemen breadcrumb baru
    const newBreadcrumb = document.createElement('div');
    newBreadcrumb.className = 'breadcrumbs';
    newBreadcrumb.id = `breadcrumbs-${pageId}`;

    newBreadcrumb.innerHTML = `
        <h3>
            <i class="fas fa-route"></i>
            Rute ke Halaman ${title}
        </h3>
        <ol>
            <li><a href="https://www.upi.edu">UPI - Beranda</a> (https://www.upi.edu)</li>
            <li><a href="https://fpmipa.upi.edu">FPMIPA</a> (https://fpmipa.upi.edu)</li>
            <li><a href="${url}">${title}</a> (${url})</li>
        </ol>
    `;

    // Tambahkan breadcrumb ke container hasil pencarian
    document.getElementById('searchResults').appendChild(newBreadcrumb);
}
