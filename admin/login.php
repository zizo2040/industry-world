<?php
/**
 * Admin Login Page
 */
require_once __DIR__ . '/../config.php';
initApp();

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . adminUrl('index.php'));
    exit;
}

$lang = getCurrentLang();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = $lang === 'ar' ? 'خطأ في التحقق من الأمان' : 'Security verification failed';
    } else {
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = $lang === 'ar' ? 'الرجاء إدخال اسم المستخدم وكلمة المرور' : 'Please enter username and password';
        } else {
            $result = login($username, $password);
            
            if ($result['success']) {
                header('Location: ' . adminUrl('index.php'));
                exit;
            } else {
                $error = $result['message'];
            }
        }
    }
}

$pageTitle = ($lang === 'ar' ? 'تسجيل الدخول - ' : 'Login - ') . getSetting('site_name_' . $lang);
?>
<!DOCTYPE html>
<html lang="<?php echo getHtmlLang(); ?>" dir="<?php echo getDirection(); ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?></title>
    <link rel="icon" type="image/x-icon" href="<?php echo asset('images/' . getSetting('site_favicon', 'favicon.ico')); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo asset('css/main.css'); ?>">
    <style>
        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--color-bg) 0%, #eef2f7 50%, var(--color-bg) 100%);
            padding: 2rem;
        }
        [data-theme="dark"] .login-page {
            background: linear-gradient(135deg, var(--color-bg) 0%, #1a1f2e 50%, var(--color-bg) 100%);
        }
        .login-box {
            width: 100%;
            max-width: 420px;
            background: var(--color-bg-card);
            border-radius: var(--radius-2xl);
            border: 1px solid var(--color-border);
            padding: var(--space-10);
            box-shadow: var(--shadow-xl);
        }
        .login-logo {
            text-align: center;
            margin-bottom: var(--space-8);
        }
        .login-logo-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
            border-radius: var(--radius-xl);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: var(--space-3);
            box-shadow: var(--shadow-primary);
        }
        .login-title {
            font-size: 1.35rem;
            font-weight: 700;
            margin-bottom: var(--space-1);
        }
        .login-subtitle {
            color: var(--color-text-muted);
            font-size: 0.9375rem;
        }
        .login-error {
            background: var(--color-danger-light);
            color: #991b1b;
            padding: var(--space-3) var(--space-4);
            border-radius: var(--radius-lg);
            font-size: 0.9375rem;
            margin-bottom: var(--space-5);
            border-right: 3px solid var(--color-danger);
        }
        .login-footer {
            text-align: center;
            margin-top: var(--space-6);
            font-size: 0.8125rem;
            color: var(--color-text-muted);
        }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-box">
            <div class="login-logo">
                <div class="login-logo-icon">A</div>
                <h1 class="login-title"><?php echo e(getSetting('site_name_' . $lang)); ?></h1>
                <p class="login-subtitle"><?php echo $lang === 'ar' ? 'لوحة التحكم الإدارية' : 'Admin Dashboard'; ?></p>
            </div>
            
            <?php if ($error): ?>
            <div class="login-error"><?php echo e($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" data-validate>
                <?php echo csrfField(); ?>
                
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'اسم المستخدم' : 'Username'; ?></label>
                    <input type="text" name="username" class="form-control form-control-lg" required autofocus>
                </div>
                
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'كلمة المرور' : 'Password'; ?></label>
                    <input type="password" name="password" class="form-control form-control-lg" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:var(--space-3);">
                    <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                    <?php echo $lang === 'ar' ? 'تسجيل الدخول' : 'Login'; ?>
                </button>
            </form>
            
            <div class="login-footer">
                <a href="<?php echo url(); ?>">&larr; <?php echo $lang === 'ar' ? 'العودة للموقع' : 'Back to Website'; ?></a>
            </div>
        </div>
    </div>
</body>
</html>
