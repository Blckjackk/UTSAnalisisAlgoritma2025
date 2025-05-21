<?php
// Koneksi ke database MySQL
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'db_andal';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Buat tabel jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    url TEXT UNIQUE,
    title TEXT,
    content TEXT
)");

$conn->query("CREATE TABLE IF NOT EXISTS word_counts (
    page_id INT,
    word TEXT,
    count INT,
    FOREIGN KEY (page_id) REFERENCES pages (id)
)");

$conn->query("CREATE TABLE IF NOT EXISTS links (
    from_page_id INT,
    to_page_id INT,
    FOREIGN KEY (from_page_id) REFERENCES pages (id),
    FOREIGN KEY (to_page_id) REFERENCES pages (id)
)");

// Bersihkan teks
function clean_text($text) {
    return strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $text));
}

// Tokenisasi teks
function tokenize($text) {
    return explode(' ', clean_text($text));
}

// Simpan halaman dan word count
function save_page($conn, $url, $title, $content) {
    $stmt = $conn->prepare("INSERT INTO pages (url, title, content) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
    $stmt->bind_param('sss', $url, $title, $content);
    $stmt->execute();
    $page_id = $stmt->insert_id;

    $words = tokenize($content);
    $word_freq = array_count_values($words);

    $stmt = $conn->prepare("INSERT INTO word_counts (page_id, word, count) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE count=count+VALUES(count)");
    foreach ($word_freq as $word => $count) {
        $stmt->bind_param('isi', $page_id, $word, $count);
        $stmt->execute();
    }
}

// Simpan hubungan antar halaman
function save_link($conn, $from_url, $to_url) {
    $stmt = $conn->prepare("SELECT id FROM pages WHERE url = ?");

    $stmt->bind_param('s', $from_url);
    $stmt->execute();
    $from_page_id = $stmt->get_result()->fetch_assoc()['id'];

    $stmt->bind_param('s', $to_url);
    $stmt->execute();
    $to_page_id = $stmt->get_result()->fetch_assoc()['id'];

    if ($from_page_id && $to_page_id) {
        $stmt = $conn->prepare("INSERT INTO links (from_page_id, to_page_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE from_page_id=from_page_id");
        $stmt->bind_param('ii', $from_page_id, $to_page_id);
        $stmt->execute();
    }
}

// Crawler dengan BFS
function crawl_bfs($conn, $seed_url, $max_pages = 100) {
    $visited = [];
    $queue = [$seed_url];
    $count = 0;

    while (!empty($queue) && $count < $max_pages) {
        $url = array_shift($queue);

        if (in_array($url, $visited)) {
            continue;
        }

        $visited[] = $url;

        $html = @file_get_contents($url);
        if ($html === FALSE) {
            continue;
        }

        $dom = new DOMDocument();
        @$dom->loadHTML($html);

        $title = $dom->getElementsByTagName('title')->item(0)->nodeValue ?? '';
        $content = '';
        foreach ($dom->getElementsByTagName('p') as $p) {
            $content .= $p->nodeValue . ' ';
        }

        save_page($conn, $url, $title, $content);
        $count++;

        foreach ($dom->getElementsByTagName('a') as $a) {
            $href = $a->getAttribute('href');
            if (strpos($href, '/') === 0) {
                $href = $seed_url . $href;
            }
            if (strpos($href, $seed_url) === 0 && !in_array($href, $visited)) {
                $queue[] = $href;
                save_link($conn, $url, $href);
            }
        }
    }
}

// Jalankan crawler
$seed_url = 'https://www.tempo.co';
crawl_bfs($conn, $seed_url);

$conn->close();
?>
