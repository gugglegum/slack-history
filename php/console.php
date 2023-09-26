<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/init.php';

(function() {
    $resources = new \App\ResourceManager();
//    $actionClass = 'App\\Console\\Actions\\'
//        . implode('', array_map(function($w) { return ucfirst($w); }, explode('-', $_SERVER['argv'][1])))
//        . 'Action';
    $actionClass = match ($_SERVER['argv'][1]) {
        'fetch-history' => \App\Console\Actions\FetchHistoryAction::class,
        'fetch-files' => \App\Console\Actions\FetchFilesAction::class,
        'compile-html' => \App\Console\Actions\CompileHtmlAction::class,
        'test' => \App\Console\Actions\TestAction::class,
    };
    $action = new $actionClass($resources);
    try {
        $statusCode = (int) $action();
    } catch (Throwable $e) {
        echo "\nException " . get_class($e) . " was thrown in file {$e->getFile()} at line {$e->getLine()}\nDebug backtrace:\n{$e->getTraceAsString()}\n";
        echo "Code: {$e->getCode()}\n";
        echo "Message: {$e->getMessage()}\n";

        if ($e->getCode() != 0) {
            $statusCode = $e->getCode();
        } else {
            $statusCode = 255;
        }
    }
    exit($statusCode);
})();
