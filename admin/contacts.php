<?php
/**
 * Admin Contacts Management
 */
require_once __DIR__ . '/../config.php';
initApp();
requireAuth();

$lang = getCurrentLang();
$adminPage = 'contacts';
$pageTitle = ($lang === 'ar' ? 'الرسائل - ' : 'Messages - ') . getSetting('site_name_' . $lang);

$action = $_GET['action'] ?? 'list';
$id = intval($_GET['id'] ?? 0);

// Mark as read
if ($action === 'read' && $id) {
    Database::update('contacts', ['is_read' => 1, 'status' => 'completed'], 'id = :id', ['id' => $id]);
    logActivity('read', 'contacts', $id, 'Message marked as read');
    setFlash('success', $lang === 'ar' ? 'تم التحديث' : 'Updated');
    header('Location: ' . adminUrl('contacts.php'));
    exit;
}

// Delete
if ($action === 'delete' && $id) {
    if (validateCsrfToken($_GET[CSRF_TOKEN_NAME] ?? '')) {
        Database::query("DELETE FROM contacts WHERE id = :id", ['id' => $id]);
        logActivity('delete', 'contacts', $id, 'Message deleted');
        setFlash('success', $lang === 'ar' ? 'تم الحذف' : 'Deleted');
    }
    header('Location: ' . adminUrl('contacts.php'));
    exit;
}

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$total = Database::fetchOne("SELECT COUNT(*) as c FROM contacts")['c'] ?? 0;
$pagination = paginate($total, $page, $perPage);

$contacts = Database::fetchAll(
    "SELECT * FROM contacts ORDER BY created_at DESC LIMIT :limit OFFSET :offset",
    ['limit' => $perPage, 'offset' => $pagination['offset']]
);

include __DIR__ . '/includes/header.php';
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-6);" class="reveal">
    <div>
        <h1 style="font-size:1.35rem;"><?php echo $lang === 'ar' ? 'رسائل التواصل' : 'Contact Messages'; ?></h1>
        <p style="color:var(--color-text-muted);font-size:0.9375rem;"><?php echo $lang === 'ar' ? 'رسائل واستفسارات الزوار' : 'Visitor messages and inquiries'; ?></p>
    </div>
</div>

<div class="admin-card reveal">
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th><?php echo $lang === 'ar' ? 'الاسم' : 'Name'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'البريد/الهاتف' : 'Email/Phone'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'الموضوع' : 'Subject'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'النوع' : 'Type'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'الحالة' : 'Status'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'التاريخ' : 'Date'; ?></th>
                    <th style="text-align:center;"><?php echo __('actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contacts as $c): ?>
                <tr style="<?php echo $c['is_read'] ? '' : 'background:rgba(var(--color-primary-rgb),0.03);font-weight:600;'; ?>">
                    <td>
                        <strong><?php echo e($c['name']); ?></strong>
                        <?php if (!$c['is_read']): ?><span class="badge badge-primary" style="margin-right:var(--space-1);">New</span><?php endif; ?>
                    </td>
                    <td>
                        <div style="font-size:0.8125rem;"><?php echo e($c['email'] ?? '-'); ?></div>
                        <div style="font-size:0.8125rem;color:var(--color-text-muted);"><?php echo e($c['phone'] ?? ''); ?></div>
                    </td>
                    <td><?php echo e($c['subject'] ?? '-'); ?></td>
                    <td><span class="badge badge-secondary"><?php echo e($c['type']); ?></span></td>
                    <td>
                        <span class="status-badge <?php echo $c['status'] === 'completed' ? 'status-active' : ($c['status'] === 'in_progress' ? '' : 'status-inactive'); ?>" style="<?php echo $c['status'] === 'new' ? 'background:var(--color-warning-light);color:#92400e;' : ''; ?>">
                            <?php echo e($c['status']); ?>
                        </span>
                    </td>
                    <td style="white-space:nowrap;font-size:0.8125rem;"><?php echo timeAgo($c['created_at']); ?></td>
                    <td>
                        <div style="display:flex;gap:var(--space-1);justify-content:center;">
                            <?php if (!$c['is_read']): ?>
                            <a href="<?php echo adminUrl('contacts.php?action=read&id=' . $c['id']); ?>" class="btn btn-sm btn-ghost" title="<?php echo $lang === 'ar' ? 'تحديد كمقروء' : 'Mark as read'; ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                            </a>
                            <?php endif; ?>
                            <a href="https://wa.me/<?php echo e($c['phone'] ?? ''); ?>" target="_blank" class="btn btn-sm btn-ghost" style="color:#25d366;" title="WhatsApp">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            </a>
                            <a href="<?php echo adminUrl('contacts.php?action=delete&id=' . $c['id'] . '&' . CSRF_TOKEN_NAME . '=' . generateCsrfToken()); ?>" class="btn btn-sm btn-ghost" style="color:var(--color-danger);" onclick="return confirm('<?php echo $lang === 'ar' ? 'هل أنت متأكد؟' : 'Are you sure?'; ?>');" title="<?php echo __('delete'); ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php if ($c['message']): ?>
                <tr><td colspan="7" style="padding:0;"><div style="padding:var(--space-3) var(--space-5);background:var(--color-bg);font-size:0.875rem;color:var(--color-text-secondary);border-bottom:1px solid var(--color-border-light);"><strong><?php echo $lang === 'ar' ? 'الرسالة:' : 'Message:' ?></strong> <?php echo nl2br(e($c['message'])); ?></div></td></tr>
                <?php endif; ?>
                <?php endforeach; ?>
                <?php if (empty($contacts)): ?>
                <tr><td colspan="7" style="text-align:center;padding:var(--space-8);color:var(--color-text-muted);"><?php echo $lang === 'ar' ? 'لا توجد رسائل' : 'No messages'; ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($pagination['total_pages'] > 1): ?>
    <div style="padding:var(--space-4);border-top:1px solid var(--color-border-light);">
        <?php echo renderPagination($pagination, adminUrl('contacts.php')); ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
