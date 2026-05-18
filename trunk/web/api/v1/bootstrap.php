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
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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

function api_must_post()
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        api_json(array('ok' => false, 'error' => 'Method Not Allowed'), 405);
    }
}

function api_body_json()
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return array();
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        api_json(array('ok' => false, 'error' => 'Invalid JSON body'), 400);
    }

    return $data;
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

function api_get_accessible_problem($problem_id)
{
    $problem_id = intval($problem_id);
    if ($problem_id <= 0) return null;

    $is_admin = api_is_admin();
    $now = date('Y-m-d H:i', time());

    if ($is_admin) {
        $sql = "SELECT problem_id,title,defunct,remote_oj FROM problem WHERE problem_id=? LIMIT 1";
        $rows = pdo_query($sql, $problem_id);
    } else if (isset($OJ_FREE_PRACTICE) && $OJ_FREE_PRACTICE) {
        $sql = "SELECT problem_id,title,defunct,remote_oj FROM problem WHERE problem_id=? AND defunct='N' LIMIT 1";
        $rows = pdo_query($sql, $problem_id);
    } else {
        $sql = "SELECT p.problem_id,p.title,p.defunct,p.remote_oj
                FROM problem p
                WHERE p.problem_id=?
                  AND p.defunct='N'
                  AND NOT EXISTS (
                      SELECT 1
                      FROM contest_problem cp
                      INNER JOIN contest c ON cp.contest_id=c.contest_id
                      WHERE cp.problem_id=p.problem_id
                        AND ((c.end_time > '$now' AND c.defunct='N') OR c.private='1')
                  )
                LIMIT 1";
        $rows = pdo_query($sql, $problem_id);
    }

    if (empty($rows)) return null;
    return $rows[0];
}
