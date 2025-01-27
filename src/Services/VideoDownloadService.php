<?php
namespace App\Services;

class VideoDownloadService {
    private $downloadPath;
    private $ytdlpPath;

    public function __construct() {
        $this->downloadPath = realpath(__DIR__ . '/../../public/downloads');
        $this->ytdlpPath = realpath(__DIR__ . '/../../bin/yt-dlp.exe');
        
        error_log("=== Inicializando VideoDownloadService ===");
        error_log("Download Path: " . $this->downloadPath);
        error_log("yt-dlp Path: " . $this->ytdlpPath);
        
        // Verificar carpeta de descargas
        if (!file_exists($this->downloadPath)) {
            error_log("Creando directorio de descargas...");
            mkdir($this->downloadPath, 0777, true);
        }

        // Verificar y descargar yt-dlp si es necesario
        if (!$this->ytdlpPath || !file_exists($this->ytdlpPath)) {
            error_log("yt-dlp.exe no encontrado, intentando descargar...");
            try {
                $this->ytdlpPath = $this->downloadYtDlp();
            } catch (\Exception $e) {
                throw new \Exception("No se pudo descargar yt-dlp: " . $e->getMessage());
            }
        }
    }

    public function download($url, $webhookUrl = null) {
        try {
            error_log("=== Iniciando descarga ===");
            error_log("URL: " . $url);
            error_log("Webhook URL: " . ($webhookUrl ?? 'No proporcionada'));
            
            // 1. Primero realizamos la descarga completa
            $downloadResult = $this->downloadVideo($url);
            
            // 2. Si la descarga fue exitosa y hay webhook, enviamos la información
            if ($webhookUrl && $downloadResult['success']) {
                error_log("\nPreparando datos para webhook...");
                
                // Asegurarnos de que tenemos una URL completa para el video
                $videoUrl = $this->getPublicUrl($downloadResult['file']);
                
                $webhookData = [
                    'video' => [
                        'url' => $videoUrl,
                        'source_url' => $url
                    ],
                    'transcriptions' => [
                        'spanish' => isset($downloadResult['transcriptions']['es']) ? $downloadResult['transcriptions']['es']['text'] : '',
                        'english' => isset($downloadResult['transcriptions']['en']) ? $downloadResult['transcriptions']['en']['text'] : ''
                    ]
                ];
                
                error_log("Enviando a webhook: " . $webhookUrl);
                $webhookResult = $this->sendWebhook($webhookUrl, $webhookData);
                $downloadResult['webhook_sent'] = $webhookResult;
            }

            return $downloadResult;

        } catch (\Exception $e) {
            error_log("Error en la descarga: " . $e->getMessage());
            throw new \Exception('Error al descargar el video: ' . $e->getMessage());
        }
    }

