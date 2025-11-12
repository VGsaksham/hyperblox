<?php

function hb_get_persist_base(): string {
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $env = getenv('HYPERBLOX_PERSIST_ROOT');
    if ($env) {
        $candidate = rtrim($env, "/\\");
    } else {
        $default = realpath(__DIR__ . '/../../');
        $candidate = $default !== false ? $default : __DIR__ . '/../../';
    }

    $base = $candidate . DIRECTORY_SEPARATOR;

    if (!is_dir($base)) {
        @mkdir($base, 0777, true);
    }

    return $base;
}

function hb_path(string $relative = ''): string {
    return hb_get_persist_base() . ltrim($relative, "/\\");
}

function hb_tokens_dir(): string {
    return hb_path('tokens' . DIRECTORY_SEPARATOR);
}

function hb_template_dir(string $dir): string {
    return hb_path(rtrim($dir, "/\\") . DIRECTORY_SEPARATOR);
}

function hb_ensure_dir(string $dir): void {
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
}

function hb_rrmdir(string $dir): void {
    if (!is_dir($dir)) {
        return;
    }
    $items = scandir($dir);
    if ($items === false) {
        return;
    }
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            hb_rrmdir($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}


