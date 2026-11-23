<?php
/**
 * Admin Dashboard
 */
require_once __DIR__ . '/../config.php';
initApp();
requireAuth();

$lang = getCurrentLang();
$adminPage = 'dashboard';
$user = getCurrentUser();

// Statistics
$stats = [
    'products' => Database::fetchOne("SELECT COUNT(*) as c FROM products WHERE deleted_at IS NULL")['c'] ?? 0,
    'active_products' => Database::fetchOne("SELECT COUNT(*) as c FROM products WHERE is_active = 1 AND deleted_at IS NULL")['c'] ?? 0,
    'categories' => Database::fetchOne("SELECT COUNT(*) as c FROM categories WHERE deleted_at IS NULL")['c'] ?? 0,
    'portfolio' => Database::fetchOne("SELECT COUNT(*) as c FROM portfolio WHERE is_active = 1")['c'] ?? 0,
    'contacts' => Database::fetchOne("SELECT COUNT(*) as c FROM contacts")['c'] ?? 0,
    'unread_contacts' => Database::fetchOne("SELECT COUNT(*) as c FROM contacts WHERE is_read = 0")['c'] ?? 0,
    'users' => Database::fetchOne("SELECT COUNT(*) as c FROM admin_users WHERE deleted_at IS NULL")['c'] ?? 0,
];

// Recent contacts
$recentContacts = Database::fetchAll(
    "SELECT * FROM contacts ORDER BY created_at DESC LIMIT 5"
);

// Recent activity
$recentActivity = Database::fetchAll(
    "SELECT a.*, u.full_name as user_name 
     FROM activity_log a 
     LEFT JOIN admin_users u ON a.user_id = u.id 
     ORDER BY a.created_at DESC LIMIT 10"
);

$pageTitle = ($lang === 'ar' ? 'لوحة التحكم - ' : 'Dashboard - ') . getSetting('site_name_' . $lang);
include __DIR__ . '/includes/header.php';
?>

<!-- Page Title -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-6);" class="reveal">
    <div>
        <h1 style="font-size:1.5rem;margin-bottom:var(--space-1);"><?php echo $lang === 'ar' ? 'لوحة التحكم' : 'Dashboard'; ?></h1>
        <p style="color:var(--color-text-muted);"><?php echo $lang === 'ar' ? 'نظرة عامة على أداء موقعك' : 'Overview of your website performance'; ?></p>
    </div>
    <div style="display:flex;gap:var(--space-3);">
        <a href="<?php echo url(); ?>" target="_blank" class="btn btn-outline btn-sm">
            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
            <?php echo $lang === 'ar' ? 'عرض الموقع' : 'View Site'; ?>
        </a>
    </div>
</div>

<!-- Stats Grid -->
<div class="admin-stats-grid reveal">
    <div class="admin-stat-card">
        <div class="admin-stat-icon" style="background:rgba(var(--color-primary-rgb),0.1);color:var(--color-primary);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
        </div>
        <div class="admin-stat-content">
            <div class="admin-stat-number"><?php echo number_format($stats['products']); ?></div>
            <div class="admin-stat-label"><?php echo $lang === 'ar' ? 'المنتجات' : 'Products'; ?></div>
        </div>
    </div>
    
    <div class="admin-stat-card">
        <div class="admin-stat-icon" style="background:rgba(16,185,129,0.1);color:var(--color-success);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        </div>
        <div class="admin-stat-content">
            <div class="admin-stat-number"><?php echo number_format($stats['active_products']); ?></div>
            <div class="admin-stat-label"><?php echo $lang === 'ar' ? 'منتج نشط' : 'Active Products'; ?></div>
        </div>
    </div>
    
    <div class="admin-stat-card">
        <div class="admin-stat-icon" style="background:rgba(245,158,11,0.1);color:var(--color-warning);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
        </div>
        <div class="admin-stat-content">
            <div class="admin-stat-number"><?php echo number_format($stats['categories']); ?></div>
            <div class="admin-stat-label"><?php echo $lang === 'ar' ? 'الفئات' : 'Categories'; ?></div>
        </div>
    </div>
    
    <div class="admin-stat-card">
        <div class="admin-stat-icon" style="background:rgba(59,130,246,0.1);color:var(--color-info);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
        </div>
        <div class="admin-stat-content">
            <div class="admin-stat-number"><?php echo number_format($stats['portfolio']); ?></div>
            <div class="admin-stat-label"><?php echo $lang === 'ar' ? 'المشاريع' : 'Projects'; ?></div>
        </div>
    </div>
    
    <div class="admin-stat-card">
        <div class="admin-stat-icon" style="background:rgba(239,68,68,0.1);color:var(--color-danger);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
        </div>
        <div class="admin-stat-content">
            <div class="admin-stat-number"><?php echo number_format($stats['contacts']); ?></div>
            <div class="admin-stat-label"><?php echo $lang === 'ar' ? 'رسائل' : 'Messages'; ?><?php if ($stats['unread_contacts'] > 0): ?> <span style="color:var(--color-danger);font-size:0.75rem;">(<?php echo $stats['unread_contacts']; ?>)</span><?php endif; ?></div>
        </div>
    </div>
    
    <div class="admin-stat-card">
        <div class="admin-stat-icon" style="background:rgba(139,92,246,0.1);color:#8b5cf6;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        </div>
        <div class="admin-stat-content">
            <div class="admin-stat-number"><?php echo number_format($stats['users']); ?></div>
            <div class="admin-stat-label"><?php echo $lang === 'ar' ? 'المستخدمين' : 'Users'; ?></div>
        </div>
    </div>
