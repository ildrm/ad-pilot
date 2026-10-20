<?php
/** Local PSR-4 loader keeps the distributed runtime independent of Composer. */
spl_autoload_register(static function (string $class): void {
    $prefix = 'AdPilot\\';
    if (str_starts_with($class, $prefix)) {
        $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
});
