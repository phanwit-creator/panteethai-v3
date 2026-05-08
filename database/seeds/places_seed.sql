-- ============================================================
-- PanteeThai.com v3 — Places Seed Data
-- 5 sample places × 5 provinces = 25 rows
--
-- Province slugs match provinces table (db-schema.sql):
--   bangkok | chiang-mai | phuket | krabi | samui
--   NOTE: Koh Samui slug is 'samui', NOT 'koh-samui'
--
-- location = ST_GeomFromText('POINT(lng lat)', 4326)
--            X = longitude, Y = latitude  (WKT axis order)
-- ============================================================

SET NAMES utf8mb4;

INSERT INTO places
  (province_slug, name_th, name_en, category, location,
   address, price_thb, sha_certified, source, status)
VALUES

-- ============================================================
-- BANGKOK (5 places)
-- ============================================================

(
  'bangkok',
  'วัดพระแก้ว',
  'Wat Phra Kaew (Temple of the Emerald Buddha)',
  'temple',
  ST_GeomFromText('POINT(100.4928 13.7516)', 4326),
  'ถนนหน้าพระลาน แขวงพระบรมมหาราชวัง เขตพระนคร กรุงเทพฯ 10200',
  500,
  FALSE,
  'manual',
  'active'
),

(
  'bangkok',
  'วัดโพธิ์',
  'Wat Pho (Temple of the Reclining Buddha)',
  'temple',
  ST_GeomFromText('POINT(100.4934 13.7465)', 4326),
  '2 ถนนสนามไชย แขวงพระบรมมหาราชวัง เขตพระนคร กรุงเทพฯ 10200',
  200,
  FALSE,
  'manual',
  'active'
),

(
  'bangkok',
  'วัดอรุณราชวราราม',
  'Wat Arun (Temple of Dawn)',
  'temple',
  ST_GeomFromText('POINT(100.4888 13.7437)', 4326),
  '158 ถนนวังเดิม แขวงวัดอรุณ เขตบางกอกใหญ่ กรุงเทพฯ 10600',
  100,
  FALSE,
  'manual',
  'active'
),

(
  'bangkok',
  'ตลาดนัดจตุจักร',
  'Chatuchak Weekend Market',
  'market',
  ST_GeomFromText('POINT(100.5502 13.7999)', 4326),
  'ถนนกำแพงเพชร 2 แขวงจตุจักร เขตจตุจักร กรุงเทพฯ 10900',
  0,
  FALSE,
  'manual',
  'active'
),

(
  'bangkok',
  'พิพิธภัณฑสถานแห่งชาติ กรุงเทพ',
  'National Museum Bangkok',
  'museum',
  ST_GeomFromText('POINT(100.4900 13.7574)', 4326),
  '4 ถนนหน้าพระธาตุ แขวงพระบรมมหาราชวัง เขตพระนคร กรุงเทพฯ 10200',
  200,
  FALSE,
  'manual',
  'active'
),

-- ============================================================
-- CHIANG MAI (5 places)
-- ============================================================

(
  'chiang-mai',
  'วัดพระธาตุดอยสุเทพ',
  'Wat Phra That Doi Suthep',
  'temple',
  ST_GeomFromText('POINT(98.9219 18.8047)', 4326),
  'ดอยสุเทพ อ.เมือง จ.เชียงใหม่ 50200',
  50,
  FALSE,
  'manual',
  'active'
),

(
  'chiang-mai',
  'วัดเจดีย์หลวง',
  'Wat Chedi Luang',
  'temple',
  ST_GeomFromText('POINT(98.9842 18.7872)', 4326),
  '103 ถนนพระปกเกล้า ต.พระสิงห์ อ.เมือง จ.เชียงใหม่ 50200',
  0,
  FALSE,
  'manual',
  'active'
),

(
  'chiang-mai',
  'วัดพระสิงห์',
  'Wat Phra Singh',
  'temple',
  ST_GeomFromText('POINT(98.9852 18.7868)', 4326),
  'ถนนสามล้าน ต.พระสิงห์ อ.เมือง จ.เชียงใหม่ 50200',
  0,
  FALSE,
  'manual',
  'active'
),

(
  'chiang-mai',
  'อุทยานแห่งชาติดอยอินทนนท์',
  'Doi Inthanon National Park',
  'nature',
  ST_GeomFromText('POINT(98.4864 18.5876)', 4326),
  'ต.บ้านหลวง อ.จอมทอง จ.เชียงใหม่ 50160',
  300,
  FALSE,
  'manual',
  'active'
),

(
  'chiang-mai',
  'ตลาดวโรรส',
  'Warorot Market (Kad Luang)',
  'market',
  ST_GeomFromText('POINT(99.0012 18.7919)', 4326),
  'ถนนวิชยานนท์ ต.ช้างม่อย อ.เมือง จ.เชียงใหม่ 50300',
  0,
  FALSE,
  'manual',
  'active'
),

-- ============================================================
-- PHUKET (5 places)
-- ============================================================

(
  'phuket',
  'หาดป่าตอง',
  'Patong Beach',
  'beach',
  ST_GeomFromText('POINT(98.2975 7.8962)', 4326),
  'ถนนหาดป่าตอง ต.ป่าตอง อ.กะทู้ จ.ภูเก็ต 83150',
  0,
  FALSE,
  'manual',
  'active'
),

