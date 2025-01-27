<?php
namespace App\Controllers;

class DownloadController {
    public function download() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $videoUrl = $input['video_url'] ?? '';
            $webhookUrl = $input['webhook_url'] ?? '';
            $apiKey = $input['api_key'] ?? '';

            if (empty($videoUrl)) {
                throw new \Exception('URL del video no proporcionada');
            }

            if (empty($apiKey)) {
                throw new \Exception('API Key de OpenAI no proporcionada');
            }

            // Hacer la API key disponible globalmente
            global $savedApiKey;
            $savedApiKey = $apiKey;

            error_log("=== Iniciando descarga ===");
            error_log("URL: " . $videoUrl);
            error_log("API Key recibida: " . (empty($apiKey) ? 'NO' : 'SÍ'));

            $service = new \App\Services\VideoDownloadService();
            $result = $service->download($videoUrl, $webhookUrl);

            return json_encode($result);
        } catch (\Exception $e) {
            error_log("Error en DownloadController: " . $e->getMessage());
            return json_encode(['error' => $e->getMessage()]);
        }
    }
} 