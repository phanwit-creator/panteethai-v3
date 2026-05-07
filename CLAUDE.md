# CLAUDE.md — PanteeThai.com Development Context
# อัปเดตล่าสุด: พฤษภาคม 2026 │ Version: 3.0

## PROJECT OVERVIEW
Project: panteethai.com — Thailand Map & Travel Platform
Type: PHP web application (NO Node.js, NO Docker, NO React)
Server: HostNeverDie RH-Neptune Reseller Hosting (Shared)
Live URL: https://panteethai.com
Dev URL: https://dev.panteethai.com
GitHub: https://github.com/phanwit-creator/panteethai-v3

## TECH STACK (Exact versions)
PHP: 8.2 (set in DirectAdmin per domain)
Database: MariaDB 10.6 (MySQL 8.0 compatible)
Web Server: Apache 2.4 with mod_rewrite enabled
Map Library: Leaflet.js v1.9 (CDN)
Map Tiles PRIMARY: OpenFreeMap (https://tiles.openfreemap.org)
Map Tiles FALLBACK: Maptiler Cloud (free tier, key in .env)
CSS: Tailwind CSS v3 Play CDN (NO build step required)
Geocoding: Nominatim API (client-side JS only)
Routing: OSRM Demo (dev), self-hosted VPS (Phase 2)
Data: TAT Data API v2 (PHP server-side proxy + cache)
Analytics: Google Analytics 4
Ads: Google AdSense

## ABSOLUTE CONSTRAINTS (NEVER violate these)
1. NO Node.js, npm, or any server-side JavaScript
2. NO Docker or containerization
3. NO background processes or persistent connections
4. NO Python scripts in production
5. ALL DB queries MUST use PDO prepared statements
6. NEVER hardcode credentials — use .env via config.php
7. ALL user inputs MUST be sanitized before DB operations
8. PHP files MUST have <?php at line 1, no closing ?> tag
9. Error display MUST be OFF in production (log to file)
10. Admin routes MUST check session before any output

## FILE STRUCTURE
/public_html/panteethai/   ← web root
├── index.php              ← Homepage (map + search)
├── province/index.php     ← Province pages (77 provinces)
├── place/index.php        ← POI detail pages
├── blog/                  ← Travel articles
├── api/                   ← Internal JSON APIs
│   ├── places.php         ← GET POI GeoJSON
│   ├── search.php         ← FULLTEXT search
│   ├── nearby.php         ← Spatial radius search
│   ├── route.php          ← OSRM proxy + cache
│   └── tat-sync.php       ← CRON: TAT data sync
├── admin/                 ← Password protected CMS
├── includes/
│   ├── config.php         ← Load .env, constants
│   ├── db.php             ← PDO singleton, helpers
│   ├── tat.php            ← TAT API client + cache
│   └── seo.php            ← Meta tags, JSON-LD
└── assets/js/map.js       ← Leaflet configuration

## DATABASE SCHEMA (MariaDB 10.6)
TABLE: provinces
  slug VARCHAR(60) PK, name_th, name_en, region ENUM,
  lat DECIMAL(10,7), lng DECIMAL(10,7), zoom_level TINYINT,
  description TEXT, meta_title, meta_desc, image_url

TABLE: places
  id BIGINT PK AI, province_slug FK, name_th, name_en,
  category ENUM(temple|beach|nature|market|hotel|restaurant|museum|waterfall|island|other),
  location POINT SRID 4326 [SPATIAL INDEX],
  address, description TEXT, phone, website, hours JSON,
  price_thb INT, sha_certified BOOL, tat_id, source ENUM(tat|osm|manual),
  FULLTEXT INDEX ft_name(name_th, name_en)

TABLE: events
  id BIGINT PK AI, province_slug FK, name_th, name_en,
  event_date_start DATE, event_date_end DATE,
  location POINT SRID 4326, description TEXT,
  tat_id VARCHAR(100), image_url, status ENUM(active|inactive)

TABLE: articles
  id BIGINT PK AI, province_slug FK, slug VARCHAR(200) UNIQUE,
  title_th, title_en, content_th LONGTEXT, content_en LONGTEXT,
  featured_image, published_at TIMESTAMP, seo_keywords, status

TABLE: tat_cache
  cache_key VARCHAR(200) PK, endpoint, response_json LONGTEXT,
  fetched_at TIMESTAMP, expires_at TIMESTAMP [INDEX]

TABLE: route_cache
  cache_key VARCHAR(200) PK, from_lat, from_lng, to_lat, to_lng,
  profile ENUM(car|bike|foot), route_json LONGTEXT, cached_at TIMESTAMP

## CODING STANDARDS
PHP Patterns:
- db.php: PDO Singleton with static $instance
- All queries: $stmt = $pdo->prepare("SQL"); $stmt->execute([params]);
- Error handling: try/catch, log to /logs/error.log, never echo errors
- Config: parse_ini_file(__DIR__."/../.env") — never use $_ENV directly
- Functions: snake_case, Classes: PascalCase, Constants: UPPER_CASE

HTML/Template Patterns:
- Every page starts with: <?php require_once "../includes/head.php"; ?>
- head.php includes: SEO meta, JSON-LD, AdSense script async, Leaflet CSS
- Every page ends with: <?php require_once "../includes/footer.php"; ?>

JavaScript Patterns:
- Leaflet map variable: const map = L.map("map-container")
- Tile layer: L.tileLayer("https://tiles.openfreemap.org/...")
- Attribution: ALWAYS include "© OpenStreetMap contributors"
- API calls: fetch("/api/places.php?province="+slug).then(r=>r.json())
- Error handling: .catch(err => console.error("API Error:", err))

SQL Patterns:
- Spatial query: ST_Distance_Sphere(location, POINT(lng, lat)) < radius
- FULLTEXT: MATCH(name_th, name_en) AGAINST(:query IN BOOLEAN MODE)
- Always use LIMIT in queries, never fetch all rows

## API RESPONSE FORMAT (all /api/*.php)
{
  "success": true,
  "data": [...],
  "count": 25,
  "cached": true,
  "timestamp": "2026-05-01T10:00:00Z"
}
Error: { "success": false, "error": "message", "code": 400 }

## ENVIRONMENT VARIABLES (.env format)
DB_HOST=localhost
DB_NAME=panteethai_new
DB_USER=ptuser
DB_PASS=secure_password_here
TAT_API_KEY=your_tat_api_key
MAPTILER_KEY=your_maptiler_key
ADSENSE_PUB_ID=ca-pub-XXXXXXXXXX
APP_ENV=development
APP_DEBUG=true
APP_URL=https://dev.panteethai.com

## CURRENT SPRINT STATUS
Current Sprint: Sprint 1 (Foundation) — In Progress
Status: Core files committed, waiting for dev.panteethai.com SSL

Completed:
- [x] Master Plan v3 created
- [x] Server selected (RH-Neptune) + migrated
- [x] DB schema designed (db-schema.sql)
- [x] GitHub repo setup (panteethai-v3)
- [x] Local clone ready (~/Desktop/panteethai-v3)
- [x] .gitignore + CLAUDE.md created
- [x] Claude Project setup
- [x] TAT API key registered
- [x] Maptiler API key registered
- [x] Folder structure created (27 files)
- [x] Core PHP files: config.php, db.php, tat.php, seo.php
- [x] APIs: places.php, search.php, nearby.php
- [x] Frontend: map.js, search.js, route.js
- [x] index.php, province/index.php, sitemap.php, robots.txt, .htaccess

In Progress:
- [ ] dev.panteethai.com SSL (HostNeverDie support กำลังดำเนินการ)
- [ ] MariaDB panteethai_new database setup
- [ ] Deploy ไป dev.panteethai.com
- [ ] ทดสอบ PHP 8.2 + MariaDB ทุก feature

## USEFUL COMMANDS
Local dev: php -S localhost:8000 -t public_html/panteethai/
Deploy: ./deploy.sh dev  OR  ./deploy.sh prod
DB import: mysql -u root -p panteethai_new < schema.sql
Image convert: cwebp -q 80 input.jpg -o output.webp
Test TAT API: curl -H "Authorization: Bearer $TAT_API_KEY" https://tatdataapi.io/api/v2/places?province=10