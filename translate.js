const fs = require('fs');
let html = fs.readFileSync('ivr-designer.html', 'utf8');

// Header
html = html.replace('Asterisk Visual IVR', 'Asterisk IVR Visual');
html = html.replace('>SYNCED<', '>SINCRONIZADO<');
html = html.replace('>File<', '>Archivo<');
html = html.replace('>Edit<', '>Editar<');
html = html.replace('>View<', '>Ver<');
html = html.replace('>Analytics<', '>Analítica<');
html = html.replace('>Validate<', '>Validar<');
html = html.replace('>Save<', '>Guardar<');
html = html.replace('>Deploy to Asterisk<', '>Desplegar a Asterisk<');

// Node Library
html = html.replace('Node Library', 'Librería de Nodos');
html = html.replace('Drag components to canvas', 'Arrastra componentes al lienzo');
html = html.replace('Input &amp; Output', 'Entrada y Salida');
html = html.replace('>Playback<', '>Reproducción<');
html = html.replace('Get Digits', 'Obtener Dígitos');
html = html.replace('>Record<', '>Grabar<');
html = html.replace('Logic &amp; Routing', 'Lógica y Enrutamiento');
html = html.replace('Menu Choices', 'Opciones de Menú');
html = html.replace('Time Conditions', 'Condiciones de Tiempo');
html = html.replace('Transfer &amp; Queue', 'Transferencia y Cola');
html = html.replace('>Advanced<', '>Avanzado<');
html = html.replace('CUSTOM NODE', 'NODO PERSONALIZADO');

// Breadcrumbs
html = html.replace('>Projects<', '>Proyectos<');
html = html.replace('>Support Flow<', '>Flujo de Soporte<');
html = html.replace('IVR Main Canvas', 'Lienzo Principal IVR');

// Canvas Nodes
html = html.replace('>Trigger<', '>Gatillo<');
html = html.replace('Start IVR', 'Iniciar IVR');
html = html.replace('>Main Menu<', '>Menú Principal<');
html = html.replace('\"Press 1 for Support, 2 for Sales...\"', '\"Presione 1 para Soporte, 2 para Ventas...\"');
html = html.replace('>Support<', '>Soporte<');
html = html.replace('>Sales<', '>Ventas<');
html = html.replace('>Accounting<', '>Contabilidad<');
html = html.replace('Invalid / Timeout', 'Inválido / Tiempo Agotado');
html = html.replace('>Queue<', '>Cola<');
html = html.replace('Support Team', 'Equipo de Soporte');
html = html.replace('Sales Department', 'Departamento de Ventas');
html = html.replace('Accounting Desk', 'Escritorio de Contabilidad');

// Properties
html = html.replace('>Properties<', '>Propiedades<');
html = html.replace('Node ID', 'ID del Nodo');
html = html.replace('Node Name', 'Nombre del Nodo');
html = html.replace('Audio Prompt', 'Audio de Mensaje');
html = html.replace('Timeout (s)', 'Tiempo de Espera (s)');
html = html.replace('Max Retries', 'Reintentos Máx');
html = html.replace('Branch Configuration', 'Configuración de Ramas');
html = html.replace('3 Items', '3 Elementos');
html = html.replace('ADD OPTION', 'AÑADIR OPCIÓN');
html = html.replace('Delete Node', 'Eliminar Nodo');

// Footer
html = html.replace('Engine Online', 'Motor en Línea');
html = html.replace('Canvas 100%', 'Lienzo 100%');

fs.writeFileSync('ivr-designer.html', html);
console.log('Translated successfully');
