from flask import request, jsonify, render_template
from app import app
from app.models import search_database, get_breadcrumbs

@app.route('/')
def index():
    return render_template('index.html')

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
