<?php
require_once(dirname(__FILE__) . '/bootstrap.php');
api_must_get();

$page = api_get_int('page', 1, 1);
$page_size = api_get_int('page_size', 20, 1, 100);
$search_raw = api_get_string('search', '');
$offset = ($page - 1) * $page_size;

$is_admin = api_is_admin();
$now = date('Y-m-d H:i', time());
$where = array();
$args = array();

if ($search_raw !== '') {
    $where[] = '(title LIKE ? OR source LIKE ?)';
    $search = '%' . $search_raw . '%';
    $args[] = $search;
    $args[] = $search;
}

if (!$is_admin) {
    if (isset($OJ_FREE_PRACTICE) && $OJ_FREE_PRACTICE) {
        $where[] = "defunct='N'";
    } else {
        $where[] = "defunct='N'";
        $where[] = "problem_id NOT IN (
            SELECT cp.problem_id
            FROM contest c
            INNER JOIN contest_problem cp ON c.contest_id=cp.contest_id
            WHERE c.defunct='N' AND c.end_time > '$now'
        )";
    }
}

$where_sql = empty($where) ? '1=1' : implode(' AND ', $where);

$count_sql = "SELECT COUNT(1) AS total FROM problem WHERE $where_sql";
$total_rows = pdo_query($count_sql, $args);
$total = intval($total_rows[0]['total'] ?? 0);

$list_sql = "SELECT problem_id,title,source,accepted,submit,defunct
             FROM problem
             WHERE $where_sql
             ORDER BY problem_id
             LIMIT $offset,$page_size";
$rows = pdo_query($list_sql, $args);

$items = array();
foreach ($rows as $row) {
    $item = array(
        'problem_id' => intval($row['problem_id']),
        'title' => $row['title'],
        'source' => $row['source'],
        'accepted' => intval($row['accepted']),
        'submit' => intval($row['submit'])
    );
    if ($is_admin) {
        $item['defunct'] = $row['defunct'];
    }
    $items[] = $item;
}

api_json(array(
    'ok' => true,
    'page' => $page,
    'page_size' => $page_size,
    'total' => $total,
    'items' => $items
));
