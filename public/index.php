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
    <div class="hidden md:flex justify-center bg-blue-600 text-white px-4 py-2 text-sm">
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
                            <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                        try {
                            $cats = $pdo->query("SELECT id, name, slug FROM categories ORDER BY name ASC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
                        } catch (Exception $e) {
                            $cats = [];
                        }

                        foreach ($cats as $c):
                        ?>
                        <div class="group">
                            <a href="/shop.php?category=<?= urlencode($c['slug']) ?>"
                                class="flex items-center justify-between px-3 py-2 rounded hover:bg-gray-50 transition">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 bg-gray-100 rounded flex items-center justify-center text-xs text-gray-600">
                                        <?= strtoupper(substr($c['name'], 0, 1)) ?>
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
                        try {
                            $hero_stmt = $pdo->query("SELECT * FROM hero_slides WHERE active = 1 ORDER BY order_num ASC, id ASC");
                            $hero_slides = $hero_stmt->fetchAll(PDO::FETCH_ASSOC);
                        } catch (Exception $e) {
                            $hero_slides = [];
                        }

                        if (!empty($hero_slides)):
                            foreach ($hero_slides as $i => $slide):
                        ?>
                        <div class="w-full flex-shrink-0 relative">
                            <img src="/image.php?path=<?= urlencode(ltrim($slide['image_path'], '/')) ?>"
                                alt="<?= htmlspecialchars($slide['title']) ?>" class="w-full h-64 sm:h-96 object-cover">
                            <div
                                class="absolute inset-0 bg-gradient-to-r from-black/40 via-transparent to-black/40 flex items-center">
                                <div class="max-w-3xl px-6 py-8 sm:px-10">
                                    <h2
                                        class="text-2xl sm:text-4xl lg:text-5xl font-extrabold text-white drop-shadow-lg">
                                        <?= htmlspecialchars($slide['title']) ?>
                                    </h2>
                                    <p class="mt-3 text-white/90 max-w-xl">
                                        <?= htmlspecialchars($slide['description']) ?></p>
                                    <div class="mt-6 flex gap-3">
                                        <?php if (!empty($slide['button_text']) && !empty($slide['button_link'])): ?>
                                        <a href="<?= htmlspecialchars($slide['button_link']) ?>"
                                            class="inline-block bg-pink-400 hover:bg-pink-500 text-gray-900 px-5 py-3 rounded-full font-semibold shadow">
                                            <?= htmlspecialchars($slide['button_text']) ?>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach;
                        else: ?>
                        <!-- Fallback if no slides in DB -->
                        <div class="w-full flex-shrink-0 relative">
                            <img src="/public/assets/hero1.jpg" alt="Default Hero"
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
                                            class="inline-block bg-pink-400 hover:bg-pink-500 text-gray-900 px-5 py-3 rounded-full font-semibold shadow">Shop
                                            Now</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- controls -->
                    <button id="heroPrev" aria-label="Previous"
                        class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/90 p-2 rounded-full shadow">&larr;</button>
                    <button id="heroNext" aria-label="Next"
                        class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/90 p-2 rounded-full shadow">&rarr;</button>

                    <!-- indicators -->
                    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2">
                        <?php $slideCount = count($hero_slides); ?>
                        <?php for($i=0; $i < $slideCount; $i++): ?>
                        <button class="w-3 h-3 rounded-full bg-white/60" data-ind="<?= $i ?>"
                            aria-label="Go to slide <?= $i+1 ?>"></button>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Promo tiles (below hero) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                    <?php
                    try {
                        $promo_stmt = $pdo->query("SELECT * FROM promo_tiles WHERE active = 1 ORDER BY order_num ASC LIMIT 3");
                        $promo_tiles = $promo_stmt->fetchAll(PDO::FETCH_ASSOC);
                    } catch (Exception $e) {
                        $promo_tiles = [];
                    }

                    if (!empty($promo_tiles)):
                        foreach ($promo_tiles as $tile):
                    ?>
                    <a href="<?= htmlspecialchars($tile['link']) ?>"
                        class="bg-white rounded-xl p-4 shadow hover:shadow-xl transition flex items-center gap-4">
                        <?php if (!empty($tile['image_path'])): ?>
                        <img src="/image.php?path=<?= urlencode(ltrim($tile['image_path'], '/')) ?>"
                            alt="<?= htmlspecialchars($tile['title']) ?>" class="w-20 h-20 object-cover rounded">
                        <?php endif; ?>
                        <div>
                            <h5 class="font-semibold"><?= htmlspecialchars($tile['title']) ?></h5>
                            <p class="text-sm text-gray-500"><?= htmlspecialchars($tile['description']) ?></p>
                            <?php if (!empty($tile['price_text'])): ?>
                            <div class="mt-2 text-blue-500 font-bold"><?= htmlspecialchars($tile['price_text']) ?></div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach;
                    else: ?>
                    <a href="/shop.php?promo=brand"
                        class="bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl p-6 flex items-center gap-4 hover:scale-[1.01] transform transition">
                        <div>
                            <h4 class="font-bold text-lg">Super Brand Sale</h4>
                            <p class="text-sm mt-1">Huge savings across categories</p>
                        </div>
                        <div class="ml-auto text-white bg-white/10 px-3 py-2 rounded">Shop</div>
                    </a>

                    <a href="/shop.php?category=handbags"
                        class="bg-white rounded-xl p-4 shadow hover:shadow-xl transition flex items-center gap-4">
                        <img src="/public/assets/promo-handbag.jpg" alt="Handbags"
                            class="w-20 h-20 object-cover rounded">
                        <div>
                            <h5 class="font-semibold">Handbags & Small Bags</h5>
                            <p class="text-sm text-gray-500">Stylish and versatile options</p>
                            <div class="mt-2 text-blue-500 font-bold">From KSh 1,499</div>
                        </div>
                    </a>

                    <a href="/shop.php?category=clothing"
                        class="bg-white rounded-xl p-4 shadow hover:shadow-xl transition flex items-center gap-4">
                        <img src="/public/assets/promo-clothing.jpg" alt="Clothing"
                            class="w-20 h-20 object-cover rounded">
                        <div>
                            <h5 class="font-semibold">Men's T-Shirts & Jewelry</h5>
                            <p class="text-sm text-gray-500">Quality apparel and accessories</p>
                            <div class="mt-2 text-blue-500 font-bold">From KSh 999</div>
                        </div>
                    </a>
                    <?php endif; ?>
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
                try {
                    $stmt = $pdo->query("
                        SELECT id, name, price, stock
                        FROM products
                        WHERE status = 'active'
                        ORDER BY created_at DESC
                        LIMIT 20
                    ");
                    $prods = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $prods = [];
                }

                foreach ($prods as $prod):
                    $imgStmt = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ? ORDER BY id ASC LIMIT 1");
                    $imgStmt->execute([$prod['id']]);
                    $imgFile = $imgStmt->fetchColumn();
                    $thumb = $imgFile ? "assets/products/" . $imgFile : "assets/images/placeholder.png";
                ?>
                <article
                    class="bg-white rounded-2xl p-4 shadow hover:shadow-xl transform hover:-translate-y-2 transition">
                    <a href="/product.php?id=<?= $prod['id'] ?>" class="block relative">
                        <img src="<?= htmlspecialchars($thumb) ?>" alt="<?= htmlspecialchars($prod['name']) ?>"
                            class="w-full h-40 object-cover rounded-lg">
                    </a>

                    <div class="mt-3">
                        <h4 class="text-sm font-medium truncate"><?= htmlspecialchars($prod['name']) ?></h4>
                        <div class="mt-2 flex items-center justify-between">
                            <div class="text-lg font-bold text-blue-600">KSh <?= number_format($prod['price'], 2) ?>
                            </div>
                            <a href="/product.php?id=<?= $prod['id'] ?>"
                                class="ml-2 px-3 py-1 bg-pink-500 text-white rounded-lg text-xs hover:bg-pink-600">View</a>
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
                    <div class="flex transition-transform duration-600 ease-in-out" data-test-track>
                        <?php
                        try {
                            $stmt = $pdo->query("
                                SELECT r.comment, r.rating, u.name AS user_name
                                FROM reviews r
                                JOIN users u ON r.user_id = u.id
                                ORDER BY r.created_at DESC
                                LIMIT 5
                            ");
                            $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        } catch (Exception $e) {
                            $reviews = [];
                        }

                        if ($reviews):
                            foreach ($reviews as $rev):
                        ?>
                        <div class="min-w-full p-4">
                            <blockquote class="text-gray-700 italic">"<?= htmlspecialchars($rev['comment']) ?>"
                            </blockquote>
                            <div class="mt-3 text-sm text-gray-500">— <?= htmlspecialchars($rev['user_name']) ?></div>
                            <div class="flex mt-2">
                                <?php for($i = 0; $i < 5; $i++): ?>
                                <?php if ($i < $rev['rating']): ?>
                                <!-- Filled Star -->
                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.176c.969 0 1.371 1.24.588 1.81l-3.384 2.46a1 1 0 00-.364 1.118l1.287 3.966c.3.922-.755 1.688-1.54 1.118l-3.384-2.459a1 1 0 00-1.175 0l-3.384 2.46c-.785.57-1.84-.197-1.54-1.118l1.287-3.967a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.176a1 1 0 00.95-.69l1.285-3.967z" />
                                </svg>
                                <?php else: ?>
                                <!-- Empty Star -->
                                <svg class="w-5 h-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.176c.969 0 1.371 1.24.588 1.81l-3.384 2.46a1 1 0 00-.364 1.118l1.287 3.966c.3.922-.755 1.688-1.54 1.118l-3.384-2.459a1 1 0 00-1.175 0l-3.384 2.46c-.785.57-1.84-.197-1.54-1.118l1.287-3.967a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.176a1 1 0 00.95-.69l1.285-3.967z" />
                                </svg>
                                <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php endforeach;
                        else: ?>
                        <div class="min-w-full p-4 text-center text-gray-500">No customer reviews yet.</div>
                        <?php endif; ?>
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
                    <button class="px-4 py-2 bg-pink-400 text-gray-900 rounded-lg font-semibold">Subscribe</button>
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