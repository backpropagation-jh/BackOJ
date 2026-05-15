<?php
require_once(dirname(__FILE__) . '/bootstrap.php');
api_must_get();

$page = api_get_int('page', 1, 1);
$page_size = api_get_int('page_size', 20, 1, 100);
$offset = ($page - 1) * $page_size;

$filter_user = api_get_string('user_id', '');
$problem_id = api_get_int('problem_id', 0, 0);

$current_user = api_current_user_id();
$can_view_all = (isset($OJ_PUBLIC_STATUS) && $OJ_PUBLIC_STATUS)
    || api_is_admin()
    || isset($_SESSION[$OJ_NAME . '_source_browser']);

if (!$can_view_all && !$current_user) {
    api_json(array('ok' => false, 'error' => 'Login required'), 401);
}

$where = array('result >= 4');
$args = array();

if ($problem_id > 0) {
    $where[] = 'problem_id = ?';
    $args[] = $problem_id;
}

if ($filter_user !== '') {
    if (!$can_view_all && $current_user !== $filter_user) {
        api_json(array('ok' => false, 'error' => 'Forbidden'), 403);
    }
    $where[] = 'user_id = ?';
    $args[] = $filter_user;
} else if (!$can_view_all && $current_user) {
    $where[] = 'user_id = ?';
    $args[] = $current_user;
}

$where_sql = implode(' AND ', $where);

$count_sql = "SELECT COUNT(1) AS total FROM solution WHERE $where_sql";
$total_rows = pdo_query($count_sql, $args);
$total = intval($total_rows[0]['total'] ?? 0);

$list_sql = "SELECT solution_id,problem_id,user_id,nick,result,memory,time,language,pass_rate,in_date,contest_id
             FROM solution
             WHERE $where_sql
             ORDER BY solution_id DESC
             LIMIT $offset,$page_size";
$rows = pdo_query($list_sql, $args);

$items = array();
foreach ($rows as $row) {
    $result_code = intval($row['result']);
    $lang_code = intval($row['language']);
    $items[] = array(
        'solution_id' => intval($row['solution_id']),
        'problem_id' => intval($row['problem_id']),
        'user_id' => $row['user_id'],
        'nick' => $row['nick'],
        'result' => $result_code,
        'result_text' => $jresult[$result_code] ?? (string)$result_code,
        'memory' => intval($row['memory']),
        'time' => intval($row['time']),
        'language' => $lang_code,
        'language_text' => $language_name[$lang_code] ?? (string)$lang_code,
        'pass_rate' => floatval($row['pass_rate']),
        'in_date' => $row['in_date'],
        'contest_id' => intval($row['contest_id'])
    );
}

api_json(array(
    'ok' => true,
    'page' => $page,
    'page_size' => $page_size,
    'total' => $total,
    'items' => $items
));
