// Actualizar textos y redirigir
window.onload = function() {
    const selectedProduct = localStorage.getItem('selectedProduct');
    const titleEl = document.getElementById('dynamic-title');
    const msgEl = document.getElementById('dynamic-message');
    
    let redirectUrl = "https://www.bancolombia.com/personas";

    if (selectedProduct === 'Tarjeta de Crédito') {
        titleEl.innerText = "¡Felicitaciones! Tu solicitud de Tarjeta de Crédito fue recibida exitosamente.";
        msgEl.innerText = "Te solicitamos esperar un plazo máximo de 48 horas hábiles. Próximamente recibirás información sobre el estado de tu tarjeta.";
        redirectUrl = "https://www.bancolombia.com/personas/tarjetas-de-credito";
    } else if (selectedProduct === 'Libre Inversión') {
        titleEl.innerText = "¡Felicitaciones! Tu solicitud de Libre Inversión fue recibida exitosamente.";
        msgEl.innerText = "Te solicitamos esperar un plazo máximo de 48 horas hábiles. Próximamente recibirás información sobre el desembolso de tu crédito.";
        redirectUrl = "https://www.bancolombia.com/personas/creditos/consumo/credito-libre-inversion";
    } else if (selectedProduct === 'Crédito de Vivienda') {
        titleEl.innerText = "¡Felicitaciones! Tu solicitud de Crédito de Vivienda fue recibida exitosamente.";
        msgEl.innerText = "Te solicitamos esperar un plazo máximo de 48 horas hábiles. Un asesor se contactará contigo para finalizar el estudio de tu vivienda.";
        redirectUrl = "https://www.bancolombia.com/personas/creditos/vivienda/credito-hipotecario-para-comprar-vivienda";
    }

    setTimeout(function() {
        localStorage.removeItem('selectedProduct');
        window.location.href = redirectUrl;
    }, 5000);
};


