// Gestión del webhook
let savedWebhookUrl = '';
let savedApiKey = '';

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado, inicializando handlers...');
    
    const saveButton = document.getElementById('saveWebhook');
    const testButton = document.getElementById('testWebhook');
    const changeButton = document.getElementById('changeWebhook');
    const downloadForm = document.getElementById('downloadForm');
    const saveApiKeyButton = document.getElementById('saveApiKey');
    const testApiKeyButton = document.getElementById('testApiKey');
    const changeApiKeyButton = document.getElementById('changeApiKey');
    
    console.log('Elementos encontrados:', {
        save: !!saveButton,
        test: !!testButton,
        change: !!changeButton,
        form: !!downloadForm,
        saveApiKey: !!saveApiKeyButton,
        testApiKey: !!testApiKeyButton,
        changeApiKey: !!changeApiKeyButton
    });

    // Guardar webhook
    saveButton?.addEventListener('click', function() {
        console.log('Guardando webhook...');
        const webhookUrl = document.getElementById('webhook_url').value;
        if (!webhookUrl) {
            alert('Por favor, ingresa la URL del webhook');
            return;
        }

        savedWebhookUrl = webhookUrl;
        updateWebhookUI();

        // Mostrar notificación toast
        const toastEl = document.getElementById('webhookToast');
        const toast = new bootstrap.Toast(toastEl, {
            animation: true,
            autohide: true,
            delay: 2000
        });
        toast.show();
    });

    // Probar webhook
    testButton?.addEventListener('click', async function() {
        console.log('Probando webhook:', savedWebhookUrl);
        if (!savedWebhookUrl) {
            alert('No hay webhook guardado');
            return;
        }

        try {
            const response = await fetch('test-webhook', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ 
                    webhook_url: savedWebhookUrl 
                })
            });

            const data = await response.json();
            console.log('Respuesta webhook:', data);
            
            if (data.success) {
                alert(`Conexión exitosa con el webhook (HTTP ${data.http_code})`);
            } else {
                alert('Error al conectar con el webhook: ' + (data.error || 'Error desconocido'));
            }
        } catch (error) {
            console.error('Error webhook:', error);
            alert('Error al probar el webhook: ' + error.message);
        }
    });

    // Cambiar webhook
    changeButton?.addEventListener('click', function() {
        console.log('Cambiando webhook...');
        const inputContainer = document.getElementById('webhookInputContainer');
        const webhookInput = document.getElementById('webhook_url');
        
        inputContainer.style.display = 'block';
        webhookInput.value = savedWebhookUrl;
    });

    // Manejar la descarga
    downloadForm?.addEventListener('submit', async function(e) {
        e.preventDefault();
        console.log('Iniciando descarga con webhook:', savedWebhookUrl);
        
        const videoUrl = document.getElementById('video_url').value;
        const resultDiv = document.getElementById('result');
        const progressBar = document.querySelector('.progress');
        
        try {
            // Mostrar barra de progreso
            progressBar.style.display = 'flex';
            resultDiv.innerHTML = '';
            
            console.log('Enviando petición de descarga:', {
                video_url: videoUrl,
                webhook_url: savedWebhookUrl,
                api_key: savedApiKey
            });

            const response = await fetch('download', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ 
                    video_url: videoUrl,
                    webhook_url: savedWebhookUrl,
                    api_key: savedApiKey
                })
            });
            
            const text = await response.text();
            console.log('Respuesta del servidor:', text);
            
            progressBar.style.display = 'none';
            
            try {
                const data = JSON.parse(text);
                if (data.error) {
                    resultDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>${data.error}
                        </div>`;
                } else {
                    // Mostrar resultado de la descarga y estado del webhook
                    let webhookStatus = '';
                    if (savedWebhookUrl) {
                        webhookStatus = data.webhook_sent 
                            ? `<div class="alert alert-info mt-2">
                                 <i class="fas fa-check-circle me-2"></i>
                                 Datos enviados exitosamente al webhook
                               </div>`
                            : `<div class="alert alert-warning mt-2">
                                 <i class="fas fa-exclamation-triangle me-2"></i>
                                 Error al enviar datos al webhook
                               </div>`;
                    }

                    resultDiv.innerHTML = `
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>${data.message}<br>
                            <a href="${data.file}" class="btn btn-success mt-3 download-btn" download>
                                <i class="fas fa-file-download"></i>
                                Descargar archivo
                            </a>
                            ${webhookStatus}
                        </div>
                        <div class="card mt-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-closed-captioning me-2"></i>
                                    Transcripciones Disponibles
                                </h5>
                            </div>
                            <div class="card-body">
                                <ul class="nav nav-pills mb-3" id="transcriptionTabs" role="tablist">
                                    ${Object.entries(data.transcriptions).map(([lang, trans], index) => `
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link ${lang === 'es' ? 'active' : ''}"
                                                    id="tab-${lang}"
                                                    data-bs-toggle="pill"
                                                    data-bs-target="#content-${lang}"
                                                    type="button"
                                                    role="tab"
                                                    aria-controls="content-${lang}"
                                                    aria-selected="${lang === 'es' ? 'true' : 'false'}">
                                                <i class="fas fa-language me-2"></i>
                                                ${trans.language}
                                            </button>
                                        </li>
                                    `).join('')}
                                </ul>
                                <div class="tab-content mt-3" id="transcriptionContent">
                                    ${Object.entries(data.transcriptions).map(([lang, trans], index) => `
                                        <div class="tab-pane fade ${lang === 'es' ? 'show active' : ''}"
                                             id="content-${lang}"
                                             role="tabpanel"
                                             aria-labelledby="tab-${lang}">
                                            <div class="transcription-text" id="text-${lang}">
                                                ${trans.text}
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        </div>`;

                    if (data.webhook_sent === false) {
                        console.error('Error enviando webhook:', {
                            videoUrl: data.file,
                            webhookUrl: savedWebhookUrl
                        });
                    }
                }
            } catch (jsonError) {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error al procesar la respuesta del servidor
                    </div>`;
                console.error('Error al parsear JSON:', text);
            }
        } catch (error) {
            console.error('Error en la descarga:', error);
            progressBar.style.display = 'none';
            resultDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error en la petición: ${error.message}
                </div>`;
        }
    });

    // Guardar API Key
    saveApiKeyButton?.addEventListener('click', function() {
        console.log('Guardando API Key...');
        const apiKey = document.getElementById('openai_key').value;
        if (!apiKey) {
            alert('Por favor, ingresa la API Key de OpenAI');
            return;
        }

        savedApiKey = apiKey;
        updateApiKeyUI();

        // Mostrar notificación toast
        const toastEl = document.getElementById('apiKeyToast');
        const toast = new bootstrap.Toast(toastEl, {
            animation: true,
            autohide: true,
            delay: 2000
        });
        toast.show();
    });

    // Probar API Key
    testApiKeyButton?.addEventListener('click', async function() {
        console.log('Probando API Key...');
        if (!savedApiKey) {
            alert('No hay API Key guardada');
            return;
        }

        try {
            const response = await fetch('test-openai', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ 
                    api_key: savedApiKey 
                })
            });

            const data = await response.json();
            
            if (data.success) {
                alert('API Key válida - Conexión exitosa con OpenAI');
            } else {
                alert('Error al verificar API Key: ' + (data.error || 'Error desconocido'));
            }
        } catch (error) {
            console.error('Error API Key:', error);
            alert('Error al probar API Key: ' + error.message);
        }
    });

    // Cambiar API Key
    changeApiKeyButton?.addEventListener('click', function() {
        console.log('Cambiando API Key...');
        const inputContainer = document.getElementById('apiKeyInputContainer');
        const apiKeyInput = document.getElementById('openai_key');
        
        inputContainer.style.display = 'block';
        apiKeyInput.value = savedApiKey;
    });

    // Inicializar UI
    updateWebhookUI();
    updateApiKeyUI();
});

