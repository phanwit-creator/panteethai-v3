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

-- ============================================================
-- Batch 2 — 5 more provinces × 5 places = 25 rows
-- chiang-rai | ayutthaya | nakhon-ratchasima | khon-kaen | surat-thani
-- ============================================================

INSERT INTO places
  (province_slug, name_th, name_en, category, location,
   address, price_thb, sha_certified, source, status)
VALUES

-- ============================================================
-- CHIANG RAI (5 places)
-- ============================================================

(
  'chiang-rai',
  'วัดร่องขุ่น',
  'Wat Rong Khun (White Temple)',
  'temple',
  ST_GeomFromText('POINT(99.7629 19.8238)', 4326),
  'ต.ป่าอ้อดอนชัย อ.เมือง จ.เชียงราย 57000',
  100,
  FALSE,
  'manual',
  'active'
),

(
  'chiang-rai',
  'วัดร่องเสือเต้น',
  'Wat Rong Suea Ten (Blue Temple)',
  'temple',
  ST_GeomFromText('POINT(99.8421 19.8917)', 4326),
  'ต.ริมกก อ.เมือง จ.เชียงราย 57100',
  0,
  FALSE,
  'manual',
  'active'
),

(
  'chiang-rai',
  'สามเหลี่ยมทองคำ',
  'Golden Triangle',
  'nature',
  ST_GeomFromText('POINT(100.0851 20.3568)', 4326),
  'ต.เวียง อ.เชียงแสน จ.เชียงราย 57150',
  0,
  FALSE,
  'manual',
  'active'
),

(
  'chiang-rai',
  'ดอยแม่สลอง',
  'Doi Mae Salong',
  'nature',
  ST_GeomFromText('POINT(99.6422 20.2767)', 4326),
  'ต.แม่สลองนอก อ.แม่ฟ้าหลวง จ.เชียงราย 57110',
  0,
  FALSE,
  'manual',
  'active'
),

(
  'chiang-rai',
  'สวนศิลปวัฒนธรรมแม่ฟ้าหลวง',
  'Mae Fah Luang Art and Culture Park',
  'museum',
  ST_GeomFromText('POINT(99.8516 20.2643)', 4326),
  '313 ต.รอบเวียง อ.เมือง จ.เชียงราย 57000',
  200,
  FALSE,
  'manual',
  'active'
),

-- ============================================================
-- AYUTTHAYA (5 places)
-- ============================================================

(
  'ayutthaya',
  'วัดพระศรีสรรเพชญ์',
  'Wat Phra Si Sanphet',
  'temple',
  ST_GeomFromText('POINT(100.5570 14.3556)', 4326),
  'ต.ประตูชัย อ.พระนครศรีอยุธยา จ.พระนครศรีอยุธยา 13000',
  50,
  FALSE,
  'manual',
  'active'
),

(
  'ayutthaya',
  'วัดมหาธาตุ',
  'Wat Mahathat',
  'temple',
  ST_GeomFromText('POINT(100.5592 14.3574)', 4326),
  'ต.ท่าวาสุกรี อ.พระนครศรีอยุธยา จ.พระนครศรีอยุธยา 13000',
  50,
  FALSE,
  'manual',
  'active'
),

(
  'ayutthaya',
  'วิหารพระมงคลบพิตร',
  'Wihan Phra Mongkhon Bophit',
  'temple',
  ST_GeomFromText('POINT(100.5591 14.3540)', 4326),
  'ต.ประตูชัย อ.พระนครศรีอยุธยา จ.พระนครศรีอยุธยา 13000',
  0,
  FALSE,
  'manual',
  'active'
),

(
  'ayutthaya',
  'วัดใหญ่ชัยมงคล',
  'Wat Yai Chai Mongkhon',
  'temple',
  ST_GeomFromText('POINT(100.5753 14.3427)', 4326),
  'ต.คลองสวนพลู อ.พระนครศรีอยุธยา จ.พระนครศรีอยุธยา 13000',
  20,
  FALSE,
  'manual',
  'active'
),

(
  'ayutthaya',
  'พิพิธภัณฑสถานแห่งชาติเจ้าสามพระยา',
  'Chao Sam Phraya National Museum',
  'museum',
  ST_GeomFromText('POINT(100.5627 14.3617)', 4326),
  'ถนนโรจนะ ต.ท่าวาสุกรี อ.พระนครศรีอยุธยา จ.พระนครศรีอยุธยา 13000',
  150,
  FALSE,
  'manual',
  'active'
),

-- ============================================================
-- NAKHON RATCHASIMA / KORAT (5 places)
-- ============================================================

(
  'nakhon-ratchasima',
  'อนุสาวรีย์ท้าวสุรนารี',
  'Thao Suranari Monument (Ya Mo)',
  'other',
  ST_GeomFromText('POINT(102.1010 14.9729)', 4326),
  'ถนนจอมพล ต.ในเมือง อ.เมือง จ.นครราชสีมา 30000',
  0,
  FALSE,
  'manual',
  'active'
),

(
  'nakhon-ratchasima',
  'อุทยานประวัติศาสตร์พิมาย',
  'Phimai Historical Park',
  'temple',
  ST_GeomFromText('POINT(102.5626 15.2227)', 4326),
  'ต.ในเมือง อ.พิมาย จ.นครราชสีมา 30110',
  100,
  FALSE,
  'manual',
  'active'
),

