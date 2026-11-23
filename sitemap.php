<?php
/**
 * XML Sitemap (Arabic default URLs + English ?lang=en alternates)
 * Submit https://industry-world.online/sitemap.php in Google Search Console.
 */
require_once __DIR__ . '/config.php';

header('Content-Type: application/xml; charset=UTF-8');
header('X-Robots-Tag: noindex');

$today = date('Y-m-d');
$urls = [];
$add = function (string $loc, string $lastmod, string $freq, string $prio, array $images = []) use (&$urls) {
    $urls[] = compact('loc', 'lastmod', 'freq', 'prio', 'images');
};

foreach ([
    ['', 'daily', '1.0'], ['products.php', 'daily', '0.9'], ['services.php', 'monthly', '0.8'],
    ['about.php', 'monthly', '0.7'], ['portfolio.php', 'weekly', '0.7'], ['contact.php', 'monthly', '0.8'],
    ['faq.php', 'monthly', '0.6'], ['privacy.php', 'yearly', '0.2'], ['terms.php', 'yearly', '0.2'],
] as [$path, $freq, $prio]) {
    $add(url($path), $today, $freq, $prio);
}

try {
    $categories = Database::fetchAll("SELECT slug, image, updated_at FROM categories WHERE is_active = 1 AND deleted_at IS NULL ORDER BY parent_id IS NOT NULL, sort_order");
    foreach ($categories as $c) {
        $add(url('products.php?category=' . rawurlencode($c['slug'])), date('Y-m-d', strtotime($c['updated_at'] ?: 'now')), 'weekly', '0.8',
            !empty($c['image']) ? [uploadUrl($c['image'])] : []);
    }
    $products = Database::fetchAll("SELECT slug, main_image, updated_at FROM products WHERE is_active = 1 AND deleted_at IS NULL");
    foreach ($products as $p) {
        $add(url('product.php?slug=' . rawurlencode($p['slug'])), date('Y-m-d', strtotime($p['updated_at'] ?: 'now')), 'weekly', '0.7',
            !empty($p['main_image']) ? [uploadUrl($p['main_image'])] : []);
    }
} catch (Throwable $e) {
    // keep the static part of the sitemap if the database is unavailable
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
<?php foreach ($urls as $u): foreach (['ar', 'en'] as $l): $loc = langUrl($u['loc'], $l); ?>
  <url>
    <loc><?php echo htmlspecialchars($loc, ENT_XML1); ?></loc>
    <lastmod><?php echo $u['lastmod']; ?></lastmod>
    <changefreq><?php echo $u['freq']; ?></changefreq>
    <priority><?php echo $l === 'ar' ? $u['prio'] : number_format(max(0.1, (float) $u['prio'] - 0.1), 1); ?></priority>
    <xhtml:link rel="alternate" hreflang="ar" href="<?php echo htmlspecialchars(langUrl($u['loc'], 'ar'), ENT_XML1); ?>"/>
    <xhtml:link rel="alternate" hreflang="en" href="<?php echo htmlspecialchars(langUrl($u['loc'], 'en'), ENT_XML1); ?>"/>
    <xhtml:link rel="alternate" hreflang="x-default" href="<?php echo htmlspecialchars(langUrl($u['loc'], 'ar'), ENT_XML1); ?>"/>
<?php foreach ($u['images'] as $img): ?>
    <image:image><image:loc><?php echo htmlspecialchars($img, ENT_XML1); ?></image:loc></image:image>
<?php endforeach; ?>
  </url>
<?php endforeach; endforeach; ?>
</urlset>
