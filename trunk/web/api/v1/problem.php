<?php
require_once(dirname(__FILE__) . '/bootstrap.php');
api_must_get();

$problem_id = api_get_int('id', 0, 1);
if ($problem_id <= 0) {
    api_json(array('ok' => false, 'error' => 'Missing or invalid problem id'), 400);
}

$is_admin = api_is_admin();
$now = date('Y-m-d H:i', time());

if ($is_admin) {
    $sql = "SELECT problem_id,title,description,input,output,sample_input,sample_output,hint,source,time_limit,memory_limit,accepted,submit,spj,defunct
            FROM problem WHERE problem_id=? LIMIT 1";
    $rows = pdo_query($sql, $problem_id);
} else if (isset($OJ_FREE_PRACTICE) && $OJ_FREE_PRACTICE) {
    $sql = "SELECT problem_id,title,description,input,output,sample_input,sample_output,hint,source,time_limit,memory_limit,accepted,submit,spj,defunct
            FROM problem WHERE problem_id=? AND defunct='N' LIMIT 1";
    $rows = pdo_query($sql, $problem_id);
} else {
    $sql = "SELECT problem_id,title,description,input,output,sample_input,sample_output,hint,source,time_limit,memory_limit,accepted,submit,spj,defunct
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

if (empty($rows)) {
    api_json(array('ok' => false, 'error' => 'Problem not found or not accessible'), 404);
}

$row = $rows[0];

api_json(array(
    'ok' => true,
    'item' => array(
        'problem_id' => intval($row['problem_id']),
        'title' => $row['title'],
        'description' => $row['description'],
        'input' => $row['input'],
        'output' => $row['output'],
        'sample_input' => $row['sample_input'],
        'sample_output' => $row['sample_output'],
        'hint' => $row['hint'],
        'source' => $row['source'],
        'time_limit' => intval($row['time_limit']),
        'memory_limit' => intval($row['memory_limit']),
        'accepted' => intval($row['accepted']),
        'submit' => intval($row['submit']),
        'spj' => intval($row['spj']),
        'defunct' => $row['defunct']
    )
));