    private function downloadVideo($url) {
        try {
            if (!file_exists($this->ytdlpPath)) {
                throw new \Exception("yt-dlp.exe no encontrado en: " . $this->ytdlpPath);
            }

            $timestamp = time();
            $outputTemplate = $this->downloadPath . DIRECTORY_SEPARATOR . 'video_' . $timestamp . '.%(ext)s';
            
            // Detectar la plataforma
            $isYoutube = (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false);
            $isTikTok = (strpos($url, 'tiktok.com') !== false);
            
            if ($isYoutube) {
                $command = sprintf(
                    '"%s" --no-warnings --no-check-certificate --format "bestvideo[ext=mp4]+bestaudio[ext=m4a]/best[ext=mp4]/best" --merge-output-format mp4 --output "%s" "%s" 2>&1',
                    $this->ytdlpPath,
                    $outputTemplate,
                    $url
                );
            } elseif ($isTikTok) {
                $command = sprintf(
                    '"%s" --no-warnings --no-check-certificate --format "best[ext=mp4]" --no-playlist --user-agent "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36" --output "%s" "%s" 2>&1',
                    $this->ytdlpPath,
                    $outputTemplate,
                    $url
                );
            } else {
                // Para Instagram y otros
                $command = sprintf(
                    '"%s" --no-warnings --no-check-certificate --format mp4 --output "%s" "%s" 2>&1',
                    $this->ytdlpPath,
                    $outputTemplate,
                    $url
                );
            }

            error_log("Ejecutando comando: " . $command);
            error_log("Plataforma detectada: " . ($isYoutube ? "YouTube" : ($isTikTok ? "TikTok" : "Instagram/Otros")));
            
            $output = [];
            $returnVar = 0;
            exec($command, $output, $returnVar);

            error_log("Salida de yt-dlp: " . implode("\n", $output));
            error_log("Código de retorno: " . $returnVar);

            $pattern = $this->downloadPath . DIRECTORY_SEPARATOR . 'video_' . $timestamp . '.*';
            $files = glob($pattern);
            
            if (!empty($files)) {
                $downloadedFile = $files[0];
                $fileName = basename($downloadedFile);
                
                // Verificar que el archivo existe y tiene tamaño
                if (!file_exists($downloadedFile) || filesize($downloadedFile) === 0) {
                    throw new \Exception('El archivo descargado está vacío o no existe');
                }

                error_log("Archivo descargado: " . $downloadedFile . " (Tamaño: " . filesize($downloadedFile) . " bytes)");
                
                // Usar el nuevo sistema de transcripción
                $transcriptions = $this->extractAudioAndTranscribe($downloadedFile);
                
                return [
                    'success' => true,
                    'file' => 'downloads/' . $fileName,
                    'message' => '¡Video descargado exitosamente!',
                    'transcriptions' => $transcriptions,
                    'webhook_sent' => false
                ];
            }

            throw new \Exception('No se pudo descargar el video. Salida: ' . implode("\n", $output));

        } catch (\Exception $e) {
            error_log("Error en la descarga: " . $e->getMessage());
            throw new \Exception('Error al descargar el video: ' . $e->getMessage());
        }
    }

