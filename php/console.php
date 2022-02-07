<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/init.php';

(function() {
    $resources = new \App\ResourceManager();
    $actionClass = 'App\\Console\\Actions\\'
        . implode('', array_map(function($w) { return ucfirst($w); }, explode('-', $_SERVER['argv'][1])))
        . 'Action';
    $action = new $actionClass($resources);
    try {
        $statusCode = $action();
    } catch (Throwable $e) {
        echo "Error: {$e->getMessage()}\n";
        echo "\nException was thrown in file {$e->getFile()} at line {$e->getLine()}\nDebug backtrace:\n{$e->getTraceAsString()}\n";

        if ($e->getCode() != 0) {
            $statusCode = $e->getCode();
        } else {
            $statusCode = 255;
        }
    }
    exit($statusCode);
})();
