<?php
// Ambil data pencarian dari database MySQL
$query = isset($_GET['query']) ? $_GET['query'] : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$results = [];

if (!empty($query)) {
    // Koneksi ke database MySQL
    $conn = new mysqli('localhost', 'root', '', 'db_andal');

    // Periksa koneksi
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }

    // Tokenisasi query pencarian
    $queryWords = explode(' ', strtolower($query));
    $placeholders = implode(',', array_fill(0, count($queryWords), '?'));

    // Query untuk mencari hasil berdasarkan kata kunci
    $stmt = $conn->prepare(
        "SELECT pages.id, pages.url, pages.title, pages.content, SUM(word_counts.count) as total 
        FROM word_counts 
        JOIN pages ON pages.id = word_counts.page_id 
        WHERE word_counts.word IN ($placeholders) 
        GROUP BY pages.id 
        ORDER BY total DESC 
        LIMIT ?"
    );

    // Bind parameter
    $params = array_merge($queryWords, [$limit]);
    $types = str_repeat('s', count($queryWords)) . 'i';
    $stmt->bind_param($types, ...$params);

    // Eksekusi query
    $stmt->execute();
    $result = $stmt->get_result();

    // Ambil hasil pencarian
    while ($row = $result->fetch_assoc()) {
        $results[] = [
            'id' => $row['id'],
            'url' => $row['url'],
            'title' => $row['title'],
            'description' => substr($row['content'], 0, 200) . '...',
            'score' => $row['total']
        ];
    }

    // Tutup koneksi
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mesin Pencari Informasi Publik</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <h1>Mesin Pencarinya Rahmat</h1>
    </div>

    <div class="search-section">
        <form method="GET" action="index.php">
            <div class="search-container">
                <div class="search-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="query" id="searchInput" class="search-input" placeholder="Masukkan kata kunci pencarian..." aria-label="Kata kunci pencarian" value="<?php echo htmlspecialchars($query); ?>">
                </div>
                <button type="submit" class="search-button">
                    <i class="fas fa-search"></i>
                    Telusuri
                </button>
            </div>

            <div class="search-options">
                <div class="results-count">
                    <label for="resultsPerPage">Tampilkan:</label>
                    <select name="limit" id="resultsPerPage" onchange="this.form.submit()">
                        <option value="5" <?php echo $limit == 5 ? 'selected' : ''; ?>>5 hasil</option>
                        <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10 hasil</option>
                        <option value="20" <?php echo $limit == 20 ? 'selected' : ''; ?>>20 hasil</option>
                        <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50 hasil</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <div class="loading-indicator" id="loadingIndicator" style="display: none;">
        <div class="spinner"></div>
        <span>Mencari hasil...</span>
    </div>

    <div id="searchResults">
        <h2><i class="fas fa-list-ul"></i> Hasil Pencarian</h2>

        <div class="search-metrics">
            <i class="fas fa-clock"></i> <span id="searchTiming">Ditemukan dalam 0.35 detik</span>
        </div>

        <div class="result-list" id="resultList">
            <?php if (!empty($results)): ?>
                <?php foreach ($results as $result): ?>
                    <div class="result-item">
                        <div class="result-title">
                            <a href="<?php echo htmlspecialchars($result['url']); ?>" target="_blank"><?php echo htmlspecialchars($result['title']); ?></a>
                            <a href="#" class="view-route-button" onclick="toggleBreadcrumbs('<?php echo $result['pageId']; ?>')">
                                <i class="fas fa-route"></i> Lihat Rute Link
                            </a>
                        </div>
                        <div class="result-url">
                            <i class="fas fa-link"></i> <?php echo htmlspecialchars($result['url']); ?>
                        </div>
                        <div class="result-description">
                            <?php echo htmlspecialchars($result['description']); ?>
                        </div>
                        <div class="result-tags">
                            <?php if (!empty($result['tags'])): ?>
                                <?php foreach ($result['tags'] as $tag): ?>
                                    <span class="result-tag"><?php echo htmlspecialchars($tag); ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Tidak ada hasil ditemukan.</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
