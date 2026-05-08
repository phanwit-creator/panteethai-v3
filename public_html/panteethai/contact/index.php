<?php
require_once '../includes/config.php';
require_once '../includes/seo.php';

$seo = [
    'title'    => 'ติดต่อเรา — PanteeThai',
    'desc'     => 'ติดต่อทีมงาน PanteeThai.com สอบถามข้อมูล แนะนำสถานที่ หรือรายงานข้อผิดพลาด',
    'url'      => APP_URL . '/contact',
    'keywords' => 'ติดต่อ PanteeThai,contact,แผนที่ท่องเที่ยวไทย',
];
$json_ld = [
    '<script type="application/ld+json">'
    . json_encode([
        '@context' => 'https://schema.org',
        '@type'    => 'ContactPage',
        'name'     => 'ติดต่อ PanteeThai',
        'url'      => APP_URL . '/contact',
        'contactPoint' => [
            '@type'       => 'ContactPoint',
            'email'       => 'phanwit@gmail.com',
            'contactType' => 'customer support',
            'availableLanguage' => ['Thai', 'English'],
        ],
    ], JSON_UNESCAPED_UNICODE)
    . '</script>',
];
$extra_head = '';

require_once '../includes/head.php';
?>
<body class="bg-gray-50">

    <!-- Navbar -->
    <nav class="bg-white shadow-sm h-16 flex items-center px-4 gap-3 relative z-[900]">
        <a href="/" class="text-xl font-bold text-green-600 flex-shrink-0">PanteeThai</a>
        <span class="text-gray-300 flex-shrink-0">/</span>
        <span class="text-gray-700 text-sm">ติดต่อเรา</span>
    </nav>

    <main class="max-w-2xl mx-auto px-4 py-10">

        <h1 class="text-2xl font-bold text-gray-800 mb-2">ติดต่อเรา</h1>
        <p class="text-gray-500 mb-8">มีคำถาม ข้อเสนอแนะ หรือต้องการแนะนำสถานที่? เราพร้อมรับฟังเสมอ</p>

        <!-- Direct email -->
        <div class="bg-green-50 rounded-2xl p-5 border border-green-100 mb-8 flex items-center gap-4">
            <span class="text-3xl flex-shrink-0">✉️</span>
            <div>
                <p class="text-sm text-gray-500 mb-0.5">อีเมลโดยตรง</p>
                <a href="mailto:phanwit@gmail.com"
                   class="text-lg font-semibold text-green-700 hover:text-green-800 hover:underline transition">
                    phanwit@gmail.com
                </a>
            </div>
        </div>

        <!-- Company contact details -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <h2 class="text-base font-semibold text-gray-800 mb-4">ข้อมูลติดต่อ</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex gap-3">
                    <dt class="flex-shrink-0 w-5 text-gray-400 text-base leading-snug">🏢</dt>
                    <dd class="text-gray-700 font-medium">แผนที่ไทย.คอม</dd>
                </div>
                <div class="flex gap-3">
                    <dt class="flex-shrink-0 w-5 text-gray-400 text-base leading-snug">📍</dt>
                    <dd class="text-gray-600 leading-snug">147/103 ซอยสุวินทวงศ์ 5/1<br>แสนแสบ มีนบุรี กรุงเทพมหานคร 10510</dd>
                </div>
                <div class="flex gap-3">
                    <dt class="flex-shrink-0 w-5 text-gray-400 text-base leading-snug">📞</dt>
                    <dd><a href="tel:029195900" class="text-gray-600 hover:text-green-600 transition">02 919-5900</a></dd>
                </div>
                <div class="flex gap-3">
                    <dt class="flex-shrink-0 w-5 text-gray-400 text-base leading-snug">📠</dt>
                    <dd class="text-gray-600">02 919-5899</dd>
                </div>
                <div class="flex gap-3">
                    <dt class="flex-shrink-0 w-5 text-gray-400 text-base leading-snug">✉️</dt>
                    <dd><a href="mailto:phanwit@gmail.com" class="text-green-600 hover:underline">phanwit@gmail.com</a></dd>
                </div>
            </dl>
        </div>

        <!-- Contact form (mailto) -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-base font-semibold text-gray-800 mb-5">ส่งข้อความถึงเรา</h2>

            <form id="contact-form" class="space-y-4">

                <div>
                    <label for="cf-name" class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">
                        ชื่อ <span class="text-red-400">*</span>
                    </label>
                    <input id="cf-name" type="text" required
                           placeholder="ชื่อของคุณ"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm
                                  focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100">
                </div>

                <div>
                    <label for="cf-email" class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">
                        อีเมล <span class="text-red-400">*</span>
                    </label>
                    <input id="cf-email" type="email" required
                           placeholder="your@email.com"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm
                                  focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100">
                </div>

                <div>
                    <label for="cf-subject" class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">
                        หัวข้อ
                    </label>
                    <select id="cf-subject"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-white
                                   focus:outline-none focus:border-green-500 cursor-pointer">
                        <option value="general">ทั่วไป / General</option>
                        <option value="suggest">แนะนำสถานที่ท่องเที่ยว</option>
                        <option value="error">รายงานข้อผิดพลาด</option>
                        <option value="ads">โฆษณาและสปอนเซอร์</option>
                        <option value="other">อื่น ๆ</option>
                    </select>
                </div>

                <div>
                    <label for="cf-message" class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">
                        ข้อความ <span class="text-red-400">*</span>
                    </label>
                    <textarea id="cf-message" required rows="5"
                              placeholder="เขียนข้อความที่นี่..."
                              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm resize-y
                                     focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100"></textarea>
                </div>

                <p id="cf-error" class="text-red-500 text-sm hidden"></p>

                <button type="submit"
                        class="w-full py-2.5 bg-green-500 hover:bg-green-600 active:bg-green-700
                               text-white font-medium rounded-lg text-sm transition">
                    ส่งข้อความ
                </button>

                <p class="text-xs text-gray-400 text-center">
                    ฟอร์มนี้จะเปิด email client ของคุณ — หรือส่งโดยตรงที่
                    <a href="mailto:phanwit@gmail.com" class="text-green-600 hover:underline">phanwit@gmail.com</a>
                </p>

            </form>
        </div>

        <!-- Topics -->
        <div class="mt-8 grid grid-cols-1 sm:grid-cols-3 gap-4 text-center">
            <?php
            $topics = [
                ['🏝️', 'แนะนำสถานที่', 'พบสถานที่น่าสนใจที่ยังไม่มีในระบบ?'],
                ['🐛', 'รายงานข้อผิดพลาด', 'ข้อมูลผิดพลาดหรือแผนที่ไม่ถูกต้อง'],
                ['🤝', 'ร่วมงานกัน', 'โฆษณา สปอนเซอร์ หรือพาร์ทเนอร์'],
            ];
            foreach ($topics as [$icon, $title, $desc]):
            ?>
            <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
                <div class="text-2xl mb-2"><?= $icon ?></div>
                <h3 class="text-sm font-semibold text-gray-700 mb-1"><?= $title ?></h3>
                <p class="text-xs text-gray-400"><?= $desc ?></p>
            </div>
            <?php endforeach; ?>
        </div>

    </main>

<?php
$footer_inline = <<<'JS'
(function () {
    document.getElementById('contact-form').addEventListener('submit', function (e) {
        e.preventDefault();
        var name    = document.getElementById('cf-name').value.trim();
        var email   = document.getElementById('cf-email').value.trim();
        var subject = document.getElementById('cf-subject');
        var subjectText = subject.options[subject.selectedIndex].text;
        var message = document.getElementById('cf-message').value.trim();
        var errEl   = document.getElementById('cf-error');

        if (!name || !email || !message) {
            errEl.textContent = 'กรุณากรอกข้อมูลให้ครบถ้วน';
            errEl.classList.remove('hidden');
            return;
        }
        errEl.classList.add('hidden');

        var body = 'ชื่อ: ' + name + '\nอีเมล: ' + email + '\nหัวข้อ: ' + subjectText + '\n\n' + message;
        var mailto = 'mailto:phanwit@gmail.com'
                   + '?subject=' + encodeURIComponent('[PanteeThai] ' + subjectText)
                   + '&body='    + encodeURIComponent(body);
        window.location.href = mailto;
    });
})();
JS;

require_once '../includes/footer.php';
