<?php
/**
 * Clear Cache Script for cPanel (No SSH)
 * 
 * Upload file ini ke root folder (sejajar dengan index.php)
 * Akses via browser: https://yourdomain.com/clear-cache.php
 * 
 * PENTING: Hapus file ini setelah selesai untuk keamanan!
 */

// Prevent direct access from other domains
$allowedHosts = ['localhost', 'sekolah.test', 'abaassalam.my.id', 'www.abaassalam.my.id'];
if (!in_array($_SERVER['HTTP_HOST'], $allowedHosts)) {
    die('Access denied');
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Clear Cache - Laravel</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .success { color: #28a745; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0; }
        .warning { color: #856404; padding: 10px; background: #fff3cd; border: 1px solid #ffeeba; border-radius: 4px; margin: 10px 0; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .btn:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🧹 Laravel Cache Cleaner</h1>
        <p>Script untuk clear cache Laravel tanpa SSH/Terminal</p>";

// Check if Laravel is installed
if (!file_exists(__DIR__ . '/artisan')) {
    echo "<div class='error'>❌ File artisan tidak ditemukan. Pastikan script ini ada di root folder Laravel.</div>";
    die("</div></body></html>");
}

// Function to run artisan command
function runArtisan($command) {
    $output = [];
    $return = 0;
    
    // Try to execute artisan command
    exec("php artisan $command 2>&1", $output, $return);
    
    return [
        'success' => $return === 0,
        'output' => implode("\n", $output)
    ];
}

// Clear cache if requested
if (isset($_GET['action']) && $_GET['action'] === 'clear') {
    echo "<h2>🔄 Clearing Cache...</h2>";
    
    // 1. Clear View Cache
    echo "<h3>1. View Cache</h3>";
    $result = runArtisan('view:clear');
    if ($result['success']) {
        echo "<div class='success'>✅ View cache cleared successfully</div>";
    } else {
        echo "<div class='error'>❌ Failed: " . htmlspecialchars($result['output']) . "</div>";
    }
    
    // 2. Clear Config Cache
    echo "<h3>2. Config Cache</h3>";
    $result = runArtisan('config:clear');
    if ($result['success']) {
        echo "<div class='success'>✅ Config cache cleared successfully</div>";
    } else {
        echo "<div class='error'>❌ Failed: " . htmlspecialchars($result['output']) . "</div>";
    }
    
    // 3. Clear Route Cache
    echo "<h3>3. Route Cache</h3>";
    $result = runArtisan('route:clear');
    if ($result['success']) {
        echo "<div class='success'>✅ Route cache cleared successfully</div>";
    } else {
        echo "<div class='error'>❌ Failed: " . htmlspecialchars($result['output']) . "</div>";
    }
    
    // 4. Clear Application Cache
    echo "<h3>4. Application Cache</h3>";
    $result = runArtisan('cache:clear');
    if ($result['success']) {
        echo "<div class='success'>✅ Application cache cleared successfully</div>";
    } else {
        echo "<div class='error'>❌ Failed: " . htmlspecialchars($result['output']) . "</div>";
    }
    
    // 5. Clear Compiled Classes
    echo "<h3>5. Compiled Classes</h3>";
    $result = runArtisan('clear-compiled');
    if ($result['success']) {
        echo "<div class='success'>✅ Compiled classes cleared successfully</div>";
    } else {
        echo "<div class='error'>❌ Failed: " . htmlspecialchars($result['output']) . "</div>";
    }
    
    // 6. Optimize (optional - recache for production)
    echo "<h3>6. Re-optimize for Production</h3>";
    $result = runArtisan('optimize');
    if ($result['success']) {
        echo "<div class='success'>✅ Application optimized successfully</div>";
    } else {
        echo "<div class='error'>❌ Failed: " . htmlspecialchars($result['output']) . "</div>";
    }
    
    echo "<hr><div class='success'><strong>✅ All cache clearing completed!</strong></div>";
    echo "<p><a href='?' class='btn'>Back to Menu</a></p>";

} elseif (isset($_GET['action']) && $_GET['action'] === 'filament') {
    echo "<h2>⚡ Filament Optimization...</h2>";
    
    // 1. Clear Filament Cache
    echo "<h3>1. Clear Filament Cache</h3>";
    $result = runArtisan('filament:clear-cache');
    if ($result['success']) {
        echo "<div class='success'>✅ Filament cache cleared successfully</div>";
    } else {
        echo "<div class='error'>❌ Failed: " . htmlspecialchars($result['output']) . "</div>";
    }
    
    // 2. Optimize Filament
    echo "<h3>2. Optimize Filament</h3>";
    $result = runArtisan('filament:optimize');
    if ($result['success']) {
        echo "<div class='success'>✅ Filament optimized successfully</div>";
    } else {
        echo "<div class='error'>❌ Failed: " . htmlspecialchars($result['output']) . "</div>";
    }
    
    // 3. Cache Filament Components
    echo "<h3>3. Cache Filament Components</h3>";
    $result = runArtisan('filament:cache-components');
    if ($result['success']) {
        echo "<div class='success'>✅ Filament components cached successfully</div>";
    } else {
        echo "<div class='error'>❌ Failed: " . htmlspecialchars($result['output']) . "</div>";
    }
    
    // 4. Clear Icon Cache
    echo "<h3>4. Clear Icon Cache</h3>";
    $result = runArtisan('icons:clear');
    if ($result['success']) {
        echo "<div class='success'>✅ Icon cache cleared successfully</div>";
    } else {
        echo "<div class='warning'>⚠️ Icon cache command not available (skip jika tidak ada blade-icons)</div>";
    }
    
    echo "<hr><div class='success'><strong>✅ Filament optimization completed!</strong></div>";
    echo "<p><a href='?' class='btn'>Back to Menu</a></p>";
    
} else {
    // Show menu
    echo "
        <h2>Available Actions:</h2>
        <p>
            <a href='?action=clear' class='btn'>🧹 Clear All Cache</a>
            <a href='?action=filament' class='btn' style='background: #f59e0b;'>⚡ Optimize Filament</a>
        </p>";
        
        <div class='warning'>
            ⚠️ <strong>PENTING:</strong><br>
            - Pastikan PHP version compatible (PHP 8.1+)<br>
            - Setelah selesai, <strong>HAPUS FILE INI</strong> untuk keamanan!<br>
            - Jangan biarkan file ini accessible di production
        </div>
        
        <h3>Manual Cache Clear (Alternative):</h3>
        <p>Jika script tidak berfungsi, hapus manual via cPanel File Manager:</p>
        <ul>
            <li><code>bootstrap/cache/config.php</code></li>
            <li><code>bootstrap/cache/routes-v7.php</code></li>
            <li><code>bootstrap/cache/packages.php</code></li>
            <li><code>bootstrap/cache/services.php</code></li>
            <li>Semua file di <code>storage/framework/views/</code></li>
            <li>Semua file di <code>storage/framework/cache/data/</code></li>
        </ul>
        
        <h3>Delete This File:</h3>
        <p>
            <a href='#' onclick='if(confirm(\"Delete this file? You need to do this manually via cPanel File Manager.\")) alert(\"Please delete clear-cache.php via cPanel File Manager for security!\")' class='btn btn-danger'>
                🗑️ Remember to Delete clear-cache.php
            </a>
        </p>
    ";
}

echo "
        <hr>
        <p style='color: #666; font-size: 12px;'>TK ABA ASSALAM School Management System</p>
    </div>
</body>
</html>";
?>
