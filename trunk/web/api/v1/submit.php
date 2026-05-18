<?php
require_once(dirname(__FILE__) . '/bootstrap.php');
require_once(dirname(__FILE__) . '/../../include/my_func.inc.php');
api_must_post();

$user_id = api_current_user_id();
if (!$user_id) {
    api_json(array('ok' => false, 'error' => 'Login required'), 401);
}

$body = api_body_json();
$problem_id = intval($body['problem_id'] ?? 0);
$language = intval($body['language'] ?? 1);
$source = (string)($body['source'] ?? '');

if ($problem_id <= 0) {
    api_json(array('ok' => false, 'error' => 'Invalid problem_id'), 400);
}

$problem = api_get_accessible_problem($problem_id);
if (!$problem) {
    api_json(array('ok' => false, 'error' => 'Problem not found or not accessible'), 404);
}

if ($language < 0 || $language >= count($language_name)) {
    api_json(array('ok' => false, 'error' => 'Invalid language'), 400);
}

if (isset($OJ_LANGMASK) && (($OJ_LANGMASK & (1 << $language)) != 0)) {
    api_json(array('ok' => false, 'error' => 'Language disabled'), 400);
}

$source = str_replace("\r\n", "\n", $source);
$source = str_replace("\r", "\n", $source);
$len = strlen($source);
if ($len < 2) {
    api_json(array('ok' => false, 'error' => 'Source too short'), 400);
}
if ($len > 65536) {
    api_json(array('ok' => false, 'error' => 'Source too long'), 400);
}

if (!isset($OJ_SUBMIT_COOLDOWN_TIME)) $OJ_SUBMIT_COOLDOWN_TIME = 5;
$time_point = date("Y-m-d H:i:s", time() - $OJ_SUBMIT_COOLDOWN_TIME);
$recent = pdo_query("SELECT solution_id FROM solution WHERE user_id=? AND in_date>? ORDER BY in_date DESC LIMIT 1", $user_id, $time_point);
if (!empty($recent)) {
    api_json(array('ok' => false, 'error' => 'Submit too frequently', 'retry_after_seconds' => $OJ_SUBMIT_COOLDOWN_TIME), 429);
}

$nick_rows = pdo_query("SELECT nick FROM users WHERE user_id=?", $user_id);
$nick = $user_id;
if (!empty($nick_rows) && !empty($nick_rows[0]['nick'])) {
    $nick = $nick_rows[0]['nick'];
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $tmp_ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $ip = trim($tmp_ip[0]);
}

$insert_id = pdo_query(
    "INSERT INTO solution(problem_id,user_id,nick,in_date,language,ip,code_length,result) VALUES(?,?,?,NOW(),?,?,?,14)",
    $problem_id,
    $user_id,
    $nick,
    $language,
    $ip,
    $len
);

if (intval($insert_id) <= 0) {
    api_json(array('ok' => false, 'error' => 'Failed to create solution'), 500);
}

pdo_query("INSERT INTO source_code_user(solution_id,source) VALUES(?,?)", $insert_id, $source);
pdo_query("INSERT INTO source_code(solution_id,source) VALUES(?,?)", $insert_id, $source);
pdo_query("UPDATE problem SET submit=submit+1 WHERE problem_id=?", $problem_id);

$result_code = 0;
$remote_oj = (string)($problem['remote_oj'] ?? '');
if ($remote_oj !== '') {
    $result_code = 16;
    pdo_query("UPDATE solution SET result=16,remote_oj=? WHERE solution_id=?", $remote_oj, $insert_id);
}

pdo_query("UPDATE solution SET result=? WHERE solution_id=?", $result_code, $insert_id);
if (isset($OJ_UDP) && $OJ_UDP && $result_code == 0) {
    trigger_judge($insert_id);
}

api_json(array(
    'ok' => true,
    'solution_id' => intval($insert_id),
    'problem_id' => $problem_id,
    'language' => $language,
    'result' => $result_code
));
