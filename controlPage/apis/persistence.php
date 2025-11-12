<?php

function hb_get_persist_base(): string {
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $preferred = '/data/hyperblox';
    $candidate = null;
    if (is_dir($preferred) || @mkdir($preferred, 0777, true)) {
        $candidate = $preferred;
    }
    if ($candidate === null) {
        $default = realpath(__DIR__ . '/../../');
        $candidate = $default !== false ? $default : __DIR__ . '/../../';
    }
    $base = rtrim($candidate, "/\\") . DIRECTORY_SEPARATOR;

    if (!is_dir($base)) {
        @mkdir($base, 0777, true);
    }

    return $base;
}

function hb_project_root(): string {
    static $root = null;
    if ($root !== null) {
        return $root;
    }
    $resolved = realpath(__DIR__ . '/../../');
    $root = rtrim($resolved !== false ? $resolved : __DIR__ . '/../../', "/\\") . DIRECTORY_SEPARATOR;
    return $root;
}

function hb_public_template_path(string $dir): string {
    return hb_project_root() . rtrim($dir, "/\\");
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

function hb_copy_directory(string $source, string $destination): void {
    hb_ensure_dir($destination);
    $items = scandir($source);
    if ($items === false) {
        return;
    }
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $src = $source . DIRECTORY_SEPARATOR . $item;
        $dest = $destination . DIRECTORY_SEPARATOR . $item;
        if (is_dir($src)) {
            hb_copy_directory($src, $dest);
        } else {
            @copy($src, $dest);
        }
    }
}

function hb_remove_public_template(string $dir): void {
    $publicPath = hb_public_template_path($dir);
    if (is_link($publicPath) || is_file($publicPath)) {
        @unlink($publicPath);
    } elseif (is_dir($publicPath)) {
        hb_rrmdir($publicPath);
    }
}

function hb_ensure_public_template(string $dir): void {
    $target = hb_template_dir($dir);
    $publicPath = hb_public_template_path($dir);

    hb_remove_public_template($dir);
    hb_copy_directory($target, $publicPath);
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


