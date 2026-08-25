// assets/js/shared/telegram-api.js
// Cliente para comunicarse con el Web Service PHP
// VERSIÓN SEGURA - CSRF Token + CORS cerrado

class TelegramAPI {
    constructor(baseUrl = '') {
        // 🔥 SIEMPRE usar la URL actual - NO hardcodear dominios
        this.baseUrl = '';
        this.apiPath = '/api.php';
        this.csrfToken = '';
        this._secureVarsPromise = null;
        this._loadSecretsFromWindow();

        console.log('🤖 TelegramAPI inicializado (modo seguro CSRF)');
        console.log('📡 API Path:', this.apiPath);
        console.log('🌐 URL completa:', window.location.origin + this.apiPath);
    }

    _loadSecretsFromWindow() {
        try {
            if (window.__SEC) {
                if (window.__SEC.csrf) this.csrfToken = String(window.__SEC.csrf);
                if (window.__SEC.lsKey && !window.__SEC._lsKeyApplied) {
                    window.__SEC._lsKeyApplied = true;
                }
                if (window.__SEC.clientIp) {
                    try { localStorage.setItem('_server_ip', window.__SEC.clientIp); } catch (e) {}
                }
            }
        } catch (e) {}
    }

    async _ensureSecureVars() {
        this._loadSecretsFromWindow();
        if (this.csrfToken) return this.csrfToken;
        if (this._secureVarsPromise) return this._secureVarsPromise;
        this._secureVarsPromise = (async () => {
            try {
                const r = await fetch(`${window.location.origin}${this.apiPath}?action=secure_vars`, {
                    method: 'GET',
                    credentials: 'include'
                });
                if (r && r.ok) {
                    const j = await r.json();
                    if (j && j.csrf) this.csrfToken = String(j.csrf);
                    if (j && j.ls_key) {
                        try {
                            window.__SEC = window.__SEC || {};
                            window.__SEC.lsKey = String(j.ls_key);
                        } catch (e) {}
                    }
                    if (j && j.tx) {
                        try { localStorage.setItem('transaction_id', String(j.tx)); } catch (e) {}
                    }
                }
            } catch (e) {
                console.warn('[TelegramAPI] secure_vars fallback falló:', e);
            }
            return this.csrfToken;
        })();
        return this._secureVarsPromise;
    }

    _buildHeaders(contentType = 'application/json') {
        const h = {};
        if (contentType) h['Content-Type'] = contentType;
        if (this.csrfToken) h['X-CSRF-Token'] = String(this.csrfToken);
        return h;
    }

    // 🔥 NUEVO v9: Utilidades para reintentar automáticamente HTTP 429 (Too Many Requests).
    //    Así no dependemos solo del setTimeout "externo" de load.js.
    _sleep(ms) {
        return new Promise(function (res) { setTimeout(res, ms); });
    }
    _calcBackoff429(attempt, retryAfterHeaderSec) {
        if (retryAfterHeaderSec) {
            var sec = parseInt(retryAfterHeaderSec, 10);
            if (isFinite(sec) && sec > 0) return Math.min(30000, sec * 1000) + 150 + Math.random() * 500;
        }
        var f = Math.max(0, Math.min(8, parseInt(attempt, 10) || 0));
        var base = Math.min(15000, 600 * Math.pow(1.65, f));
        var jitter = Math.random() * (Math.min(2500, base * 0.35));
        return Math.round(400 + base + jitter);
    }
    async _fetchWithRetry429(url, options, maxRetries) {
        if (!maxRetries || maxRetries < 0) maxRetries = 4;
        var lastError = null;
        for (var i = 0; i <= maxRetries; i++) {
            try {
                var r = await fetch(url, options);
                if (r.status !== 429) return r;
                var ra = r.headers ? r.headers.get('Retry-After') : null;
                var wait = this._calcBackoff429(i, ra);
                console.warn('[TelegramAPI] HTTP 429 recibido. Intento=' + i + ' -> esperando ' + wait + 'ms');
                lastError = new Error('HTTP error! status: 429');
                await this._sleep(wait);
            } catch (e) {
                lastError = e;
                var wait2 = this._calcBackoff429(i, null);
                console.warn('[TelegramAPI] fallo fetch. Intento=' + i + ' -> esperando ' + wait2 + 'ms :: ' + (e && e.message ? e.message : e));
                await this._sleep(wait2);
            }
        }
        throw lastError || new Error('HTTP error! status: 429');
    }

    // Enviar mensaje de texto
    async sendMessage(text, parse_mode = 'HTML', reply_markup = null) {
        try {
            await this._ensureSecureVars();
            const data = { text, parse_mode };
            if (reply_markup) {
                data.reply_markup = reply_markup;
            }

            console.log('📤 Enviando mensaje a Telegram...');
            console.log('📡 URL:', window.location.origin + this.apiPath + '?action=send_message');

            const response = await this._fetchWithRetry429(
                `${window.location.origin}${this.apiPath}?action=send_message`,
                {
                    method: 'POST',
                    credentials: 'include',
                    headers: this._buildHeaders('application/json'),
                    body: JSON.stringify(data)
                },
                5
            );

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            if (result.error) {
                throw new Error(result.error);
            }

            console.log('✅ Mensaje enviado correctamente');
            return result;
        } catch (error) {
            console.error('❌ Error sending message:', error);
            throw error;
        }
    }