function updateWebhookUI() {
    const savedContainer = document.getElementById('savedWebhookContainer');
    const inputContainer = document.getElementById('webhookInputContainer');
    const testButton = document.getElementById('testWebhook');
    const urlDisplay = document.getElementById('savedWebhookUrl');
    const webhookInput = document.getElementById('webhook_url');
    const downloadButton = document.querySelector('.btn-download');

    if (savedWebhookUrl) {
        savedContainer.style.display = 'block';
        urlDisplay.textContent = savedWebhookUrl;
        webhookInput.value = '';
        inputContainer.style.display = 'none';
        testButton.disabled = false;
        downloadButton.disabled = false;
        downloadButton.title = '';
    } else {
        savedContainer.style.display = 'none';
        inputContainer.style.display = 'block';
        testButton.disabled = true;
        downloadButton.disabled = true;
        downloadButton.title = 'Primero debes guardar un webhook';
    }
    updateDownloadButton();
}

// Función para actualizar UI de API Key
function updateApiKeyUI() {
    const savedContainer = document.getElementById('savedApiKeyContainer');
    const inputContainer = document.getElementById('apiKeyInputContainer');
    const testButton = document.getElementById('testApiKey');
    const apiKeyDisplay = document.getElementById('savedApiKey');
    const apiKeyInput = document.getElementById('openai_key');

    if (savedApiKey) {
        savedContainer.style.display = 'block';
        apiKeyDisplay.textContent = '••••••••' + savedApiKey.slice(-4);
        apiKeyInput.value = '';
        inputContainer.style.display = 'none';
        testButton.disabled = false;
    } else {
        savedContainer.style.display = 'none';
        inputContainer.style.display = 'block';
        testButton.disabled = true;
    }
    updateDownloadButton();
}

function updateDownloadButton() {
    const downloadButton = document.querySelector('.btn-download');
    
    if (savedWebhookUrl && savedApiKey) {
        downloadButton.disabled = false;
        downloadButton.title = '';
    } else {
        downloadButton.disabled = true;
        downloadButton.title = 'Primero debes configurar el webhook y la API Key de OpenAI';
    }
}

// Agregar mensaje informativo en el HTML cuando el botón está deshabilitado
document.querySelector('.btn-download').title = 'Primero debes guardar un webhook'; 