from flask import Flask, request, jsonify
import mysql.connector

app = Flask(__name__)

def search_database(query, limit):
    conn = mysql.connector.connect(
        host="localhost",
        user="root",  # Ganti dengan username MySQL Anda
        password="",  # Ganti dengan password MySQL Anda
        database="db_andal"
    )
    c = conn.cursor()

    query_words = query.lower().split()
    results = {}

    for word in query_words:
        c.execute('''
            SELECT pages.id, pages.url, pages.title, pages.content, SUM(word_counts.count) as total
            FROM word_counts
            JOIN pages ON pages.id = word_counts.page_id
            WHERE word_counts.word = %s
            GROUP BY pages.id
            ORDER BY total DESC
            LIMIT %s
        ''', (word, limit))

        for row in c.fetchall():
            page_id, url, title, content, score = row
            if page_id not in results:
                results[page_id] = {
                    'pageId': page_id,
                    'url': url,
                    'title': title,
                    'description': content[:200] + '...',
                    'tags': [],
                    'score': 0
                }
            results[page_id]['score'] += score

    conn.close()
    return sorted(results.values(), key=lambda x: x['score'], reverse=True)

def get_breadcrumbs(page_id):
    conn = mysql.connector.connect(
        host="localhost",
        user="root",  # Ganti dengan username MySQL Anda
        password="",  # Ganti dengan password MySQL Anda
        database="db_andal"
    )
    c = conn.cursor()

    breadcrumbs = []
    current_page_id = page_id

    while current_page_id:
        c.execute('''
            SELECT url, title FROM pages WHERE id = %s
        ''', (current_page_id,))
        row = c.fetchone()

        if not row:
            break

        url, title = row
        breadcrumbs.append({'url': url, 'title': title})

        # Cari halaman sebelumnya (jika ada)
        c.execute('''
            SELECT page_id FROM word_counts WHERE word = %s LIMIT 1
        ''', (url,))
        parent_row = c.fetchone()
        current_page_id = parent_row[0] if parent_row else None

    conn.close()
    return breadcrumbs[::-1]  # Balikkan urutan untuk menampilkan dari root ke halaman hasil

@app.route('/search', methods=['GET'])
def search():
    query = request.args.get('query', '')
    limit = int(request.args.get('limit', 10))

    if not query:
        return jsonify([])

    results = search_database(query, limit)
    return jsonify(results)

@app.route('/breadcrumbs/<int:page_id>', methods=['GET'])
def breadcrumbs(page_id):
    breadcrumbs = get_breadcrumbs(page_id)
    return jsonify(breadcrumbs)

if __name__ == '__main__':
    app.run(debug=True)