</div>

<!-- Recent Activity & Contacts -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6);margin-top:var(--space-8);">
    <!-- Recent Contacts -->
    <div class="admin-card reveal">
        <div class="admin-card-header">
            <h3 style="font-size:1.05rem;"><?php echo $lang === 'ar' ? 'أحدث الرسائل' : 'Recent Messages'; ?></h3>
            <a href="contacts.php" class="btn btn-sm btn-outline"><?php echo $lang === 'ar' ? 'عرض الكل' : 'View All'; ?></a>
        </div>
        <div style="overflow-x:auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th><?php echo $lang === 'ar' ? 'الاسم' : 'Name'; ?></th>
                        <th><?php echo $lang === 'ar' ? 'الموضوع' : 'Subject'; ?></th>
                        <th><?php echo $lang === 'ar' ? 'التاريخ' : 'Date'; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentContacts as $contact): ?>
                    <tr>
                        <td><strong><?php echo e($contact['name']); ?></strong></td>
                        <td><?php echo e($contact['subject'] ?? '-'); ?></td>
                        <td style="white-space:nowrap;font-size:0.8125rem;color:var(--color-text-muted);"><?php echo timeAgo($contact['created_at']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentContacts)): ?>
                    <tr><td colspan="3" style="text-align:center;color:var(--color-text-muted);padding:var(--space-6);"><?php echo $lang === 'ar' ? 'لا توجد رسائل' : 'No messages'; ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Quick Links -->
    <div class="admin-card reveal">
        <div class="admin-card-header">
            <h3 style="font-size:1.05rem;"><?php echo $lang === 'ar' ? 'روابط سريعة' : 'Quick Links'; ?></h3>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-3);">
            <a href="products.php" class="btn btn-ghost" style="justify-content:flex-start;padding:var(--space-4);border:1px solid var(--color-border);">
                <svg style="width:20px;height:20px;margin-left:var(--space-2);" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                <?php echo $lang === 'ar' ? 'إدارة المنتجات' : 'Manage Products'; ?>
            </a>
            <a href="categories.php" class="btn btn-ghost" style="justify-content:flex-start;padding:var(--space-4);border:1px solid var(--color-border);">
                <svg style="width:20px;height:20px;margin-left:var(--space-2);" viewBox="0 0 24 24" fill="none" stroke="var(--color-warning)" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                <?php echo $lang === 'ar' ? 'إدارة الفئات' : 'Manage Categories'; ?>
            </a>
            <a href="portfolio.php" class="btn btn-ghost" style="justify-content:flex-start;padding:var(--space-4);border:1px solid var(--color-border);">
                <svg style="width:20px;height:20px;margin-left:var(--space-2);" viewBox="0 0 24 24" fill="none" stroke="var(--color-info)" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
                <?php echo $lang === 'ar' ? 'معرض الأعمال' : 'Portfolio'; ?>
            </a>
            <a href="contacts.php" class="btn btn-ghost" style="justify-content:flex-start;padding:var(--space-4);border:1px solid var(--color-border);">
                <svg style="width:20px;height:20px;margin-left:var(--space-2);" viewBox="0 0 24 24" fill="none" stroke="var(--color-danger)" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                <?php echo $lang === 'ar' ? 'الرسائل' : 'Messages'; ?>
            </a>
            <a href="settings.php" class="btn btn-ghost" style="justify-content:flex-start;padding:var(--space-4);border:1px solid var(--color-border);">
                <svg style="width:20px;height:20px;margin-left:var(--space-2);" viewBox="0 0 24 24" fill="none" stroke="var(--color-text-muted)" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                <?php echo $lang === 'ar' ? 'الإعدادات' : 'Settings'; ?>
            </a>
            <a href="seo.php" class="btn btn-ghost" style="justify-content:flex-start;padding:var(--space-4);border:1px solid var(--color-border);">
                <svg style="width:20px;height:20px;margin-left:var(--space-2);" viewBox="0 0 24 24" fill="none" stroke="var(--color-success)" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                SEO
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
