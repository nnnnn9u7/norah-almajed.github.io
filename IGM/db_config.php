<?php
// Database settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'user');
define('DB_USER', 'root');
define('DB_PASS', '');

// AI Service 
function _d($s){return base64_decode($s);}
$externalServiceToken = getenv('EXTERNAL_SERVICE_TOKEN');
define('EXTERNAL_SERVICE_TOKEN', $externalServiceToken !== false ? $externalServiceToken : '');
define('EXTERNAL_API_ENDPOINT', _d('aHR0cHM6Ly9hcGkuZ3JvcS5jb20vb3BlbmFpL3YxL2NoYXQvY29tcGxldGlvbnM='));
define('AI_MODEL_VERSION', _d('bGxhbWEtMy4zLTcwYi12ZXJzYXRpbGU='));
define('MAX_QUESTIONS', 1000);
define('MAX_TOKENS', 6000);
define('DEFAULT_EXAM_TIME', 1800); 

// Enable error display (for development)
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Riyadh');

// Database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    die("Database connection failed. Please check your settings.");
}

// ✅ الحل هنا: التحقق مما إذا كانت الدالة موجودة قبل تعريفها
if (!function_exists('checkAdminSession')) {
    function checkAdminSession() {
        // التأكد من أن الجلسة بدأت قبل فحص المتغيرات
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['admin_id'])) {
            header("Location: admin_login.php");
            exit();
        }
        
        return [
            'admin_id' => $_SESSION['admin_id'],
            'admin_username' => $_SESSION['admin_username'],
            'admin_role' => $_SESSION['admin_role'],
            'admin_name' => $_SESSION['admin_name']
        ];
    }
}
?>