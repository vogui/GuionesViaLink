<?php
// Configurar el log de errores
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');
error_log("=== Nueva solicitud ===");
error_log("URI: " . $_SERVER['REQUEST_URI']);
error_log("Método: " . $_SERVER['REQUEST_METHOD']);

// Al inicio del archivo
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Cargar el autoloader de Composer
$autoloader = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloader)) {
    die('Por favor, ejecuta "composer install" primero');
}
require $autoloader;

// Habilitar visualización de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Manejo de rutas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], 'download') !== false) {
    header('Content-Type: application/json');
    
    try {
        $input = file_get_contents('php://input');
        error_log("Datos recibidos: " . $input);
        
        $controller = new \App\Controllers\DownloadController();
        $result = $controller->download();
        
        error_log("Respuesta: " . $result);
        echo $result;
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// Agregar después de la ruta de download
if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], 'test-webhook') !== false) {
    header('Content-Type: application/json');
    
    try {
        $controller = new \App\Controllers\WebhookController();
        echo $controller->test();
    } catch (Exception $e) {
        error_log("Error en test-webhook: " . $e->getMessage());
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// Agregar después de la ruta test-webhook
if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], 'test-openai') !== false) {
    header('Content-Type: application/json');
    
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON inválido: ' . json_last_error_msg());
        }
        
        $apiKey = $input['api_key'] ?? '';

        if (empty($apiKey)) {
            throw new \Exception('API Key no proporcionada');
        }

        // Probar la conexión con OpenAI
        $transcriptionService = new \App\Services\TranscriptionService($apiKey);
        $result = $transcriptionService->testConnection();

        echo json_encode(['success' => true]);
    } catch (\Exception $e) {
        error_log("Error en test-openai: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// Página principal
require __DIR__ . '/views/home.php'; 