<?php
/** Development commands; never loaded by WordPress. */
if (PHP_SAPI !== 'cli') {
    exit(1);
}

$root = dirname(__DIR__);
$command = $argv[1] ?? '';

if ($command === 'lint') {
    $files = glob($root . '/*.php');
    foreach (['src', 'scripts', 'tests'] as $folder) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $folder, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
    }
    sort($files);
    $failed = false;
    foreach ($files as $file) {
        $process = proc_open([PHP_BINARY, '-l', $file], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException('Could not start the PHP syntax checker.');
        }
        $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        if (proc_close($process) !== 0) {
            fwrite(STDERR, $output);
            $failed = true;
        }
    }
    echo count($files) . " PHP files checked.\n";
    exit($failed ? 1 : 0);
}

if ($command !== 'package') {
    fwrite(STDERR, "Usage: php scripts/plugin.php lint|package\n");
    exit(1);
}
if (!class_exists(ZipArchive::class)) {
    throw new RuntimeException('Packaging requires the PHP zip extension.');
}
preg_match('/^\s*\* Version:\s*([0-9.]+)\s*$/m', file_get_contents($root . '/adpilot.php'), $version);
if (empty($version[1])) {
    throw new RuntimeException('Plugin version header is missing.');
}
$files = ['adpilot.php', 'uninstall.php', 'LICENSE', 'readme.txt', 'THIRD_PARTY_NOTICES.md'];
foreach (['src', 'blocks', 'assets', 'languages'] as $folder) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $folder, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->isFile() && !$file->isLink() && in_array($file->getExtension(), ['php', 'json', 'js', 'css', 'po', 'mo', 'pot'], true)) {
            $files[] = substr($file->getPathname(), strlen($root) + 1);
        }
    }
}
sort($files);
if (!is_dir($root . '/dist') && !mkdir($root . '/dist', 0755)) {
    throw new RuntimeException('Cannot create the package output directory.');
}
$path = $root . '/dist/adpilot-' . $version[1] . '.zip';
$zip = new ZipArchive();
if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    throw new RuntimeException('Cannot create the plugin archive.');
}
foreach ($files as $file) {
    $name = 'adpilot/' . str_replace(DIRECTORY_SEPARATOR, '/', $file);
    if (!$zip->addFile($root . '/' . $file, $name) || !$zip->setMtimeName($name, 1767225600) || !$zip->setExternalAttributesName($name, ZipArchive::OPSYS_UNIX, 0100644 << 16)) {
        throw new RuntimeException('Cannot add package input: ' . $file);
    }
}
if (!$zip->close()) {
    throw new RuntimeException('Cannot finish the plugin archive.');
}
echo basename($path) . ' (' . count($files) . ' files), SHA-256: ' . hash_file('sha256', $path) . "\n";
