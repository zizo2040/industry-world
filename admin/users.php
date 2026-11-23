<?php
/**
 * Admin Users Management
 */
require_once __DIR__ . '/../config.php';
initApp();
requireAuth();
requireRole('super_admin');

$lang = getCurrentLang();
$adminPage = 'users';
$pageTitle = ($lang === 'ar' ? 'المستخدمين - ' : 'Users - ') . getSetting('site_name_' . $lang);

$action = $_GET['action'] ?? 'list';
$id = intval($_GET['id'] ?? 0);

// Delete
if ($action === 'delete' && $id) {
    if (validateCsrfToken($_GET[CSRF_TOKEN_NAME] ?? '')) {
        Database::softDelete('admin_users', 'id = :id', ['id' => $id]);
        logActivity('delete', 'admin_users', $id, 'User deleted');
        setFlash('success', $lang === 'ar' ? 'تم الحذف' : 'Deleted');
    }
    header('Location: ' . adminUrl('users.php'));
    exit;
}

// Toggle status
if ($action === 'toggle' && $id) {
    if (validateCsrfToken($_GET[CSRF_TOKEN_NAME] ?? '')) {
        $user = Database::fetchOne("SELECT is_active FROM admin_users WHERE id = :id", ['id' => $id]);
        if ($user) {
            Database::update('admin_users', ['is_active' => $user['is_active'] ? 0 : 1], 'id = :id', ['id' => $id]);
            setFlash('success', $lang === 'ar' ? 'تم التحديث' : 'Updated');
        }
    }
    header('Location: ' . adminUrl('users.php'));
    exit;
}

// Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['add', 'edit'])) {
    if (validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $data = [
            'username' => sanitize($_POST['username'] ?? ''),
            'email' => sanitizeEmail($_POST['email'] ?? ''),
            'full_name' => sanitize($_POST['full_name'] ?? ''),
            'role' => sanitize($_POST['role'] ?? 'editor'),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        
        if (!empty($_POST['password'])) {
            $data['password_hash'] = hashPassword($_POST['password']);
        }
        
        try {
            if ($action === 'edit' && $id) {
                if (empty($data['password_hash'])) unset($data['password_hash']);
                Database::update('admin_users', $data, 'id = :id', ['id' => $id]);
                logActivity('update', 'admin_users', $id, 'User updated');
                setFlash('success', $lang === 'ar' ? 'تم التحديث' : 'Updated');
            } else {
                if (empty($data['password_hash'])) {
                    $error = $lang === 'ar' ? 'كلمة المرور مطلوبة' : 'Password is required';
                } else {
                    $newId = Database::insert('admin_users', $data);
                    logActivity('create', 'admin_users', $newId, 'User created');
                    setFlash('success', $lang === 'ar' ? 'تم الإضافة' : 'Added');
                }
            }
            if (empty($error)) {
                header('Location: ' . adminUrl('users.php'));
                exit;
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$userEdit = null;
if ($action === 'edit' && $id) {
    $userEdit = Database::fetchOne("SELECT * FROM admin_users WHERE id = :id", ['id' => $id]);
}

$users = Database::fetchAll("SELECT * FROM admin_users WHERE deleted_at IS NULL ORDER BY created_at DESC");

include __DIR__ . '/includes/header.php';
?>

<?php if (in_array($action, ['add', 'edit'])): ?>

<div style="display:flex;align-items:center;gap:var(--space-3);margin-bottom:var(--space-6);" class="reveal">
    <a href="<?php echo adminUrl('users.php'); ?>" class="btn btn-ghost btn-sm"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg></a>
    <h1 style="font-size:1.35rem;"><?php echo $action === 'edit' ? ($lang === 'ar' ? 'تعديل مستخدم' : 'Edit User') : ($lang === 'ar' ? 'إضافة مستخدم' : 'Add User'); ?></h1>
</div>

<div class="admin-card reveal">
    <div class="admin-card-body">
        <?php if (!empty($error)): ?><div class="alert alert-danger" style="margin-bottom:var(--space-4);"><?php echo e($error); ?></div><?php endif; ?>
        <form method="POST" action="" class="admin-form">
            <?php echo csrfField(); ?>
            <div class="admin-form-grid">
                <div class="form-group"><label class="form-label"><?php echo __('name'); ?> *</label><input type="text" name="full_name" class="form-control" value="<?php echo e($userEdit['full_name'] ?? ''); ?>" required></div>
                <div class="form-group"><label class="form-label"><?php echo __('email'); ?> *</label><input type="email" name="email" class="form-control" value="<?php echo e($userEdit['email'] ?? ''); ?>" required></div>
            </div>
            <div class="admin-form-grid">
                <div class="form-group"><label class="form-label"><?php echo $lang === 'ar' ? 'اسم المستخدم' : 'Username'; ?> *</label><input type="text" name="username" class="form-control" value="<?php echo e($userEdit['username'] ?? ''); ?>" required></div>
                <div class="form-group"><label class="form-label"><?php echo $lang === 'ar' ? 'كلمة المرور' : 'Password'; ?> <?php echo $action === 'edit' ? '(' . ($lang === 'ar' ? 'اترك فارغاً للإبقاء' : 'Leave empty to keep') . ')' : '*'; ?></label><input type="password" name="password" class="form-control" <?php echo $action === 'add' ? 'required' : ''; ?>></div>
            </div>
            <div class="form-group">
                <label class="form-label"><?php echo $lang === 'ar' ? 'الدور' : 'Role'; ?></label>
                <select name="role" class="form-control">
                    <option value="editor" <?php echo ($userEdit['role'] ?? '') === 'editor' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'محرر' : 'Editor'; ?></option>
                    <option value="admin" <?php echo ($userEdit['role'] ?? '') === 'admin' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'مدير' : 'Admin'; ?></option>
                    <option value="super_admin" <?php echo ($userEdit['role'] ?? '') === 'super_admin' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'مدير عام' : 'Super Admin'; ?></option>
                </select>
            </div>
            <label style="display:flex;align-items:center;gap:var(--space-2);cursor:pointer;margin-top:var(--space-2);"><input type="checkbox" name="is_active" <?php echo ($userEdit['is_active'] ?? 1) ? 'checked' : ''; ?>> <span><?php echo $lang === 'ar' ? 'نشط' : 'Active'; ?></span></label>
            <div style="margin-top:var(--space-5);display:flex;gap:var(--space-3);"><button type="submit" class="btn btn-primary"><?php echo __('save'); ?></button><a href="<?php echo adminUrl('users.php'); ?>" class="btn btn-ghost"><?php echo __('cancel'); ?></a></div>
        </form>
    </div>
</div>

<?php else: ?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-6);" class="reveal">
    <h1 style="font-size:1.35rem;"><?php echo $lang === 'ar' ? 'المستخدمين' : 'Users'; ?></h1>
    <a href="<?php echo adminUrl('users.php?action=add'); ?>" class="btn btn-primary"><svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg> <?php echo $lang === 'ar' ? 'إضافة مستخدم' : 'Add User'; ?></a>
</div>

<div class="admin-card reveal">
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr><th><?php echo __('name'); ?></th><th><?php echo __('email'); ?></th><th><?php echo $lang === 'ar' ? 'الدور' : 'Role'; ?></th><th><?php echo $lang === 'ar' ? 'آخر دخول' : 'Last Login'; ?></th><th><?php echo $lang === 'ar' ? 'الحالة' : 'Status'; ?></th><th style="text-align:center;"><?php echo __('actions'); ?></th></tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><strong><?php echo e($u['full_name']); ?></strong><div style="font-size:0.75rem;color:var(--color-text-muted);">@<?php echo e($u['username']); ?></div></td>
                    <td><?php echo e($u['email']); ?></td>
                    <td><span class="badge badge-<?php echo $u['role'] === 'super_admin' ? 'danger' : ($u['role'] === 'admin' ? 'warning' : 'primary'); ?>"><?php echo e($u['role']); ?></span></td>
                    <td style="font-size:0.8125rem;color:var(--color-text-muted);"><?php echo $u['last_login'] ? timeAgo($u['last_login']) : '-'; ?></td>
                    <td><a href="<?php echo adminUrl('users.php?action=toggle&id=' . $u['id'] . '&' . CSRF_TOKEN_NAME . '=' . generateCsrfToken()); ?>" class="status-badge <?php echo $u['is_active'] ? 'status-active' : 'status-inactive'; ?>"><?php echo $u['is_active'] ? ($lang === 'ar' ? 'نشط' : 'Active') : ($lang === 'ar' ? 'غير نشط' : 'Inactive'); ?></a></td>
                    <td>
                        <div style="display:flex;gap:var(--space-1);justify-content:center;">
                            <a href="<?php echo adminUrl('users.php?action=edit&id=' . $u['id']); ?>" class="btn btn-sm btn-ghost"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg></a>
                            <?php if ($u['id'] != ($user['id'] ?? 0)): ?>
                            <a href="<?php echo adminUrl('users.php?action=delete&id=' . $u['id'] . '&' . CSRF_TOKEN_NAME . '=' . generateCsrfToken()); ?>" class="btn btn-sm btn-ghost" style="color:var(--color-danger);" onclick="return confirm('<?php echo $lang === 'ar' ? 'هل أنت متأكد؟' : 'Are you sure?'; ?>');"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
