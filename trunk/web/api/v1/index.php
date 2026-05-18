<?php
require_once(dirname(__FILE__) . '/bootstrap.php');
api_must_get();

api_json(array(
    'ok' => true,
    'name' => 'BackOJ API v1',
    'endpoints' => array(
        '/api/v1/problems.php?page=1&page_size=20&search=',
        '/api/v1/problem.php?id=1000',
        '/api/v1/status.php?page=1&page_size=20&problem_id=&user_id=',
        '/api/v1/languages.php',
        '/api/v1/submit.php (POST JSON: problem_id, language, source)'
    )
));