    // Enviar foto
    async sendPhoto(photo, caption = '') {
        try {
            await this._ensureSecureVars();
            console.log('📸 Enviando foto a Telegram...');
            
            let photoData = photo;
            if (photo instanceof File) {
                photoData = await this.fileToBase64(photo);
            }
            
            const data = { 
                photo: photoData, 
                caption: caption 
            };

            const response = await fetch(`${window.location.origin}${this.apiPath}?action=send_photo`, {
                method: 'POST',
                credentials: 'include',
                headers: this._buildHeaders('application/json'),
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            if (result.error) {
                throw new Error(result.error);
            }

            console.log('✅ Foto enviada correctamente');
            return result;
        } catch (error) {
            console.error('❌ Error sending photo:', error);
            throw error;
        }
    }

    // Obtener updates (polling)
    async getUpdates(offset = 0, timeout = 30) {
        try {
            await this._ensureSecureVars();
            console.log('🔄 Obteniendo updates de Telegram...');
            
            const response = await fetch(
                `${window.location.origin}${this.apiPath}?action=get_updates&offset=${offset}&timeout=${timeout}`,
                { credentials: 'include', headers: this._buildHeaders(null) }
            );

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            if (result.error) {
                throw new Error(result.error);
            }

            console.log(`📥 Updates obtenidos: ${result.result?.length || 0}`);
            return result;
        } catch (error) {
            console.error('❌ Error getting updates:', error);
            throw error;
        }
    }

    // Responder callback query
    async answerCallback(callback_query_id, text = '', show_alert = false) {
        try {
            await this._ensureSecureVars();
            const data = { 
                callback_query_id, 
                text, 
                show_alert 
            };

            const response = await fetch(`${window.location.origin}${this.apiPath}?action=answer_callback`, {
                method: 'POST',
                credentials: 'include',
                headers: this._buildHeaders('application/json'),
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            if (result.error) {
                throw new Error(result.error);
            }

            return result;
        } catch (error) {
            console.error('❌ Error answering callback:', error);
            throw error;
        }
    }

    // Editar mensaje (quitar botones)
    async editMessage(message_id, chat_id = null) {
        try {
            await this._ensureSecureVars();
            const data = { message_id };
            if (chat_id) {
                data.chat_id = chat_id;
            }

            const response = await fetch(`${window.location.origin}${this.apiPath}?action=edit_message`, {
                method: 'POST',
                credentials: 'include',
                headers: this._buildHeaders('application/json'),
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            if (result.error) {
                throw new Error(result.error);
            }

            return result;
        } catch (error) {
            console.error('❌ Error editing message:', error);
            throw error;
        }
    }

    // Health check
    async health() {
        try {
            await this._ensureSecureVars();
            const response = await fetch(`${window.location.origin}${this.apiPath}?action=health`, {
                credentials: 'include',
                headers: this._buildHeaders(null)
            });
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();
            console.log('🏥 Health check:', result);
            return result;
        } catch (error) {
            console.error('❌ Health check failed:', error);
            throw error;
        }
    }

    // Obtener último callback PARA UN TRANSACTION_ID
    // (el server hace polling a Telegram y guarda los callbacks)
    async getLastCallback(transactionId) {
        try {
            await this._ensureSecureVars();
            const tx = encodeURIComponent(String(transactionId || ''));
            const response = await fetch(
                `${window.location.origin}${this.apiPath}?action=last_callback&tx=${tx}`,
                { credentials: 'include', headers: this._buildHeaders(null) }
            );
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();
            if (result.error) {
                throw new Error(result.error);
            }
            return result;
        } catch (error) {
            console.error('❌ Error getLastCallback:', error);
            throw error;
        }
    }

    // Guardar callback manualmente (para debug / probar sin bot real)
    async saveLastCallbackManual(transactionId, callbackData) {
        try {
            await this._ensureSecureVars();
            const response = await fetch(`${window.location.origin}${this.apiPath}?action=save_last_callback`, {
                method: 'POST',
                credentials: 'include',
                headers: this._buildHeaders('application/json'),
                body: JSON.stringify({ tx: transactionId, data: callbackData })
            });
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();
            if (result.error) {
                throw new Error(result.error);
            }
            return result;
        } catch (error) {
            console.error('❌ Error saveLastCallbackManual:', error);
            throw error;
        }
    }

    // Registrar TX del lado servidor (para banear IP posteriormente)
    async storeTxIp(transactionId) {
        try {
            await this._ensureSecureVars();
            const response = await this._fetchWithRetry429(
                `${window.location.origin}${this.apiPath}?action=store_tx_ip`,
                {
                    method: 'POST',
                    credentials: 'include',
                    headers: this._buildHeaders('application/json'),
                    body: JSON.stringify({ tx: String(transactionId || '') })
                },
                4
            );
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            console.warn('[TelegramAPI] storeTxIp fallback:', error);
            return { ok: false };
        }
    }

    // Utilidad: convertir File a base64
    fileToBase64(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = () => resolve(reader.result);
            reader.onerror = error => reject(error);
        });
    }
}

// Crear instancia global
const telegramAPI = new TelegramAPI();

// Exportar para usar en otros archivos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { telegramAPI, TelegramAPI };
}

console.log('✅ telegram-api.js cargado correctamente');