from app import app
from app.crawler import crawl

if __name__ == '__main__':
    app.run(debug=True)
    print("Memulai proses crawling...")
    crawl(seed_url='https://www.tempo.co')
    print("Proses crawling selesai.")
    app.run(debug=True)
