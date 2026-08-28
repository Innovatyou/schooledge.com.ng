<?php
/**
 * PHPUnit bootstrap: launches a real PHP built-in server for the app once for
 * the whole test run (killed via a registered shutdown function), so tests
 * exercise the actual CI3 front controller and routes.php over real HTTP -
 * the same way every endpoint in this app was manually verified throughout
 * development - rather than trying to instantiate CI3 controllers in-process,
 * which the framework was never written to support outside a real request.
 */

require __DIR__ . '/../vendor/autoload.php';

define('TEST_SERVER_HOST', '127.0.0.1');
define('TEST_SERVER_PORT', getenv('TEST_SERVER_PORT') ?: 8199);
define('TEST_BASE_URL', 'http://' . TEST_SERVER_HOST . ':' . TEST_SERVER_PORT);

$root = dirname(__DIR__);
$descriptors = array(1 => array('pipe', 'w'), 2 => array('pipe', 'w'));
putenv('CI_ENV=development');
$process = proc_open(
    sprintf('php -S %s:%d -t %s %s', TEST_SERVER_HOST, TEST_SERVER_PORT, escapeshellarg($root), escapeshellarg($root . '/tests/router.php')),
    $descriptors,
    $pipes,
    $root,
    null
);
if (!is_resource($process)) {
    fwrite(STDERR, "Could not start the PHP built-in test server.\n");
    exit(1);
}
stream_set_blocking($pipes[1], false);
stream_set_blocking($pipes[2], false);

register_shutdown_function(function () use ($process, $pipes) {
    foreach ($pipes as $pipe) if (is_resource($pipe)) fclose($pipe);
    if (is_resource($process)) {
        $status = proc_get_status($process);
        if (!empty($status['pid'])) {
            // proc_terminate() alone leaves the PHP dev server's child process
            // running on Windows; taskkill /T reaps the whole process tree.
            if (stripos(PHP_OS, 'WIN') === 0) {
                exec('taskkill /F /T /PID ' . (int)$status['pid'] . ' > NUL 2>&1');
            } else {
                proc_terminate($process);
            }
        }
        proc_close($process);
    }
});

$deadline = microtime(true) + 15;
$up = false;
while (microtime(true) < $deadline) {
    $connection = @fsockopen(TEST_SERVER_HOST, TEST_SERVER_PORT, $errno, $errstr, 0.5);
    if ($connection) {
        fclose($connection);
        $up = true;
        break;
    }
    usleep(150000);
}
if (!$up) {
    fwrite(STDERR, "Test server did not start listening on " . TEST_BASE_URL . " in time.\n");
    exit(1);
}
