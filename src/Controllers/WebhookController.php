<?php
namespace App\Controllers;

class WebhookController {
    public function test() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $webhookUrl = $input['webhook_url'] ?? '';

            if (empty($webhookUrl)) {
                return json_encode(['success' => false, 'error' => 'URL no proporcionada']);
            }

            // Datos de prueba
            $testData = [
                'url' => 'https://ejemplo.com/video_test.mp4',
                'source' => 'https://youtube.com/test',
                'spanish_text' => 'Esto es una prueba de transcripción en español',
                'english_text' => 'This is a test transcription in English',
                'timestamp' => date('c')
            ];

            // Enviar petición de prueba
            $ch = curl_init($webhookUrl);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($testData),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json'
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                throw new \Exception(curl_error($ch));
            }
            
            curl_close($ch);

            return json_encode([
                'success' => $httpCode >= 200 && $httpCode < 300,
                'response' => $response,
                'http_code' => $httpCode
            ]);

        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
} 