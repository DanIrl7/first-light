<?php
// Clean URLs for PHP's built-in server, which doesn't read Apache rules:
//   php -S localhost:8085 -t . scripts/router.php
// On the live site, docker/clean-urls.conf does the same job.
if (PHP_SAPI !== 'cli-server') {
    return false;
}

$root = dirname(__DIR__);
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$pages = 'start|loading|result|build|share';

// /result.php is sent to /result (GET only, so a POST keeps its data).
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match("#^/(index|$pages)\.php$#", $path, $match)) {
    $query = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '' ? '?' . $_SERVER['QUERY_STRING'] : '';
    header('Location: ' . ($match[1] === 'index' ? '/' : '/' . $match[1]) . $query, true, 301);
    exit;
}

// /result runs result.php.
if (preg_match("#^/($pages)$#", $path, $match)) {
    chdir($root);
    $_SERVER['SCRIPT_NAME'] = '/' . $match[1] . '.php';
    require $root . '/' . $match[1] . '.php';
    return true;
}

return false; // everything else (assets, /) is served as usual
