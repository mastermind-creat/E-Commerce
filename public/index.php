<?php
// public/index.php
// Landing page — advanced, responsive, animated, tailored for your project

require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php'; // should contain <head> and open <body>; if not, Tailwind included below
?>
<!-- If header.php DOES NOT include Tailwind, uncomment below line (only one copy of tailwind needed) -->
<!-- <script src="https://cdn.tailwindcss.com"></script> -->

<main class="bg-gray-50 text-gray-800">

    <!-- ===== Top promo bar (optional) ===== -->
    <div class="hidden md:flex justify-center bg-red-600 text-white px-4 py-2 text-sm">
        <div class="max-w-7xl w-full flex justify-between items-center px-4">
            <div>🔥 Get KSh 100 off — limited time offer!</div>
            <div class="space-x-4">
                <a href="/seller" class="underline">Seller Center</a>
                <a href="/help" class="underline">Help</a>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- ===== Header Row: Mega categories (left) + Hero (right) ===== -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- Left: Mega categories (collapsible on mobile) -->
            <aside class="lg:col-span-3">
                <div class="bg-white rounded-2xl shadow sticky top-6 overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-3 border-b">
                        <div class="flex items-center gap-3">
                            <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            <h3 class="font-semibold">Shop by category</h3>
                        </div>
                        <button id="catToggle" class="lg:hidden px-2 py-1 text-sm text-gray-600">Categories</button>
                    </div>

                    <!-- Category list -->
                    <nav id="categoryPanel" class="p-2 space-y-1 max-h-[65vh] overflow-y-auto">
                        <?php
            // categories + first-level subcategory example
            try {
              $cats = $pdo->query("SELECT id, name, slug FROM categories ORDER BY name ASC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
              $cats = [];
            }

            foreach ($cats as $c):
              // placeholder: you can load subcategories from db if you have them
            ?>
                        <div class="group">
                            <a href="/shop.php?category=<?= urlencode($c['id']) ?>"
                                class="flex items-center justify-between px-3 py-2 rounded hover:bg-gray-50 transition">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 bg-gray-100 rounded flex items-center justify-center text-xs text-gray-600">
                                        <?= strtoupper(substr($c['name'],0,1)) ?>
                                    </div>
                                    <span class="text-sm text-gray-700"><?= htmlspecialchars($c['name']) ?></span>
                                </div>
                                <svg class="w-4 h-4 text-gray-400 group-hover:text-gray-600" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                        <?php endforeach; ?>

                        <?php if (empty($cats)): ?>
                        <div class="p-3 text-sm text-gray-500">No categories yet</div>
                        <?php endif; ?>
                    </nav>
                </div>
            </aside>

            <!-- Right: Hero & CTA -->
            <section class="lg:col-span-9">
                <div class="relative rounded-2xl overflow-hidden shadow-lg">
                    <!-- Slides track -->
                    <div id="heroTrack"
                        class="flex transition-transform duration-700 ease-in-out will-change-transform">
                        <?php
            $heroImages = [
              '/public/assets/hero1.jpg',
              '/public/assets/hero2.jpg',
              '/public/assets/hero3.jpg'
            ];
            foreach ($heroImages as $i => $src): ?>
                        <div class="w-full flex-shrink-0 relative">
                            <img src="<?= htmlspecialchars($src) ?>" alt="Hero <?= $i+1 ?>"
                                class="w-full h-64 sm:h-96 object-cover">
                            <div
                                class="absolute inset-0 bg-gradient-to-r from-black/40 via-transparent to-black/40 flex items-center">
                                <div class="max-w-3xl px-6 py-8 sm:px-10">
                                    <h2
                                        class="text-2xl sm:text-4xl lg:text-5xl font-extrabold text-white drop-shadow-lg">
                                        Discover Handpicked Styles</h2>
                                    <p class="mt-3 text-white/90 max-w-xl">Clothes, bags, jewelry and more — quality
                                        finds at friendly prices.</p>
                                    <div class="mt-6 flex gap-3">
                                        <a href="/shop.php"
                                            class="inline-block bg-yellow-400 hover:bg-yellow-500 text-gray-900 px-5 py-3 rounded-full font-semibold shadow">Shop
                                            Now</a>
                                        <a href="/collections.php"
                                            class="inline-block border border-white/30 text-white px-4 py-3 rounded-full hover:bg-white/10">Collections</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- controls -->
                    <button id="heroPrev" aria-label="Previous"
                        class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/90 p-2 rounded-full shadow">&larr;</button>
                    <button id="heroNext" aria-label="Next"
                        class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/90 p-2 rounded-full shadow">&rarr;</button>

                    <!-- indicators -->
                    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2">
                        <?php for($i=0;$i<count($heroImages);$i++): ?>
                        <button class="w-3 h-3 rounded-full bg-white/60" data-ind="<?= $i ?>"
                            aria-label="Go to slide <?= $i+1 ?>"></button>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Promo tiles (below hero) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                    <a href="/shop.php?promo=brand"
                        class="bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl p-6 flex items-center gap-4 hover:scale-[1.01] transform transition">
                        <div>
                            <h4 class="font-bold text-lg">Super Brand Sale</h4>
                            <p class="text-sm mt-1">Huge savings across categories</p>
                        </div>
                        <div class="ml-auto text-white bg-white/10 px-3 py-2 rounded">Shop</div>
                    </a>

                    <a href="/shop.php?category=phones"
                        class="bg-white rounded-xl p-4 shadow hover:shadow-xl transition flex items-center gap-4">
                        <img src="/public/assets/promo-phone.jpg" alt="Phones" class="w-20 h-20 object-cover rounded">
                        <div>
                            <h5 class="font-semibold">Phones & Accessories</h5>
                            <p class="text-sm text-gray-500">Top deals from leading brands</p>
                            <div class="mt-2 text-yellow-500 font-bold">From KSh 3,499</div>
                        </div>
                    </a>

                    <a href="/shop.php?category=home"
                        class="bg-white rounded-xl p-4 shadow hover:shadow-xl transition flex items-center gap-4">
                        <img src="/public/assets/promo-home.jpg" alt="Home" class="w-20 h-20 object-cover rounded">
                        <div>
                            <h5 class="font-semibold">Home & Living</h5>
                            <p class="text-sm text-gray-500">Made for comfort & style</p>
                            <div class="mt-2 text-yellow-500 font-bold">From KSh 1,299</div>
                        </div>
                    </a>
                </div>

            </section>
        </div>

        <!-- ===== Featured product grid ===== -->
        <section class="mt-10">
            <div class="flex items-center justify-between">
                <h3 class="text-2xl font-bold">Featured Products</h3>
                <a class="text-sm text-gray-600 hover:underline" href="/shop.php">View all</a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6 mt-6">
                <?php
        // Fetch featured/active products with primary image
        try {
          $stmt = $pdo->query("
            SELECT p.id,p.name,p.price,p.stock, pi.image_url
            FROM products p
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE p.status = 'active'
            ORDER BY p.created_at DESC
            LIMIT 20
          ");
          $prods = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
          $prods = [];
        }

        foreach ($prods as $prod):
          $thumb = $prod['image_url'] ? '/public/assets/products/' . $prod['image_url'] : '/public/assets/placeholder.png';
        ?>
                <article
                    class="bg-white rounded-2xl p-4 shadow hover:shadow-xl transform hover:-translate-y-2 transition">
                    <a href="/product.php?id=<?= $prod['id'] ?>" class="block relative">
                        <img data-src="<?= htmlspecialchars($thumb) ?>" alt="<?= htmlspecialchars($prod['name']) ?>"
                            class="w-full h-40 object-cover rounded-lg lazy">
                    </a>

                    <div class="mt-3">
                        <h4 class="text-sm font-medium truncate"><?= htmlspecialchars($prod['name']) ?></h4>
                        <div class="mt-2 flex items-center justify-between">
                            <div class="text-lg font-bold text-blue-600">KSh <?= number_format($prod['price'],2) ?>
                            </div>
                            <a href="product.php?id=<?= $prod['id'] ?>"
                                class="ml-2 px-3 py-1 bg-yellow-500 text-white rounded-lg text-xs hover:bg-yellow-600">View</a>

                        </div>
                    </div>
                </article>
                <?php endforeach; ?>

                <?php if (empty($prods)): ?>
                <div class="col-span-full text-center text-gray-500 py-8">No products to show.</div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ===== Testimonials slider ===== -->
        <section class="mt-12">
            <div class="bg-white rounded-2xl shadow p-6">
                <h3 class="text-xl font-bold mb-4">What customers say</h3>
                <div id="testimonials" class="relative overflow-hidden">
                    <div class="flex transition-transform duration-600" data-test-track>
                        <div class="min-w-full p-4">
                            <blockquote class="text-gray-700 italic">"Lovely service and fast delivery — great quality
                                items!"</blockquote>
                            <div class="mt-3 text-sm text-gray-500">— Jane M.</div>
                        </div>
                        <div class="min-w-full p-4">
                            <blockquote class="text-gray-700 italic">"The bags I bought are perfect. Excellent customer
                                support."</blockquote>
                            <div class="mt-3 text-sm text-gray-500">— Tom B.</div>
                        </div>
                        <div class="min-w-full p-4">
                            <blockquote class="text-gray-700 italic">"Easy checkout and reasonable shipping."
                            </blockquote>
                            <div class="mt-3 text-sm text-gray-500">— Aisha K.</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Newsletter CTA -->
        <section class="mt-10">
            <div
                class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-2xl text-white p-8 flex flex-col md:flex-row items-center justify-between gap-4">
                <div>
                    <h4 class="text-2xl font-bold">Join our newsletter — get 10% off</h4>
                    <p class="mt-2 text-white/90">Exclusive deals, new arrivals and members-only discounts.</p>
                </div>
                <form action="/subscribe.php" method="post" class="flex gap-2 w-full md:w-auto">
                    <input type="email" name="email" placeholder="Enter your email" required
                        class="px-4 py-2 rounded-lg text-gray-800 w-full md:w-80">
                    <button class="px-4 py-2 bg-yellow-400 text-gray-900 rounded-lg font-semibold">Subscribe</button>
                </form>
            </div>
        </section>

    </div> <!-- container -->

    <!-- Footer CTA small -->
    <div class="bg-white border-t">
        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>

</main>

<!-- ===== Styles & small animations ===== -->
<style>
@keyframes subtleFloat {
    0% {
        transform: translateY(0px);
    }

    50% {
        transform: translateY(-6px);
    }

    100% {
        transform: translateY(0px);
    }
}

.float-slow {
    animation: subtleFloat 6s ease-in-out infinite;
}

.scale-102 {
    transform: scale(1.02);
}

.lazy {
    opacity: 0;
    transition: opacity .3s ease-in-out;
}

.lazy.loaded {
    opacity: 1;
}
</style>

<!-- ===== Lightweight JS: carousel, lazy-loading, mobile behaviours ===== -->
<script>
/* HERO carousel */
(function() {
    const track = document.querySelector('#heroTrack');
    const slides = Array.from(track.children);
    const prev = document.getElementById('heroPrev');
    const next = document.getElementById('heroNext');
    const indicators = Array.from(document.querySelectorAll('[data-ind]'));
    let idx = 0;

    function update() {
        track.style.transform = `translateX(${-idx * 100}%)`;
        indicators.forEach((b, i) => b.classList.toggle('bg-white', i === idx));
    }
    prev && prev.addEventListener('click', () => {
        idx = (idx - 1 + slides.length) % slides.length;
        update();
    });
    next && next.addEventListener('click', () => {
        idx = (idx + 1) % slides.length;
        update();
    });
    indicators.forEach((b, i) => b.addEventListener('click', () => {
        idx = i;
        update();
    }));
    let auto = setInterval(() => {
        idx = (idx + 1) % slides.length;
        update();
    }, 5000);
    const hero = document.getElementById('heroTrack');
    hero && hero.addEventListener('mouseenter', () => clearInterval(auto));
    hero && hero.addEventListener('mouseleave', () => auto = setInterval(() => {
        idx = (idx + 1) % slides.length;
        update();
    }, 5000));
    update();
    // keyboard
    document.addEventListener('keydown', e => {
        if (e.key === 'ArrowLeft') prev.click();
        if (e.key === 'ArrowRight') next.click();
    });
})();

/* Testimonials rotate */
(function() {
    const tTrack = document.querySelector('[data-test-track]');
    if (!tTrack) return;
    const slides = tTrack.children;
    let i = 0;
    setInterval(() => {
        i = (i + 1) % slides.length;
        tTrack.style.transform = `translateX(${-i * 100}%)`;
    }, 5000);
})();

/* Lazy load images using IntersectionObserver */
(function() {
    const imgObserver = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const img = entry.target;
            const src = img.dataset.src;
            if (src) {
                img.src = src;
                img.onload = () => img.classList.add('loaded');
            }
            obs.unobserve(img);
        });
    }, {
        rootMargin: '200px'
    });
    document.querySelectorAll('img.lazy').forEach(img => imgObserver.observe(img));
})();

/* Mobile category toggle */
(function() {
    const catToggle = document.getElementById('catToggle');
    const panel = document.getElementById('categoryPanel');
    if (!catToggle || !panel) return;
    catToggle.addEventListener('click', () => {
        panel.classList.toggle('hidden');
    });
    // Hide on init for small screens
    if (window.innerWidth < 1024) panel.classList.add('hidden');
})();
</script>