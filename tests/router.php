<?php
// Router for PHP's built-in server (`php -S`), mimicking the Apache mod_rewrite
// rule this CI3 app expects in production (application/config/config.php sets
// index_page = '', i.e. clean URLs) - the built-in server has no .htaccess
// support, so every request that isn't an existing static file is handed to
// index.php exactly as mod_rewrite would.
$root = dirname(__DIR__);
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if ($uri !== '/' && file_exists($root . $uri) && !is_dir($root . $uri)) {
    return false;
}

chdir($root);
require $root . '/index.php';