(
  'nakhon-ratchasima',
  'อุทยานแห่งชาติเขาใหญ่',
  'Khao Yai National Park',
  'nature',
  ST_GeomFromText('POINT(101.3722 14.4451)', 4326),
  'ต.หมูสี อ.ปากช่อง จ.นครราชสีมา 30130',
  400,
  FALSE,
  'manual',
  'active'
),

(
  'nakhon-ratchasima',
  'หมู่บ้านด่านเกวียน',
  'Dan Kwian Pottery Village',
  'market',
  ST_GeomFromText('POINT(102.1826 14.8858)', 4326),
  'ต.ด่านเกวียน อ.โชคชัย จ.นครราชสีมา 30190',
  0,
  FALSE,
  'manual',
  'active'
),

(
  'nakhon-ratchasima',
  'วัดสาลวัน',
  'Wat Salawan',
  'temple',
  ST_GeomFromText('POINT(102.1079 14.9708)', 4326),
  'ถนนสุรนารี ต.ในเมือง อ.เมือง จ.นครราชสีมา 30000',
  0,
  FALSE,
  'manual',
  'active'
),

-- ============================================================
-- KHON KAEN (5 places)
-- ============================================================

(
  'khon-kaen',
  'บึงแก่นนคร',
  'Bueng Kaen Nakhon Lake',
  'nature',
  ST_GeomFromText('POINT(102.8347 16.4322)', 4326),
  'ต.ในเมือง อ.เมือง จ.ขอนแก่น 40000',
  0,
  FALSE,
  'manual',
  'active'
),

(
  'khon-kaen',
  'วัดหนองแวง (พระมหาธาตุแก่นนคร)',
  'Wat Nong Wang (Phra Mahathat Kaen Nakhon)',
  'temple',
  ST_GeomFromText('POINT(102.8365 16.4290)', 4326),
  'ถนนกลางเมือง ต.ในเมือง อ.เมือง จ.ขอนแก่น 40000',
  0,
  FALSE,
  'manual',
  'active'
),

(
  'khon-kaen',
  'พิพิธภัณฑสถานแห่งชาติขอนแก่น',
  'Khon Kaen National Museum',
  'museum',
  ST_GeomFromText('POINT(102.8359 16.4318)', 4326),
  'ถนนหลังเมือง ต.ในเมือง อ.เมือง จ.ขอนแก่น 40000',
  100,
  FALSE,
  'manual',
  'active'
),

(
  'khon-kaen',
  'พิพิธภัณฑ์ไดโนเสาร์ภูเวียง',
  'Phu Wiang Dinosaur Museum',
  'museum',
  ST_GeomFromText('POINT(102.4126 16.6792)', 4326),
  'อุทยานแห่งชาติภูเวียง ต.ในเมือง อ.ภูเวียง จ.ขอนแก่น 40150',
  200,
  FALSE,
  'manual',
  'active'
),

(
  'khon-kaen',
  'หมู่บ้านผ้าไหมชนบท',
  'Chonnabot Silk Weaving Village',
  'market',
  ST_GeomFromText('POINT(102.6108 16.0893)', 4326),
  'ต.ชนบท อ.ชนบท จ.ขอนแก่น 40180',
  0,
  FALSE,
  'manual',
  'active'
),

-- ============================================================
-- SURAT THANI (5 places)
-- ============================================================

(
  'surat-thani',
  'เกาะเต่า',
  'Koh Tao',
  'island',
  ST_GeomFromText('POINT(99.8408 10.0956)', 4326),
  'ต.เกาะเต่า อ.เกาะพะงัน จ.สุราษฎร์ธานี 84360',
  0,
  FALSE,
  'manual',
  'active'
),

(
  'surat-thani',
  'เกาะพะงัน',
  'Koh Phangan',
  'island',
  ST_GeomFromText('POINT(100.0572 9.7459)', 4326),
  'ต.เกาะพะงัน อ.เกาะพะงัน จ.สุราษฎร์ธานี 84280',
  0,
  FALSE,
  'manual',
  'active'
),

(
  'surat-thani',
  'อุทยานแห่งชาติเขาสก',
  'Khao Sok National Park',
  'nature',
  ST_GeomFromText('POINT(98.9262 8.9106)', 4326),
  'ต.บ้านตาขุน อ.บ้านตาขุน จ.สุราษฎร์ธานี 84230',
  300,
  FALSE,
  'manual',
  'active'
),

(
  'surat-thani',
  'วัดพระบรมธาตุไชยา',
  'Wat Phra Borommathat Chaiya',
  'temple',
  ST_GeomFromText('POINT(99.2019 9.3960)', 4326),
  'ต.เวียง อ.ไชยา จ.สุราษฎร์ธานี 84110',
  0,
  FALSE,
  'manual',
  'active'
),

(
  'surat-thani',
  'ตลาดบ้านดอน',
  'Ban Don Night Market',
  'market',
  ST_GeomFromText('POINT(99.3296 9.1394)', 4326),
  'ถนนตลาดใหม่ ต.ตลาด อ.เมือง จ.สุราษฎร์ธานี 84000',
  0,
  FALSE,
  'manual',
  'active'
);
