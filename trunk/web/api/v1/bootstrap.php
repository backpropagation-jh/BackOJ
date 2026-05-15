<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (preg_match('/^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/', $origin)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
    header('Vary: Origin');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(204);
    exit;
}

require_once(dirname(__FILE__) . '/../../include/db_info.inc.php');
require_once(dirname(__FILE__) . '/../../include/const.inc.php');
require_once(dirname(__FILE__) . '/../../include/setlang.php');

function api_json($data, $status = 200)
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function api_get_int($name, $default = 0, $min = null, $max = null)
{
    $value = isset($_GET[$name]) ? intval($_GET[$name]) : $default;
    if ($min !== null && $value < $min) $value = $min;
    if ($max !== null && $value > $max) $value = $max;
    return $value;
}

function api_get_string($name, $default = '')
{
    if (!isset($_GET[$name])) return $default;
    return trim((string)$_GET[$name]);
}

function api_must_get()
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
        api_json(array('ok' => false, 'error' => 'Method Not Allowed'), 405);
    }
}

function api_current_user_id()
{
    global $OJ_NAME;
    return $_SESSION[$OJ_NAME . '_user_id'] ?? null;
}

function api_is_admin()
{
    global $OJ_NAME;
    return isset($_SESSION[$OJ_NAME . '_administrator'])
        || isset($_SESSION[$OJ_NAME . '_problem_editor'])
        || isset($_SESSION[$OJ_NAME . '_contest_creator']);
}
