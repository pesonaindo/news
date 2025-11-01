<?php
// Konfigurasi
$directory = __DIR__;

// Pola yang dianggap mencurigakan (indikasi backdoor)
$suspicious_patterns = [
    'eval\(',
    'base64_decode\(',
    'gzinflate\(',
    'str_rot13\(',
    'system\(',
    'exec\(',
    'passthru\(',
    'shell_exec\(',
    'proc_open\(',
    'popen\(',
    'curl_exec\(',
    'goto ',
    '\$_(GET|POST|REQUEST|SERVER)\[.*\]',
    'assert\(',
    'php_uname\('
];

// Whitelist file (file yang aman meskipun mengandung fungsi di atas)
$whitelist = [
    // Contoh path absolut atau relatif dari file yang kamu anggap aman:
    '/lib/pkp/classes/core/String.inc.php',
    '/lib/pkp/classes/core/PKPApplication.inc.php',
    '/lib/pkp/plugins/generic/counter/CounterPlugin.inc.php',
    '/lib/pkp/plugins/importexport/native/filter/NativeImportFilter.inc.php'
];

// Menyesuaikan format path agar cocok dengan OS
$whitelist = array_map(function($path) use ($directory) {
    return realpath($directory . $path);
}, $whitelist);

$suspicious_files = [];

function scan_files($dir, $patterns, $whitelist, &$results) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = realpath($dir . DIRECTORY_SEPARATOR . $file);
        if (is_dir($path)) {
            scan_files($path, $patterns, $whitelist, $results);
        } else if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            if (in_array($path, $whitelist)) continue; // Lewati file yang ada di whitelist

            $content = @file_get_contents($path);
            if (!$content) continue;

            foreach ($patterns as $pattern) {
                if (preg_match('/' . $pattern . '/i', $content)) {
                    $results[] = [
                        'path' => $path,
                        'modified' => filemtime($path),
                    ];
                    break;
                }
            }
        }
    }
}

scan_files($directory, $suspicious_patterns, $whitelist, $suspicious_files);

usort($suspicious_files, function ($a, $b) {
    return $b['modified'] <=> $a['modified'];
});

$is_cli = php_sapi_name() === 'cli';

if (!$is_cli) {
    echo <<<HTML
<style>
    a.suspicious-link {
        color: blue;
        text-decoration: none;
    }
    a.suspicious-link.clicked {
        color: green;
    }
</style>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const links = document.querySelectorAll('a.suspicious-link');
        links.forEach(link => {
            const href = link.getAttribute('href');
            if (localStorage.getItem(href) === 'clicked') {
                link.classList.add('clicked');
            }
            link.addEventListener('click', function () {
                localStorage.setItem(href, 'clicked');
                link.classList.add('clicked');
            });
        });
    });
</script>
<pre>
HTML;
}

if (!empty($suspicious_files)) {
    echo "⚠️ File mencurigakan ditemukan (potensi backdoor):\n\n";
    foreach ($suspicious_files as $file) {
        $path = $file['path'];
        $relative_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', $path);
        if ($is_cli) {
            echo "$path\n";
        } else {
            echo "<a class='suspicious-link' href='$relative_path' target='_blank'>$relative_path</a>\n";
        }
    }
} else {
    echo "✅ Tidak ditemukan file mencurigakan.\n";
}

if (!$is_cli) echo "</pre>";
?>