(
  'phuket',
  'วัดฉลอง',
  'Wat Chalong (Wat Chaiyathararam)',
  'temple',
  ST_GeomFromText('POINT(98.3376 7.8023)', 4326),
  '70 ถนนเจ้าฟ้าตะวันออก ต.ฉลอง อ.เมือง จ.ภูเก็ต 83130',
  0,
  FALSE,
  'manual',
  'active'
),

(
  'phuket',
  'แหลมพรหมเทพ',
  'Promthep Cape',
  'nature',
  ST_GeomFromText('POINT(98.3026 7.7642)', 4326),
  'ต.ราไวย์ อ.เมือง จ.ภูเก็ต 83130',
  0,
  FALSE,
  'manual',
  'active'
),

(
  'phuket',
  'หาดกะรน',
  'Karon Beach',
  'beach',
  ST_GeomFromText('POINT(98.2988 7.8461)', 4326),
  'ถนนปฏัก ต.กะรน อ.เมือง จ.ภูเก็ต 83100',
  0,
  FALSE,
  'manual',
  'active'
),

(
  'phuket',
  'หาดกมลา',
  'Kamala Beach',
  'beach',
  ST_GeomFromText('POINT(98.2815 7.9474)', 4326),
  'ต.กมลา อ.กะทู้ จ.ภูเก็ต 83150',
  0,
  FALSE,
  'manual',
  'active'
),

-- ============================================================
-- KRABI (5 places)
-- ============================================================

(
  'krabi',
  'หาดอ่าวนาง',
  'Ao Nang Beach',
  'beach',
  ST_GeomFromText('POINT(98.8283 8.0313)', 4326),
  'ต.อ่าวนาง อ.เมือง จ.กระบี่ 81000',
  0,
  FALSE,
  'manual',
  'active'
),

(
  'krabi',
  'หาดรายเล',
  'Railay Beach',
  'beach',
  ST_GeomFromText('POINT(98.8367 8.0116)', 4326),
  'แหลมรายเล ต.อ่าวนาง อ.เมือง จ.กระบี่ 81000',
  0,
  FALSE,
  'manual',
  'active'
),

(
  'krabi',
  'เกาะพีพีดอน',
  'Koh Phi Phi Don',
  'island',
  ST_GeomFromText('POINT(98.7767 7.7408)', 4326),
  'ต.เกาะพีพี อ.เมือง จ.กระบี่ 81000',
  0,
  FALSE,
  'manual',
  'active'
),

(
  'krabi',
  'วัดถ้ำเสือ',
  'Tiger Cave Temple (Wat Tham Suea)',
  'temple',
  ST_GeomFromText('POINT(98.9127 8.0861)', 4326),
  'ต.เขาทอง อ.เมือง จ.กระบี่ 81000',
  0,
  FALSE,
  'manual',
  'active'
),

(
  'krabi',
  'สระมรกต',
  'Emerald Pool (Sa Morakot)',
  'nature',
  ST_GeomFromText('POINT(99.0949 7.9388)', 4326),
  'เขตรักษาพันธุ์สัตว์ป่าคลองแสง ต.คลองท่อมใต้ อ.คลองท่อม จ.กระบี่ 81120',
  200,
  FALSE,
  'manual',
  'active'
),

-- ============================================================
-- KOH SAMUI  (province_slug = 'samui' per provinces table)
-- ============================================================

(
  'samui',
  'หาดเฉวง',
  'Chaweng Beach',
  'beach',
  ST_GeomFromText('POINT(100.0618 9.5440)', 4326),
  'ต.บ่อผุด อ.เกาะสมุย จ.สุราษฎร์ธานี 84320',
  0,
  FALSE,
  'manual',
  'active'
),

(
  'samui',
  'หาดละไม',
  'Lamai Beach',
  'beach',
  ST_GeomFromText('POINT(100.0491 9.4757)', 4326),
  'ต.มะเร็ต อ.เกาะสมุย จ.สุราษฎร์ธานี 84310',
  0,
  FALSE,
  'manual',
  'active'
),

(
  'samui',
  'น้ำตกหน้าเมือง',
  'Na Muang Waterfall',
  'waterfall',
  ST_GeomFromText('POINT(99.9924 9.4700)', 4326),
  'ต.มะเร็ต อ.เกาะสมุย จ.สุราษฎร์ธานี 84310',
  0,
  FALSE,
  'manual',
  'active'
),

(
  'samui',
  'วัดพระใหญ่ (พระพุทธมิ่งมงคลเอกนาคคีรี)',
  'Big Buddha Temple (Wat Phra Yai)',
  'temple',
  ST_GeomFromText('POINT(100.0634 9.5499)', 4326),
  'เกาะฟาน ต.บ่อผุด อ.เกาะสมุย จ.สุราษฎร์ธานี 84320',
  0,
  FALSE,
  'manual',
  'active'
),

(
  'samui',
  'หมู่บ้านชาวประมงบ่อผุด',
  'Bophut Fisherman''s Village',
  'market',
  ST_GeomFromText('POINT(100.0165 9.5373)', 4326),
  'ต.บ่อผุด อ.เกาะสมุย จ.สุราษฎร์ธานี 84320',
  0,
  FALSE,
  'manual',
  'active'
);
