<?php
namespace App\Services;

use OpenAI\Client;
use OpenAI;

class TranscriptionService {
    private $apiKey;
    private $client;

    public function __construct($apiKey) {
        if (empty($apiKey)) {
            throw new \Exception('La API Key no puede estar vacía');
        }

        $this->apiKey = $apiKey;
        try {
            $this->client = OpenAI::factory()
                ->withApiKey($apiKey)
                ->withHttpClient(new \GuzzleHttp\Client([
                    'timeout' => 30,
                    'verify' => false // Si hay problemas con certificados SSL
                ]))
                ->make();
        } catch (\Exception $e) {
            error_log('Error al inicializar cliente OpenAI: ' . $e->getMessage());
            throw new \Exception('Error al inicializar OpenAI: ' . $e->getMessage());
        }
    }

    public function transcribe($audioPath, $language = null) {
        try {
            error_log("Iniciando transcripción de audio: " . $audioPath);
            
            if (!file_exists($audioPath)) {
                throw new \Exception("El archivo de audio no existe: " . $audioPath);
            }

            // Crear el stream del archivo
            $stream = \GuzzleHttp\Psr7\Utils::tryFopen($audioPath, 'r');
            
            $response = $this->client->audio()->transcribe([
                'model' => 'whisper-1',
                'file' => $stream,
                'language' => $language,
                'response_format' => 'text'
            ]);

            error_log("Transcripción completada exitosamente");
            return [
                'success' => true,
                'text' => $response->text
            ];
        } catch (\Exception $e) {
            error_log("Error en transcripción: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        } finally {
            // Cerrar el stream si existe
            if (isset($stream) && is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    public function testConnection() {
        try {
            // Remover la validación del formato ya que OpenAI ha cambiado su estructura
            if (empty($this->apiKey)) {
                throw new \Exception('La API Key no puede estar vacía');
            }

            // Hacer una petición simple a la API
            $response = $this->client->models()->retrieve('whisper-1');
            
            error_log('Test de conexión exitoso: ' . json_encode($response));
            return true;
        } catch (\Exception $e) {
            error_log('Error en test de conexión: ' . $e->getMessage());
            throw new \Exception($e->getMessage());
        }
    }
} 