<?php
/**
 * Authentication System
 * Secure admin authentication with brute force protection
 */

// ============================================================
// SESSION MANAGEMENT
// ============================================================

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['admin_user_id']) && $_SESSION['admin_user_id'] > 0;
}

/**
 * Get current user
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    
    $userId = $_SESSION['admin_user_id'];
    return Database::fetchOne(
        "SELECT id, username, email, full_name, role, avatar, is_active, last_login 
         FROM admin_users 
         WHERE id = :id AND is_active = 1 AND deleted_at IS NULL",
        ['id' => $userId]
    );
}

/**
 * Get user role
 */
function getUserRole(): string {
    $user = getCurrentUser();
    return $user ? $user['role'] : 'guest';
}

/**
 * Check if user has role
 */
function hasRole(string $role): bool {
    $userRole = getUserRole();
    $roles = ['guest' => 0, 'moderator' => 1, 'editor' => 2, 'admin' => 3, 'super_admin' => 4];
    return ($roles[$userRole] ?? 0) >= ($roles[$role] ?? 0);
}

/**
 * Require authentication
 */
function requireAuth(): void {
    if (!isLoggedIn()) {
        header('Location: ' . adminUrl('login.php'));
        exit;
    }
    
    // Validate session
    if (!validateSession()) {
        logout();
        header('Location: ' . adminUrl('login.php'));
        exit;
    }
}

/**
 * Require specific role
 */
function requireRole(string $role): void {
    requireAuth();
    
    if (!hasRole($role)) {
        http_response_code(403);
        exit('Access denied');
    }
}

/**
 * Validate session
 */
function validateSession(): bool {
    if (!isset($_SESSION['session_token'])) return false;
    
    $session = Database::fetchOne(
        "SELECT * FROM admin_sessions 
         WHERE session_token = :token 
         AND expires_at > NOW()",
        ['token' => $_SESSION['session_token']]
    );
    
    return $session !== null;
}

// ============================================================
// LOGIN / LOGOUT
// ============================================================

/**
 * Login user
 */
function login(string $username, string $password, bool $remember = false): array {
    $result = ['success' => false, 'message' => '', 'user' => null];
    
    // Check brute force
    $ip = getClientIP();
    if (!checkLoginAttempts($ip, $username)) {
        $result['message'] = isArabic() 
            ? 'تم تجاوز عدد المحاولات. يرجى الانتظار 15 دقيقة.' 
            : 'Too many attempts. Please wait 15 minutes.';
        return $result;
    }
    
    // Find user
    $user = Database::fetchOne(
        "SELECT * FROM admin_users 
         WHERE (username = :login OR email = :login) 
         AND is_active = 1 
         AND deleted_at IS NULL",
        ['login' => $username]
    );
    
    if (!$user) {
        recordLoginAttempt($ip, $username);
        $result['message'] = isArabic() 
            ? 'اسم المستخدم أو كلمة المرور غير صحيحة' 
            : 'Invalid username or password';
        return $result;
    }
    
    // Verify password
    if (!verifyPassword($password, $user['password_hash'])) {
        recordLoginAttempt($ip, $username);
        $result['message'] = isArabic() 
            ? 'اسم المستخدم أو كلمة المرور غير صحيحة' 
            : 'Invalid username or password';
        return $result;
    }
    
    // Clear login attempts
    clearLoginAttempts($ip, $username);
    
    // Create session
    $token = generateToken(64);
    $expires = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
    
    Database::insert('admin_sessions', [
        'user_id' => $user['id'],
        'session_token' => $token,
        'ip_address' => $ip,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'expires_at' => $expires
    ]);
    
    // Update user
    Database::update('admin_users', [
        'last_login' => date('Y-m-d H:i:s'),
        'login_count' => $user['login_count'] + 1
    ], "id = :id", ['id' => $user['id']]);
    
    // Set session
    $_SESSION['admin_user_id'] = $user['id'];
    $_SESSION['session_token'] = $token;
    $_SESSION['login_time'] = time();
    
    // Log activity
    logActivity('login', 'admin_users', $user['id'], 'User logged in');
    
    $result['success'] = true;
    $result['user'] = $user;
    return $result;
}

/**
 * Logout
 */
function logout(): void {
    if (isset($_SESSION['session_token'])) {
        Database::query(
            "DELETE FROM admin_sessions WHERE session_token = :token",
            ['token' => $_SESSION['session_token']]
        );
    }
    
    // Log activity
    if (isset($_SESSION['admin_user_id'])) {
        logActivity('logout', 'admin_users', $_SESSION['admin_user_id'], 'User logged out');
    }
    
    // Clear session
    session_unset();
    session_destroy();
}

// ============================================================
// BRUTE FORCE PROTECTION
// ============================================================

/**
 * Check login attempts
 */
function checkLoginAttempts(string $ip, string $username): bool {
    $attempts = Database::fetchOne(
        "SELECT * FROM login_attempts 
         WHERE ip_address = :ip AND username = :username",
        ['ip' => $ip, 'username' => $username]
    );
    
    if (!$attempts) return true;
    if ($attempts['is_locked'] && strtotime($attempts['locked_until']) > time()) {
        return false;
    }
    
    return $attempts['attempt_count'] < MAX_LOGIN_ATTEMPTS;
}

/**
 * Record login attempt
 */
function recordLoginAttempt(string $ip, string $username): void {
    $existing = Database::fetchOne(
        "SELECT * FROM login_attempts WHERE ip_address = :ip AND username = :username",
        ['ip' => $ip, 'username' => $username]
    );
    
    if ($existing) {
        $newCount = $existing['attempt_count'] + 1;
        $isLocked = $newCount >= MAX_LOGIN_ATTEMPTS ? 1 : 0;
        $lockedUntil = $isLocked ? date('Y-m-d H:i:s', time() + LOGIN_LOCKOUT_TIME) : null;
        
        Database::update('login_attempts', [
            'attempt_count' => $newCount,
            'last_attempt' => date('Y-m-d H:i:s'),
            'is_locked' => $isLocked,
            'locked_until' => $lockedUntil
        ], "id = :id", ['id' => $existing['id']]);
    } else {
        Database::insert('login_attempts', [
            'ip_address' => $ip,
            'username' => $username,
            'attempt_count' => 1,
            'last_attempt' => date('Y-m-d H:i:s')
        ]);
    }
}

/**
 * Clear login attempts
 */
function clearLoginAttempts(string $ip, string $username): void {
    Database::query(
        "DELETE FROM login_attempts WHERE ip_address = :ip AND username = :username",
        ['ip' => $ip, 'username' => $username]
    );
}

// ============================================================
// ACTIVITY LOG
// ============================================================

/**
 * Log activity
 */
function logActivity(string $action, string $entityType, ?int $entityId = null, string $details = ''): void {
    $userId = isLoggedIn() ? $_SESSION['admin_user_id'] : null;
    
    Database::insert('activity_log', [
        'user_id' => $userId,
        'action' => $action,
        'entity_type' => $entityType,
        'entity_id' => $entityId,
        'details' => $details,
        'ip_address' => getClientIP(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
}
