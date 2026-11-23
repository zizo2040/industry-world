<?php
/**
 * XML Sitemap Generator
 */
require_once __DIR__ . '/config.php';

header('Content-Type: application/xml; charset=UTF-8');

$lang = getCurrentLang();
$lastMod = date('Y-m-d');

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
    <url>
        <loc><?php echo url(); ?></loc>
        <lastmod><?php echo $lastMod; ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
        <xhtml:link rel="alternate" hreflang="ar" href="<?php echo url('?lang=ar'); ?>" />
        <xhtml:link rel="alternate" hreflang="en" href="<?php echo url('?lang=en'); ?>" />
    </url>
    <url>
        <loc><?php echo url('about.php'); ?></loc>
        <lastmod><?php echo $lastMod; ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
        <xhtml:link rel="alternate" hreflang="ar" href="<?php echo url('about.php?lang=ar'); ?>" />
        <xhtml:link rel="alternate" hreflang="en" href="<?php echo url('about.php?lang=en'); ?>" />
    </url>
    <url>
        <loc><?php echo url('services.php'); ?></loc>
        <lastmod><?php echo $lastMod; ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
        <xhtml:link rel="alternate" hreflang="ar" href="<?php echo url('services.php?lang=ar'); ?>" />
        <xhtml:link rel="alternate" hreflang="en" href="<?php echo url('services.php?lang=en'); ?>" />
    </url>
    <url>
        <loc><?php echo url('products.php'); ?></loc>
        <lastmod><?php echo $lastMod; ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
        <xhtml:link rel="alternate" hreflang="ar" href="<?php echo url('products.php?lang=ar'); ?>" />
        <xhtml:link rel="alternate" hreflang="en" href="<?php echo url('products.php?lang=en'); ?>" />
    </url>
    <url>
        <loc><?php echo url('portfolio.php'); ?></loc>
        <lastmod><?php echo $lastMod; ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
        <xhtml:link rel="alternate" hreflang="ar" href="<?php echo url('portfolio.php?lang=ar'); ?>" />
        <xhtml:link rel="alternate" hreflang="en" href="<?php echo url('portfolio.php?lang=en'); ?>" />
    </url>
    <url>
        <loc><?php echo url('contact.php'); ?></loc>
        <lastmod><?php echo $lastMod; ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
        <xhtml:link rel="alternate" hreflang="ar" href="<?php echo url('contact.php?lang=ar'); ?>" />
        <xhtml:link rel="alternate" hreflang="en" href="<?php echo url('contact.php?lang=en'); ?>" />
    </url>
    <url>
        <loc><?php echo url('faq.php'); ?></loc>
        <lastmod><?php echo $lastMod; ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
        <xhtml:link rel="alternate" hreflang="ar" href="<?php echo url('faq.php?lang=ar'); ?>" />
        <xhtml:link rel="alternate" hreflang="en" href="<?php echo url('faq.php?lang=en'); ?>" />
    </url>
    <?php
    // Product pages
    $products = Database::fetchAll("SELECT slug, updated_at FROM products WHERE is_active = 1 AND deleted_at IS NULL");
    foreach ($products as $p):
    ?>
    <url>
        <loc><?php echo url('product.php?slug=' . $p['slug']); ?></loc>
        <lastmod><?php echo date('Y-m-d', strtotime($p['updated_at'])); ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    <?php endforeach; ?>
    <?php
    // Category pages
    $categories = Database::fetchAll("SELECT slug, updated_at FROM categories WHERE is_active = 1 AND deleted_at IS NULL");
    foreach ($categories as $c):
    ?>
    <url>
        <loc><?php echo url('products.php?category=' . $c['slug']); ?></loc>
        <lastmod><?php echo date('Y-m-d', strtotime($c['updated_at'])); ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    <?php endforeach; ?>
</urlset>