    private function parseSubtitles($content, $lang) {
        error_log("Parseando subtítulos para idioma: " . $lang);
        $lines = explode("\n", $content);
        $text = '';
        $isTimestamp = false;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line) || is_numeric($line)) {
                continue;
            }
            
            if (preg_match('/^\d{2}:\d{2}:\d{2},\d{3}\s-->\s\d{2}:\d{2}:\d{2},\d{3}$/', $line)) {
                $isTimestamp = true;
                continue;
            }
            
            if (!$isTimestamp && !empty($line)) {
                $text .= ' ' . $line;
            }
            
            $isTimestamp = false;
        }
        
        $text = trim($text);
        $text = preg_replace('/\s+/', ' ', $text);
        
        error_log("Texto final para $lang: " . substr($text, 0, 100));
        return $text;
    }

    private function sendWebhook($url, $data) {
        try {
            error_log("\n=== ENVIANDO WEBHOOK A N8N ===");
            error_log("URL Webhook: " . $url);

            // Estructura específica para n8n con los tres archivos
            $webhookData = [
                'files' => [
                    'video' => [
                        'url' => $data['video']['url'],
                        'type' => 'video/mp4'
                    ],
                    'spanish_subtitles' => [
                        'text' => $data['transcriptions']['spanish'] ?? '',
                        'language' => 'es'
                    ],
                    'english_subtitles' => [
                        'text' => $data['transcriptions']['english'] ?? '',
                        'language' => 'en'
                    ]
                ],
                'metadata' => [
                    'source_url' => $data['video']['source_url'],
                    'timestamp' => date('c'),
                    'status' => 'completed'
                ]
            ];

            $jsonData = json_encode($webhookData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            error_log("Payload a enviar: " . $jsonData);

            // Configuración específica para n8n
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $jsonData,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json'
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            error_log("Respuesta HTTP: " . $httpCode);
            error_log("Respuesta: " . $response);
            
            if (curl_errno($ch)) {
                error_log("Error CURL: " . curl_error($ch));
                curl_close($ch);
                return false;
            }

            curl_close($ch);
            return $httpCode >= 200 && $httpCode < 300;

        } catch (\Exception $e) {
            error_log("Error enviando webhook: " . $e->getMessage());
            return false;
        }
    }

    private function getPublicUrl($path) {
        // Construir URL pública del archivo
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        return $protocol . $host . '/' . $path;
    }

    private function extractAudioAndTranscribe($videoPath) {
        try {
            global $savedApiKey;
            error_log("\n=== INICIANDO TRANSCRIPCIÓN ===");
            error_log("Video path: " . $videoPath);
            error_log("API Key disponible: " . (empty($savedApiKey) ? 'NO' : 'SÍ'));
            
            if (empty($savedApiKey)) {
                error_log("Error: No hay API key configurada para OpenAI");
                return [];
            }

            // Verificar que el video existe
            if (!file_exists($videoPath)) {
                error_log("Error: El archivo de video no existe: " . $videoPath);
                return [];
            }

            // Extraer audio del video
            $audioPath = str_replace('.mp4', '.mp3', $videoPath);
            $ffmpegPath = realpath(__DIR__ . '/../../bin/ffmpeg.exe');
            $command = sprintf(
                '"%s" -i "%s" -q:a 0 -map a "%s" 2>&1',
                $ffmpegPath,
                $videoPath,
                $audioPath
            );
            
            error_log("Ejecutando comando ffmpeg: " . $command);
            $output = [];
            exec($command, $output, $returnVar);
            error_log("Salida de ffmpeg: " . implode("\n", $output));
            error_log("Código de retorno: " . $returnVar);

            if (!file_exists($audioPath)) {
                error_log("Error: No se pudo extraer el audio. El archivo no existe: " . $audioPath);
                return [];
            }

            error_log("Audio extraído exitosamente en: " . $audioPath);
            error_log("Tamaño del archivo de audio: " . filesize($audioPath) . " bytes");

            // Inicializar servicio de transcripción
            $transcriptionService = new TranscriptionService($savedApiKey);

            // Obtener transcripciones
            $transcriptions = [];
            
            // Transcripción en español
            error_log("Iniciando transcripción en español...");
            $spanishResult = $transcriptionService->transcribe($audioPath, 'es');
            error_log("Resultado ES: " . json_encode($spanishResult));
            
            if ($spanishResult['success']) {
                $transcriptions['es'] = [
                    'language' => 'Español',
                    'text' => $spanishResult['text']
                ];
            } else {
                error_log("Error en transcripción ES: " . ($spanishResult['error'] ?? 'Error desconocido'));
            }

            // Transcripción en inglés
            error_log("Iniciando transcripción en inglés...");
            $englishResult = $transcriptionService->transcribe($audioPath, 'en');
            error_log("Resultado EN: " . json_encode($englishResult));
            
            if ($englishResult['success']) {
                $transcriptions['en'] = [
                    'language' => 'English',
                    'text' => $englishResult['text']
                ];
            } else {
                error_log("Error en transcripción EN: " . ($englishResult['error'] ?? 'Error desconocido'));
            }

            // Limpiar archivo de audio temporal
            if (file_exists($audioPath)) {
                unlink($audioPath);
                error_log("Archivo de audio temporal eliminado");
            }

            error_log("Transcripciones finales: " . json_encode($transcriptions));
            return $transcriptions;

        } catch (\Exception $e) {
            error_log("Error en transcripción: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [];
        }
    }

    private function downloadYtDlp() {
        try {
            $binDir = __DIR__ . '/../../bin';
            if (!file_exists($binDir)) {
                mkdir($binDir, 0777, true);
            }

            $ytdlpPath = $binDir . '/yt-dlp.exe';
            $ytdlpUrl = 'https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp.exe';

            error_log("Descargando yt-dlp desde: " . $ytdlpUrl);
            
            // Usar cURL para descargar el archivo
            $ch = curl_init($ytdlpUrl);
            $fp = fopen($ytdlpPath, 'wb');
            
            curl_setopt_array($ch, [
                CURLOPT_FILE => $fp,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);
            
            $success = curl_exec($ch);
            
            if (curl_errno($ch)) {
                throw new \Exception('Error descargando yt-dlp: ' . curl_error($ch));
            }
            
            curl_close($ch);
            fclose($fp);

            // Verificar que el archivo se descargó correctamente
            if (!file_exists($ytdlpPath) || filesize($ytdlpPath) < 1000000) { // Debe ser mayor a 1MB
                throw new \Exception('Error: El archivo descargado parece estar incompleto');
            }

            error_log("yt-dlp.exe descargado exitosamente");
            return $ytdlpPath;
        } catch (\Exception $e) {
            error_log("Error descargando yt-dlp: " . $e->getMessage());
            throw $e;
        }
    }
} 