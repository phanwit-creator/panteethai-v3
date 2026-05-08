<?php
require_once '../includes/config.php';
require_once '../includes/seo.php';

$seo = [
    'title'    => 'นโยบายความเป็นส่วนตัว — PanteeThai',
    'desc'     => 'นโยบายความเป็นส่วนตัวของ PanteeThai.com ครอบคลุมการเก็บข้อมูล คุกกี้ Google Analytics และ Google AdSense',
    'url'      => APP_URL . '/privacy-policy',
    'keywords' => 'นโยบายความเป็นส่วนตัว,privacy policy,PanteeThai',
];
$json_ld    = [];
$extra_head = '';

require_once '../includes/head.php';
?>
<body class="bg-gray-50">

    <!-- Navbar -->
    <nav class="bg-white shadow-sm h-16 flex items-center px-4 gap-3 relative z-[900]">
        <a href="/" class="text-xl font-bold text-green-600 flex-shrink-0">PanteeThai</a>
        <span class="text-gray-300 flex-shrink-0">/</span>
        <span class="text-gray-700 text-sm">นโยบายความเป็นส่วนตัว</span>
    </nav>

    <main class="max-w-3xl mx-auto px-4 py-10">

        <h1 class="text-2xl font-bold text-gray-800 mb-2">นโยบายความเป็นส่วนตัว</h1>
        <p class="text-sm text-gray-400 mb-8">อัปเดตล่าสุด: พฤษภาคม 2566</p>

        <div class="prose prose-sm max-w-none space-y-8 text-gray-700 leading-relaxed">

            <section>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">1. ภาพรวม</h2>
                <p>
                    PanteeThai.com ("เรา", "เว็บไซต์") ให้ความสำคัญกับความเป็นส่วนตัวของผู้ใช้งาน
                    นโยบายนี้อธิบายข้อมูลที่เราเก็บรวบรวม วิธีการใช้งาน และสิทธิ์ของท่านในฐานะผู้ใช้บริการ
                    การใช้งานเว็บไซต์ถือว่าท่านยอมรับนโยบายนี้
                </p>
            </section>

            <section>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">2. ข้อมูลที่เราเก็บรวบรวม</h2>
                <p class="mb-3">เราอาจเก็บรวบรวมข้อมูลดังต่อไปนี้โดยอัตโนมัติเมื่อท่านเข้าใช้งานเว็บไซต์:</p>
                <ul class="list-disc pl-6 space-y-1.5">
                    <li>ที่อยู่ IP และประเภทของเบราว์เซอร์</li>
                    <li>หน้าที่เข้าชมและระยะเวลาการใช้งาน</li>
                    <li>อุปกรณ์และระบบปฏิบัติการที่ใช้งาน</li>
                    <li>URL ที่มาก่อนเข้าสู่เว็บไซต์ (Referrer URL)</li>
                    <li>ข้อมูลที่ท่านกรอกในแบบฟอร์มติดต่อ (ชื่อ อีเมล ข้อความ)</li>
                </ul>
                <p class="mt-3">
                    เราไม่เก็บข้อมูลบัตรเครดิต รหัสผ่าน หรือข้อมูลส่วนตัวที่ละเอียดอ่อนอื่น ๆ
                </p>
            </section>

            <section>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">3. คุกกี้ (Cookies)</h2>
                <p class="mb-3">
                    เว็บไซต์ใช้คุกกี้เพื่อเพิ่มประสิทธิภาพการใช้งาน คุกกี้คือไฟล์ขนาดเล็กที่บันทึกในอุปกรณ์ของท่าน
                    ประเภทคุกกี้ที่เราใช้ได้แก่:
                </p>
                <ul class="list-disc pl-6 space-y-2">
                    <li>
                        <strong>คุกกี้จำเป็น (Essential Cookies):</strong>
                        ช่วยให้เว็บไซต์ทำงานได้อย่างถูกต้อง ไม่สามารถปิดการใช้งานได้
                    </li>
                    <li>
                        <strong>คุกกี้วิเคราะห์ (Analytics Cookies):</strong>
                        ใช้โดย Google Analytics เพื่อเก็บสถิติการเข้าชมแบบไม่ระบุตัวตน
                        ท่านสามารถปฏิเสธได้ผ่านการตั้งค่าเบราว์เซอร์
                    </li>
                    <li>
                        <strong>คุกกี้โฆษณา (Advertising Cookies):</strong>
                        ใช้โดย Google AdSense เพื่อแสดงโฆษณาที่เกี่ยวข้องกับความสนใจของท่าน
                        ข้อมูลนี้ถูกจัดการโดย Google ตามนโยบายความเป็นส่วนตัวของ Google
                    </li>
                </ul>
                <p class="mt-3">
                    ท่านสามารถตั้งค่าเบราว์เซอร์ให้ปฏิเสธคุกกี้ทั้งหมดได้
                    อย่างไรก็ตามบางส่วนของเว็บไซต์อาจทำงานไม่สมบูรณ์
                </p>
            </section>

            <section>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">4. Google Analytics</h2>
                <p class="mb-3">
                    เราใช้ Google Analytics เพื่อวิเคราะห์พฤติกรรมการใช้งานเว็บไซต์
                    Google Analytics เก็บข้อมูลเช่น จำนวนผู้เข้าชม หน้าที่เป็นที่นิยม และระยะเวลาการใช้งาน
                    ข้อมูลนี้ถูกส่งไปยังเซิร์ฟเวอร์ของ Google และอาจถูกประมวลผลในสหรัฐอเมริกา
                </p>
                <p>
                    ท่านสามารถปฏิเสธการเก็บข้อมูลของ Google Analytics โดยติดตั้ง
                    <a href="https://tools.google.com/dlpage/gaoptout" target="_blank" rel="noopener"
                       class="text-green-600 hover:underline">Google Analytics Opt-out Browser Add-on</a>
                    หรืออ่านนโยบายความเป็นส่วนตัวของ Google ได้ที่
                    <a href="https://policies.google.com/privacy" target="_blank" rel="noopener"
                       class="text-green-600 hover:underline">policies.google.com/privacy</a>
                </p>
            </section>

            <section>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">5. Google AdSense และผู้ลงโฆษณาบุคคลที่สาม</h2>
                <p class="mb-3">
                    เว็บไซต์แสดงโฆษณาผ่านบริการ Google AdSense
                    Google และพันธมิตรโฆษณาบุคคลที่สามอาจใช้คุกกี้เพื่อแสดงโฆษณาตามการเข้าชมเว็บไซต์ก่อนหน้า
                    ของท่าน (Remarketing) รวมถึงโฆษณาที่ปรับตามความสนใจ (Personalized Ads)
                </p>
                <p class="mb-3">
                    ผู้ลงโฆษณาบุคคลที่สามที่ให้บริการผ่าน Google AdSense ปฏิบัติตาม
                    <a href="https://policies.google.com/technologies/ads" target="_blank" rel="noopener"
                       class="text-green-600 hover:underline">นโยบายโฆษณาของ Google</a>
                    ซึ่งห้ามเก็บข้อมูลส่วนตัวโดยไม่ได้รับอนุญาต
                </p>
                <p>
                    ท่านสามารถจัดการการตั้งค่าโฆษณาส่วนบุคคลได้ที่
                    <a href="https://adssettings.google.com" target="_blank" rel="noopener"
                       class="text-green-600 hover:underline">Google Ads Settings</a>
                    หรือเลือกออกจากโฆษณาส่วนบุคคลผ่าน
                    <a href="https://www.aboutads.info/choices/" target="_blank" rel="noopener"
                       class="text-green-600 hover:underline">aboutads.info</a>
                </p>
            </section>

            <section>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">6. วัตถุประสงค์การใช้ข้อมูล</h2>
                <p class="mb-2">เราใช้ข้อมูลที่เก็บรวบรวมเพื่อ:</p>
                <ul class="list-disc pl-6 space-y-1.5">
                    <li>พัฒนาและปรับปรุงเนื้อหาและฟีเจอร์ของเว็บไซต์</li>
                    <li>วิเคราะห์สถิติการใช้งานเพื่อประเมินประสิทธิภาพ</li>
                    <li>แสดงโฆษณาที่เกี่ยวข้องกับความสนใจของผู้ใช้</li>
                    <li>ตอบคำถามและข้อเสนอแนะที่ส่งผ่านแบบฟอร์มติดต่อ</li>
                </ul>
            </section>

            <section>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">7. การแชร์ข้อมูลกับบุคคลที่สาม</h2>
                <p>
                    เราไม่ขาย ให้เช่า หรือแชร์ข้อมูลส่วนตัวของท่านกับบุคคลที่สามเพื่อวัตถุประสงค์เชิงพาณิชย์
                    ยกเว้นกรณีที่กำหนดไว้ในนโยบายนี้ (Google Analytics, Google AdSense)
                    หรือกรณีที่กฎหมายกำหนดให้ต้องเปิดเผย
                </p>
            </section>

            <section>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">8. ความปลอดภัยของข้อมูล</h2>
                <p>
                    เราใช้มาตรการที่เหมาะสมเพื่อปกป้องข้อมูลจากการเข้าถึงโดยไม่ได้รับอนุญาต
                    เว็บไซต์ใช้การเชื่อมต่อ HTTPS เพื่อเข้ารหัสข้อมูลระหว่างเบราว์เซอร์และเซิร์ฟเวอร์
                    อย่างไรก็ตามไม่มีระบบใดที่ปลอดภัย 100%
                </p>
            </section>

            <section>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">9. สิทธิ์ของท่าน</h2>
                <p class="mb-2">ท่านมีสิทธิ์:</p>
                <ul class="list-disc pl-6 space-y-1.5">
                    <li>ขอทราบว่าเราเก็บข้อมูลอะไรเกี่ยวกับท่านบ้าง</li>
                    <li>ขอให้ลบข้อมูลส่วนตัวของท่าน</li>
                    <li>ปฏิเสธการรับโฆษณาส่วนบุคคล</li>
                    <li>ร้องเรียนต่อหน่วยงานคุ้มครองข้อมูลส่วนบุคคล</li>
                </ul>
            </section>

            <section>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">10. การเปลี่ยนแปลงนโยบาย</h2>
                <p>
                    เราอาจปรับปรุงนโยบายนี้เป็นครั้งคราว วันที่อัปเดตล่าสุดจะปรากฏที่ด้านบนของหน้านี้
                    การใช้งานเว็บไซต์ต่อไปหลังจากการเปลี่ยนแปลงถือว่าท่านยอมรับนโยบายใหม่
                </p>
            </section>

            <section class="bg-green-50 rounded-xl p-5 border border-green-100">
                <h2 class="text-lg font-semibold text-gray-800 mb-2">11. ติดต่อเรา</h2>
                <p class="mb-2">
                    หากมีคำถามเกี่ยวกับนโยบายความเป็นส่วนตัว กรุณาติดต่อ:
                </p>
                <p class="font-medium text-gray-800">PanteeThai.com</p>
                <p>อีเมล:
                    <a href="mailto:phanwit@gmail.com" class="text-green-600 hover:underline font-medium">
                        phanwit@gmail.com
                    </a>
                </p>
                <p class="mt-2">
                    หรือผ่าน <a href="/contact" class="text-green-600 hover:underline">หน้าติดต่อเรา</a>
                </p>
            </section>

        </div>
    </main>

<?php require_once '../includes/footer.php';
