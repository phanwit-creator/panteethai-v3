-- ============================================
-- PanteeThai.com v3 — MariaDB 10.6 Schema
-- อัปเดต: พฤษภาคม 2026
-- ============================================

SET NAMES utf8mb4;
SET time_zone = '+07:00';

-- ตาราง provinces (77 จังหวัด)
CREATE TABLE provinces (
  slug        VARCHAR(60)  PRIMARY KEY,
  name_th     VARCHAR(100) NOT NULL,
  name_en     VARCHAR(100) NOT NULL,
  region      ENUM('เหนือ','ใต้','กลาง','ออก','ตอ.เฉียงเหนือ','ตะวันตก') NOT NULL,
  lat         DECIMAL(10,7) NOT NULL,
  lng         DECIMAL(10,7) NOT NULL,
  zoom_level  TINYINT DEFAULT 11,
  description TEXT,
  meta_title  VARCHAR(160),
  meta_desc   VARCHAR(320),
  image_url   VARCHAR(500),
  updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตาราง places (สถานที่ท่องเที่ยว)
CREATE TABLE places (
  id            BIGINT AUTO_INCREMENT PRIMARY KEY,
  province_slug VARCHAR(60)  NOT NULL,
  name_th       VARCHAR(200) NOT NULL,
  name_en       VARCHAR(200),
  category      ENUM('temple','beach','nature','market','hotel','restaurant','museum','waterfall','island','other'),
  location      POINT NOT NULL SRID 4326,
  address       VARCHAR(500),
  description   TEXT,
  phone         VARCHAR(30),
  website       VARCHAR(500),
  hours         JSON,
  price_thb     INT DEFAULT 0,
  sha_certified BOOLEAN DEFAULT FALSE,
  tat_id        VARCHAR(100),
  source        ENUM('tat','osm','manual') DEFAULT 'manual',
  status        ENUM('active','inactive') DEFAULT 'active',
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (province_slug) REFERENCES provinces(slug),
  SPATIAL INDEX(location),
  FULLTEXT INDEX ft_name (name_th, name_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตาราง events (กิจกรรม/เทศกาล)
CREATE TABLE events (
  id               BIGINT AUTO_INCREMENT PRIMARY KEY,
  province_slug    VARCHAR(60) NOT NULL,
  name_th          VARCHAR(200) NOT NULL,
  name_en          VARCHAR(200),
  event_date_start DATE,
  event_date_end   DATE,
  location         POINT SRID 4326,
  description      TEXT,
  tat_id           VARCHAR(100),
  image_url        VARCHAR(500),
  status           ENUM('active','inactive') DEFAULT 'active',
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (province_slug) REFERENCES provinces(slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตาราง articles (บทความท่องเที่ยว)
CREATE TABLE articles (
  id             BIGINT AUTO_INCREMENT PRIMARY KEY,
  province_slug  VARCHAR(60),
  slug           VARCHAR(200) NOT NULL UNIQUE,
  title_th       VARCHAR(300) NOT NULL,
  title_en       VARCHAR(300),
  content_th     LONGTEXT,
  content_en     LONGTEXT,
  featured_image VARCHAR(500),
  seo_keywords   VARCHAR(500),
  status         ENUM('draft','published') DEFAULT 'draft',
  published_at   TIMESTAMP NULL,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (province_slug) REFERENCES provinces(slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตาราง tat_cache (cache TAT API)
CREATE TABLE tat_cache (
  cache_key     VARCHAR(200) PRIMARY KEY,
  endpoint      VARCHAR(200),
  response_json LONGTEXT,
  fetched_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  expires_at    TIMESTAMP,
  INDEX(expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตาราง route_cache (cache เส้นทาง OSRM)
CREATE TABLE route_cache (
  cache_key  VARCHAR(200) PRIMARY KEY,
  from_lat   DECIMAL(10,7),
  from_lng   DECIMAL(10,7),
  to_lat     DECIMAL(10,7),
  to_lng     DECIMAL(10,7),
  profile    ENUM('car','bike','foot') DEFAULT 'car',
  route_json LONGTEXT,
  cached_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Seed Data: 10 จังหวัดนำร่อง
-- ============================================
INSERT INTO provinces (slug, name_th, name_en, region, lat, lng, zoom_level) VALUES
('bangkok',       'กรุงเทพมหานคร', 'Bangkok',      'กลาง',          13.7563, 100.5018, 11),
('chiang-mai',    'เชียงใหม่',     'Chiang Mai',   'เหนือ',         18.7883, 98.9853,  11),
('phuket',        'ภูเก็ต',        'Phuket',       'ใต้',           7.8804,  98.3923,  11),
('krabi',         'กระบี่',        'Krabi',        'ใต้',           8.0863,  98.9063,  11),
('surat-thani',   'สุราษฎร์ธานี',  'Surat Thani',  'ใต้',           9.1382,  99.3214,  10),
('chiang-rai',    'เชียงราย',      'Chiang Rai',   'เหนือ',         19.9105, 99.8406,  11),
('ayutthaya',     'พระนครศรีอยุธยา','Ayutthaya',   'กลาง',          14.3692, 100.5877, 12),
('nakhon-ratchasima','นครราชสีมา', 'Nakhon Ratchasima','ตอ.เฉียงเหนือ',14.9799, 102.0978, 11),
('khon-kaen',     'ขอนแก่น',       'Khon Kaen',    'ตอ.เฉียงเหนือ', 16.4419, 102.8360, 11),
('samui',         'เกาะสมุย',      'Koh Samui',    'ใต้',           9.5120,  100.0136, 12);