/* Swagger UI 5.17.14 does not provide built-in translations. Limit replacements
   to interface labels; never translate examples, schemas, requests or responses. */
(() => {
    const root = document.getElementById('swagger-ui');
    if (!root) return;

    const translations = {
        'Servers': 'Servidores', 'default': 'Operaciones', 'Schemas': 'Esquemas',
        'Parameters': 'Parámetros', 'No parameters': 'Sin parámetros',
        'Try it out': 'Probar', 'Cancel': 'Cancelar', 'Execute': 'Ejecutar',
        'Clear': 'Limpiar', 'Request body': 'Cuerpo de la solicitud',
        'required': 'obligatorio', 'Responses': 'Respuestas', 'Response': 'Respuesta',
        'Code': 'Código', 'Description': 'Descripción', 'Links': 'Enlaces',
        'No links': 'Sin enlaces', 'Media type': 'Tipo de contenido',
        'Controls Accept header.': 'Controla el encabezado Accept.',
        'Example Value': 'Ejemplo', 'Schema': 'Esquema', 'Model': 'Modelo',
        'Models': 'Modelos', 'Request URL': 'URL de la solicitud',
        'Server response': 'Respuesta del servidor', 'Response body': 'Cuerpo de la respuesta',
        'Response headers': 'Encabezados de la respuesta', 'Undocumented': 'No documentado',
        'Curl': 'cURL', 'Download': 'Descargar', 'Copy to clipboard': 'Copiar al portapapeles',
        'Expand operation': 'Expandir operación', 'Collapse operation': 'Contraer operación',
        'Request content type': 'Tipo de contenido de la solicitud', 'Media Type': 'Tipo de contenido',
        'Controls': 'Controla el encabezado', 'header.': '.', 'LOADING': 'CARGANDO',
        'Select a definition': 'Seleccionar una definición', 'Loading...': 'Cargando...',
        'Filter by tag': 'Filtrar por etiqueta', 'Available values': 'Valores disponibles',
        'Default value': 'Valor predeterminado', 'Send empty value': 'Enviar valor vacío'
    };

    function translate(node) {
        if (node.nodeType === Node.TEXT_NODE) {
            const parent = node.parentElement;
            if (!parent || parent.closest('pre, code, textarea, input, .model, .microlight, .response-col_description__inner')) return;
            const text = node.nodeValue.trim();
            if (Object.hasOwn(translations, text)) {
                node.nodeValue = node.nodeValue.replace(text, translations[text]);
            }
            return;
        }
        if (node.nodeType !== Node.ELEMENT_NODE) return;
        for (const attr of ['aria-label', 'title', 'placeholder']) {
            const value = node.getAttribute(attr);
            if (value && Object.hasOwn(translations, value)) node.setAttribute(attr, translations[value]);
        }
        for (const child of node.childNodes) translate(child);
    }

    const observer = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            if (mutation.type === 'characterData') translate(mutation.target);
            else if (mutation.type === 'attributes') translate(mutation.target);
            else for (const node of mutation.addedNodes) translate(node);
        }
    });
    observer.observe(root, { childList: true, subtree: true, characterData: true, attributes: true, attributeFilter: ['aria-label', 'title', 'placeholder'] });
    translate(root);
})();
