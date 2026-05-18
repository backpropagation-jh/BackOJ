<?php
require_once(dirname(__FILE__) . '/bootstrap.php');
api_must_get();

$items = array();
for ($i = 0; $i < count($language_name); $i++) {
    if (!isset($language_name[$i])) continue;
    if ($language_name[$i] === 'UnknownLanguage') continue;

    $masked = (isset($OJ_LANGMASK) && (($OJ_LANGMASK & (1 << $i)) != 0));
    $items[] = array(
        'id' => $i,
        'name' => $language_name[$i],
        'enabled' => !$masked
    );
}

api_json(array(
    'ok' => true,
    'items' => $items
));
