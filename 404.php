<?php
/**
 * 404 Page
 */
require_once __DIR__ . '/config.php';
initApp();

$lang = getCurrentLang();

http_response_code(404);

$pageTitle = __('404_title') . ' - ' . getSetting('site_name_' . $lang);
$pageDescription = __('404_message');
$robotsMeta = 'noindex,follow';

include BASE_PATH . '/includes/header.php';
?>

<div class="page-404">
    <div class="reveal">
        <div class="empty-state-icon" style="width:120px;height:120px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M13 2L4.09 12.11a1 1 0 0 0 .76 1.65h4.24l-1.99 7.24a1 1 0 0 0 1.7.95l10.91-11.24a1 1 0 0 0-.76-1.66h-4.19l1.99-6.24A1 1 0 0 0 13 2z"></path></svg>
        </div>
        <div class="page-404-code">404</div>
        <h1 class="page-404-title"><?php echo __('404_title'); ?></h1>
        <p class="page-404-text"><?php echo __('404_message'); ?></p>
        <a href="<?php echo url(); ?>" class="btn btn-primary btn-lg">
            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <?php echo __('back_home'); ?>
        </a>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
