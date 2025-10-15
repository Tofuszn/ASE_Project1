<?php
declare(strict_types=1);
// ------------------------------
// Database Connection + Helpers
// For ASE230 Project 1 – Dealership API
// ------------------------------

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Authorization, Content-Type");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// --- Database Connection ---
$host = "127.0.0.1";       // MySQL server (Homebrew)
$port = 3306;              // Default MySQL port
$dbname = "dealership";    // Your database name
$username = "root";        // MySQL username
$password = "Carlo"; // MySQL password (set this in Terminal)

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Display readable error if connection fails
    die(json_encode(["error" => "Database connection failed: " . $e->getMessage()]));
}

// --- Helper Functions ---

// Get JSON body from request
function json_body() {
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

// Send JSON response with status code
function respond($code, $data) {
    header("Content-Type: application/json");
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// Require and validate Bearer Token
function require_token($pdo) {
    $headers = function_exists('getallheaders') ? getallheaders() : [];

    // Look for Authorization header
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (!preg_match('/^Bearer\\s+(.+)$/i', $auth, $matches)) {
        respond(401, ["error" => "Missing or invalid Authorization header"]);
    }

    $token = $matches[1];

    // Check token in staff table
    $stmt = $pdo->prepare("SELECT id, username, role FROM staff WHERE token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        respond(403, ["error" => "Invalid token"]);
    }

    return $user; // Return user info if valid
}
?>
