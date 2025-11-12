<?php

function hb_get_persist_base(): string {
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $env = getenv('HYPERBLOX_PERSIST_ROOT');
    if (!$env) {
        $env = $_ENV['HYPERBLOX_PERSIST_ROOT'] ?? $_SERVER['HYPERBLOX_PERSIST_ROOT'] ?? '';
    }

    if ($env) {
        $candidate = rtrim($env, "\\/");
    } else {
        $dataPath = '/data/hyperblox';
        if (is_dir('/data') && is_writable('/data')) {
            $candidate = $dataPath;
        } else {
            $default = realpath(__DIR__ . '/../../');
            $candidate = $default !== false ? $default : __DIR__ . '/../../';
        }
    }

    $base = $candidate . DIRECTORY_SEPARATOR;

    if (!is_dir($base)) {
        @mkdir($base, 0777, true);
    }

    error_log('[HYPERBLOX] Persistence base: ' . $base);

    return $base;
}

function hb_path(string $relative = ''): string {
    return hb_get_persist_base() . ltrim($relative, "/\\");
}

function hb_tokens_dir(): string {
    return hb_path('tokens' . DIRECTORY_SEPARATOR);
}

function hb_token_file_paths(string $token): array {
    $paths = [];
    $token = trim($token);
    if ($token === '') {
        return $paths;
    }
    $paths[] = hb_tokens_dir() . $token . '.txt';
    $paths[] = __DIR__ . DIRECTORY_SEPARATOR . 'tokens' . DIRECTORY_SEPARATOR . $token . '.txt';
    $paths[] = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tokens' . DIRECTORY_SEPARATOR . $token . '.txt';
    return array_unique($paths);
}

function hb_find_token_file(string $token): ?string {
    foreach (hb_token_file_paths($token) as $candidate) {
        if ($candidate && file_exists($candidate)) {
            return $candidate;
        }
    }
    return null;
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


