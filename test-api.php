<?php require_once 'security.php'; ?>
<!DOCTYPE html>
<html>
<head>
    <title>Test API</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        pre { background: #f4f4f4; padding: 10px; }
    </style>
</head>
<body>
    <h1>🧪 Probar API de Telegram</h1>
    <button onclick="testAPI()">🚀 Probar API</button>
    <div id="result"></div>

    <script>
    async function testAPI() {
        const div = document.getElementById('result');
        div.innerHTML = '⏳ Probando...';

        const isFileProtocol = window.location.protocol === 'file:';
        const currentUrl = window.location.href;
        const currentHost = window.location.host || '(sin servidor)';

        if (isFileProtocol) {
            div.innerHTML = `
                <div class="error">❌ Error de conexión</div>
                <p>Estás abriendo <strong>test-api.php</strong> como archivo local con <code>file://</code>.</p>
                <p><strong>api.php no se puede ejecutar así.</strong> Necesitas abrir el proyecto desde un servidor con PHP.</p>
                <p><strong>Ejemplos válidos:</strong></p>
                <ul>
                    <li><code>http://localhost/tu-proyecto/test-api.php</code></li>
                    <li><code>http://127.0.0.1:8000/test-api.php</code></li>
                </ul>
                <p><strong>URL actual:</strong> ${currentUrl}</p>
            `;
            return;
        }
        
        try {
            // 1. Probar health check
            const healthResp = await fetch('api.php?action=health');
            if (!healthResp.ok) {
                throw new Error(`Health check HTTP ${healthResp.status}`);
            }
            const healthData = await healthResp.json();
            console.log('Health:', healthData);
            
            // 2. Probar send_message
            const response = await fetch('api.php?action=send_message', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    text: '✅ <b>Test desde navegador</b>\n\n📱 Conexión exitosa!'
                })
            });
            
            const data = await response.json();
            console.log('Response:', data);
            
            if (data.ok) {
                div.innerHTML = `
                    <div class="success">✅ API funcionando correctamente</div>
                    <pre>Health: ${JSON.stringify(healthData, null, 2)}</pre>
                    <pre>Mensaje enviado: ${JSON.stringify(data, null, 2)}</pre>
                `;
            } else {
                div.innerHTML = `
                    <div class="error">❌ Error en la API</div>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                `;
            }
        } catch (error) {
            div.innerHTML = `
                <div class="error">❌ Error de conexión</div>
                <p>${error.message}</p>
                <p><strong>Host actual:</strong> ${currentHost}</p>
                <p><strong>URL actual:</strong> ${currentUrl}</p>
                <p><strong>Posibles causas:</strong></p>
                <ul>
                    <li>El archivo <code>api.php</code> no existe en la raíz</li>
                    <li>El proyecto está abierto sin servidor PHP</li>
                    <li>PHP no está instalado o no está corriendo</li>
                    <li>El puerto o la ruta son incorrectos</li>
                    <li>El servidor está devolviendo HTML o error en vez de JSON</li>
                </ul>
            `;
            console.error('Error:', error);
        }
    }
    </script>
</body>
</html>
