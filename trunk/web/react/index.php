<?php
$dist_file = __DIR__ . '/dist/index.html';
if (!file_exists($dist_file)) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    echo "React frontend is not built yet.\n";
    echo "Run: cd /home/judge/src/trunk/frontend && npm install && npm run build\n";
    exit;
}
header('Location: /react/dist/index.html', true, 302);
exit;
