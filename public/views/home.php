<!DOCTYPE html>
<html>
<head>
    <title>Descargador de Videos - YouTube, TikTok, Instagram</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #357abd;
            --success-color: #28a745;
            --background-color: #f8f9fa;
        }

        body {
            background-color: var(--background-color);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .main-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 15px;
        }

        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 20px 20px 0 0 !important;
            padding: 2rem;
        }

        .platform-icons {
            margin: 20px 0;
            display: flex;
            justify-content: center;
            gap: 2rem;
        }

        .platform-icons i {
            font-size: 2.5em;
            transition: transform 0.3s ease;
        }

        .platform-icons i:hover {
            transform: scale(1.2);
        }

        .fa-youtube { color: #FF0000; }
        .fa-tiktok { color: #000000; }
        .fa-instagram { color: #E4405F; }

        .input-group {
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            border-radius: 15px;
            overflow: hidden;
        }

        .input-group-text {
            border: none;
            background-color: white;
            padding-left: 1.5rem;
        }

        .form-control {
            border: none;
            padding: 1rem;
            font-size: 1.1em;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: var(--primary-color);
        }

        .btn-download {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 12px 40px;
            font-size: 1.1em;
            border-radius: 30px;
            transition: all 0.3s ease;
        }

        .btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(74, 144, 226, 0.3);
        }

        .progress {
            height: 10px;
            margin: 20px 0;
            display: none;
            border-radius: 5px;
            overflow: hidden;
        }

        .progress-bar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }

        #result {
            margin-top: 20px;
        }

        .alert {
            border-radius: 15px;
            padding: 1rem;
        }

        .download-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border-radius: 30px;
            padding: 12px 30px;
            transition: all 0.3s ease;
        }

        .download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        .transcription-card {
            margin-top: 2rem;
            border-radius: 15px;
        }

        .transcription-text {
            max-height: 300px;
            overflow-y: auto;
            padding: 1rem;
            line-height: 1.6;
            font-size: 1.1em;
            background-color: #f8f9fa;
            border-radius: 10px;
        }

        /* Estilo para el scrollbar */
        .transcription-text::-webkit-scrollbar {
            width: 8px;
        }

        .transcription-text::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .transcription-text::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 4px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-container {
                margin: 1rem auto;
            }

            .card-header {
                padding: 1.5rem;
            }

            .platform-icons {
                gap: 1rem;
            }

            .platform-icons i {
                font-size: 2em;
            }

            .btn-download {
                width: 100%;
            }
        }

        .transcription-tabs {
            border-radius: 10px;
            overflow: hidden;
        }

        .nav-tabs {
            background: var(--primary-color);
            padding: 10px 10px 0 10px;
            border: none;
        }

        .nav-tabs .nav-link {
            color: rgba(255,255,255,0.8);
            border: none;
            border-radius: 8px 8px 0 0;
            padding: 10px 20px;
            margin-right: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-tabs .nav-link:hover {
            color: white;
        }

        .nav-tabs .nav-link.active {
            background: white;
            color: var(--primary-color);
            font-weight: 500;
        }

        .tab-content {
            background: white;
            padding: 20px;
            border-radius: 0 0 10px 10px;
        }

        .transcription-text {
            max-height: 400px;
            overflow-y: auto;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            line-height: 1.6;
            font-size: 1.1em;
        }
    </style>
</head>
<body>
    <div class="container main-container">
        <div class="card">
            <div class="card-header text-center">
                <h1 class="h3 mb-0">Descargador de Videos</h1>
                <p class="text-light mt-2 mb-0">YouTube • TikTok • Instagram</p>
                <div class="platform-icons">
                    <i class="fab fa-youtube"></i>
                    <i class="fab fa-tiktok"></i>
                    <i class="fab fa-instagram"></i>
                </div>
            </div>
            <div class="card-body p-4">
                <form id="downloadForm" method="POST">
                    <div class="mb-4">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-link"></i>
                            </span>
                            <input type="url" 
                                   class="form-control form-control-lg" 
                                   id="video_url" 
                                   name="video_url" 
                                   required 
                                   placeholder="Pega aquí el enlace del video..."
                                   autocomplete="off">
                        </div>
                    </div>
                    <div class="mb-4">
                        <div class="webhook-container">
                            <!-- URL Guardada -->
                            <div id="savedWebhookContainer" class="alert alert-info mb-3" style="display: none;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-link me-2"></i>
                                        <strong>Webhook actual:</strong> 
                                        <span id="savedWebhookUrl"></span>
                                    </div>
                                    <button type="button" 
                                            class="btn btn-sm btn-warning" 
                                            id="changeWebhook">
                                        <i class="fas fa-edit me-2"></i>
                                        Probar
                                    </button>
                                </div>
                            </div>

                            <!-- Formulario para nuevo webhook -->
                            <div id="webhookInputContainer">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-webhook"></i>
                                    </span>
                                    <input type="url" 
                                           class="form-control form-control-lg" 
                                           id="webhook_url" 
                                           name="webhook_url" 
                                           required
                                           placeholder="URL del Webhook"
                                           autocomplete="off">
                                    <button type="button" 
                                            class="btn btn-success" 
                                            id="saveWebhook">
                                        <i class="fas fa-save me-2"></i>
                                        Guardar Webhook
                                    </button>
                                    <button type="button" 
                                            class="btn btn-info" 
                                            id="testWebhook" 
                                            disabled>
                                        <i class="fas fa-vial me-2"></i>
                                        Probar Webhook
                                    </button>
                                </div>
                                <div class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i> 
                                    URL donde se enviarán el video y las transcripciones
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <div class="openai-container">
                            <!-- API Key Guardada -->
                            <div id="savedApiKeyContainer" class="alert alert-info mb-3" style="display: none;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-key me-2"></i>
                                        <strong>API Key de OpenAI:</strong> 
                                        <span id="savedApiKey">••••••••</span>
                                    </div>
                                    <button type="button" 
                                            class="btn btn-sm btn-warning" 
                                            id="changeApiKey">
                                        <i class="fas fa-edit me-2"></i>
                                        Probar
                                    </button>
                                </div>
                            </div>

                            <!-- Formulario para nueva API Key -->
                            <div id="apiKeyInputContainer">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-key"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control form-control-lg" 
                                           id="openai_key" 
                                           name="openai_key" 
                                           required
                                           placeholder="API Key de OpenAI"
                                           autocomplete="off">
                                    <button type="button" 
                                            class="btn btn-success" 
                                            id="saveApiKey">
                                        <i class="fas fa-save me-2"></i>
                                        Guardar API Key
                                    </button>
                                    <button type="button" 
                                            class="btn btn-info" 
                                            id="testApiKey" 
                                            disabled>
                                        <i class="fas fa-vial me-2"></i>
                                        Probar API Key
                                    </button>
                                </div>
                                <div class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i> 
                                    API Key necesaria para las transcripciones automáticas
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary btn-download" disabled>
                            <i class="fas fa-download me-2"></i>Descargar Video
                        </button>
                    </div>
                </form>
                
                <div class="progress">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" 
                         style="width: 100%"></div>
                </div>

                <div id="result"></div>
            </div>
        </div>
    </div>

    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
        <div id="webhookToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>¡Éxito!</strong> Webhook guardado correctamente
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
        <div id="apiKeyToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>¡Éxito!</strong> API Key guardada correctamente
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html> 