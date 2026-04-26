<?php
/**
 * Prescribe & Co. — Core Functions
 */

// ════════════════════════════════════════════════════════════
// SESSION
// ════════════════════════════════════════════════════════════
function init_session(): void {
    if (session_status() !== PHP_SESSION_NONE) return;
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Strict');
    if (APP_ENV === 'production') ini_set('session.cookie_secure', 1);
    session_name(SESSION_NAME);
    session_start();
}

// ════════════════════════════════════════════════════════════
// AUTHENTICATION
// ════════════════════════════════════════════════════════════
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function current_user(): ?array {
    if (!is_logged_in()) return null;
    static $u = null;
    if ($u === null) {
        $u = Database::fetchOne(
            "SELECT id, email, role, first_name, last_name, phone, is_active FROM users WHERE id = ? AND is_active = 1",
            [$_SESSION['user_id']]
        );
    }
    return $u;
}

function current_user_id(): int  { return (int)($_SESSION['user_id'] ?? 0); }
function current_role(): string  { return (string)($_SESSION['user_role'] ?? ''); }

function has_role(string ...$roles): bool {
    return in_array(current_role(), $roles, true);
}

function require_auth(string ...$roles): void {
    if (!is_logged_in()) {
        redirect('/pages/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    }
    if (!empty($roles) && !has_role(...$roles)) {
        http_response_code(403);
        include APP_PATH . '/pages/errors/403.php';
        exit;
    }
    if (!current_user()) {
        session_kill();
        redirect('/pages/auth/login.php');
    }
}

function login_user(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id']     = $user['id'];
    $_SESSION['user_role']   = $user['role'];
    $_SESSION['user_email']  = $user['email'];
    $_SESSION['user_name']   = trim($user['first_name'] . ' ' . $user['last_name']);
    $_SESSION['logged_in_at']= time();
}

function session_kill(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// ════════════════════════════════════════════════════════════
// CSRF
// ════════════════════════════════════════════════════════════
function csrf_token(): string {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function csrf_field(): string {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . csrf_token() . '">';
}

function csrf_verify(): bool {
    $token = $_POST[CSRF_TOKEN_NAME] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return hash_equals($_SESSION[CSRF_TOKEN_NAME] ?? '', $token);
}

function csrf_check(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_verify()) {
        http_response_code(403);
        die('Invalid security token. Please go back and try again.');
    }
}

// ════════════════════════════════════════════════════════════
// AUDIT LOGGING
// ════════════════════════════════════════════════════════════
function audit_log(string $action, string $entity_type, ?int $entity_id = null,
                   array $details = [], ?int $user_id = null): void {
    try {
        Database::insert('audit_logs', [
            'user_id'     => $user_id ?? (current_user_id() ?: null),
            'action'      => $action,
            'entity_type' => $entity_type,
            'entity_id'   => $entity_id,
            'details_json'=> !empty($details) ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
            'ip_address'  => client_ip(),
            'user_agent'  => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    } catch (Throwable $e) {
        error_log('Audit log failed: ' . $e->getMessage());
    }
}

// ════════════════════════════════════════════════════════════
// RESPONSE / REDIRECT
// ════════════════════════════════════════════════════════════
function redirect(string $path): never {
    $url = str_starts_with($path, 'http') ? $path : APP_URL . $path;
    header('Location: ' . $url);
    exit;
}

function json_ok(array $data = [], string $msg = 'OK'): never {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['success' => true, 'message' => $msg], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

function json_fail(string $msg, int $code = 400): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

// ════════════════════════════════════════════════════════════
// SANITISATION / VALIDATION
// ════════════════════════════════════════════════════════════
function e(mixed $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function clean(string $v): string {
    return trim(strip_tags($v));
}

function client_ip(): string {
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $k) {
        if (!empty($_SERVER[$k])) return trim(explode(',', $_SERVER[$k])[0]);
    }
    return '0.0.0.0';
}

function validate_postcode(string $pc): bool {
    return (bool) preg_match('/^[A-Z]{1,2}[0-9][0-9A-Z]?\s?[0-9][A-Z]{2}$/i', trim($pc));
}

// ════════════════════════════════════════════════════════════
// FORMATTING
// ════════════════════════════════════════════════════════════
function money(float $amt, string $cur = 'GBP'): string {
    return ['GBP' => '£', 'USD' => '$', 'EUR' => '€'][$cur] ?? $cur . ' '
         . number_format($amt, 2);
}

function time_ago(string $dt): string {
    $d = time() - strtotime($dt);
    if ($d < 60)     return 'just now';
    if ($d < 3600)   return floor($d / 60) . 'm ago';
    if ($d < 86400)  return floor($d / 3600) . 'h ago';
    if ($d < 604800) return floor($d / 86400) . 'd ago';
    return date('d M Y', strtotime($dt));
}

function status_badge(string $status): string {
    $map = [
        'pending'       => 'badge--amber',
        'submitted'     => 'badge--purple',
        'under_review'  => 'badge--purple',
        'approved'      => 'badge--green',
        'rejected'      => 'badge--red',
        'cancelled'     => 'badge--grey',
        'active'        => 'badge--green',
        'dispensed'     => 'badge--green',
        'expired'       => 'badge--grey',
        'processing'    => 'badge--purple',
        'dispatched'    => 'badge--purple',
        'delivered'     => 'badge--green',
        'draft'         => 'badge--grey',
        'paid'          => 'badge--green',
        'unpaid'        => 'badge--amber',
        'failed'        => 'badge--red',
        'scheduled'     => 'badge--purple',
        'in_transit'    => 'badge--purple',
        'out_for_delivery' => 'badge--amber',
    ];
    $cls   = $map[$status] ?? 'badge--grey';
    $label = ucwords(str_replace('_', ' ', $status));
    return "<span class=\"pco-badge $cls\">$label</span>";
}

function rx_ref(int $id): string {
    return 'RX-' . date('Y') . '-' . str_pad($id, 5, '0', STR_PAD_LEFT);
}

function order_ref(int $id): string {
    return 'ORD-' . date('Y') . '-' . str_pad($id, 5, '0', STR_PAD_LEFT);
}

function paginate(int $total, int $page, int $per): array {
    $pages = max(1, (int)ceil($total / $per));
    $page  = max(1, min($page, $pages));
    return [
        'total'    => $total,
        'page'     => $page,
        'per_page' => $per,
        'pages'    => $pages,
        'offset'   => ($page - 1) * $per,
        'has_prev' => $page > 1,
        'has_next' => $page < $pages,
    ];
}

function get_setting(string $key, string $default = ''): string {
    static $cache = [];
    if (!array_key_exists($key, $cache)) {
        $row = Database::fetchOne("SELECT setting_value FROM system_settings WHERE setting_key = ?", [$key]);
        $cache[$key] = $row ? (string)($row['setting_value'] ?? $default) : $default;
    }
    return $cache[$key];
}

function flash_set(string $type, string $msg): void {
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function flash_render(): string {
    if (empty($_SESSION['flash'])) return '';
    $html = '';
    foreach ($_SESSION['flash'] as $f) {
        $icons = ['success'=>'circle-check','error'=>'circle-xmark','warning'=>'triangle-exclamation','info'=>'circle-info'];
        $icon  = $icons[$f['type']] ?? 'circle-info';
        $html .= '<div class="pco-alert pco-alert--' . e($f['type']) . ' flash-message">'
               . '<i class="fa-solid fa-' . $icon . '"></i><span>' . e($f['msg']) . '</span></div>';
    }
    unset($_SESSION['flash']);
    return $html;
}
