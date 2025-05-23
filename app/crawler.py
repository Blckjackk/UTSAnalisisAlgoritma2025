import requests
import time
from bs4 import BeautifulSoup
from urllib.parse import urljoin, urlparse, urldefrag
import mysql.connector

def get_db_connection():
    return mysql.connector.connect(
        host="localhost",
        user="root",
        password="",
        database="db_andal"
    )

def clean_text(text):
    return ''.join(e for e in text if e.isalnum() or e.isspace()).lower()

def tokenize(text):
    return text.split()

def save_page(conn, url, title, content):
    c = conn.cursor()
    c.execute('''
        INSERT INTO pages (url, title, content)
        VALUES (%s, %s, %s)
        ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), title=%s, content=%s
    ''', (url, title, content, title, content))
    page_id = c.lastrowid

    words = tokenize(clean_text(content))
    word_counts = {word: words.count(word) for word in set(words)}

    for word, count in word_counts.items():
        c.execute('''
            INSERT INTO word_counts (page_id, word, count)
            VALUES (%s, %s, %s)
            ON DUPLICATE KEY UPDATE count = VALUES(count)
        ''', (page_id, word, count))

    conn.commit()
    return page_id

def save_link(conn, from_id, to_id):
    c = conn.cursor()
    c.execute('''
        INSERT INTO links (from_page_id, to_page_id)
        VALUES (%s, %s)
        ON DUPLICATE KEY UPDATE from_page_id = from_page_id
    ''', (from_id, to_id))
    conn.commit()

def is_same_domain(base_url, test_url):
    return urlparse(base_url).netloc == urlparse(test_url).netloc

def crawl(seed_url):
    conn = get_db_connection()
    visited = {}
    queue = [seed_url]
    link_buffer = []

    while queue:
        current_url = queue.pop(0)
        current_url, _ = urldefrag(current_url)

        if current_url in visited:
            continue

        try:
            print(f"Crawling: {current_url}")
            response = requests.get(current_url, timeout=10)
            if response.status_code != 200:
                continue

            soup = BeautifulSoup(response.text, 'html.parser')
            title = soup.title.string.strip() if soup.title else 'No Title'
            content = soup.get_text()
            page_id = save_page(conn, current_url, title, content)
            visited[current_url] = page_id

            for link in soup.find_all('a', href=True):
                full_url = urljoin(current_url, link['href'])
                full_url, _ = urldefrag(full_url)

                if is_same_domain(seed_url, full_url):
                    if full_url not in visited and full_url not in queue:
                        queue.append(full_url)
                    if full_url in visited:
                        link_buffer.append((page_id, visited[full_url]))

            time.sleep(1)  # Delay untuk menghormati server

        except Exception as e:
            print(f"Error: {e} - URL: {current_url}")

    for from_id, to_id in link_buffer:
        save_link(conn, from_id, to_id)

    conn.close()
    print("Crawling complete.")
