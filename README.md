# Pixum- WordPress 
Sistema de Pedidos Fotográficos — Documentación Backend y Fronted
Autor: Carlos Landa , Ayrton Yactayo
Versión: 20.0 — Última actualización: Diciembre 2025


# Características principales
✅  "Pedidos Fotográficos" con ciclo completo (creación desde frontend, metadatos, estados de pago y producción).
✅  Integración Mercado Pago: creación de preferencias con SDK, webhook receptor y validación de pagos.
✅ Soporte para Stripe: estructura y handler de webhook preparado.
✅ Edición de imágenes con Google Gemini: endpoint seguro que procesa imágenes en el servidor (validaciones, timeout).
✅ REST API estable: GET /wp-json/ai/v1/pedidos (autenticación por X-API-KEY).
✅ Webhooks salientes: notificación automática a Django cuando un pedido pasa a pagado.
✅ Rol personalizado: minilab_operator con permisos limitados.
✅ Gestión de archivos por pedido: miniaturas, descargas individuales o ZIP, checkboxes operativos.
✅ Cálculo automático de DPI y clasificación (ok, warning, low).

# 💳 Integración con Pasarelas de Pago (mercado pago)

- Creación de Preferencias**: Generación automática de links de pago mediante SDK oficial de Mercado Pago.
- Webhook Receptor**: Endpoint AJAX que procesa notificaciones de pago en tiempo real.
- Validación de Pagos**: Verificación automática del estado de pago y actualización del pedido.
- Metadata**: Vinculación bidireccional entre preferencias de MP y pedidos de WordPress mediante metadata.

# Stripe (Preparado)
- Estructura Similar: Mismo flujo que Mercado Pago para consistencia.
- Webhook Handler: Endpoint dedicado para procesar eventos de Stripe.
- Configuración Flexible: Claves públicas, secretas y webhook secrets configurables desde el panel de administración.
  
# 🤖 Procesamiento de Imágenes con IA (Google Gemini)

Integración con Google Gemini API  para edición de imágenes mediante lenguaje natural:

- Endpoint Seguro: AJAX handler que protege la API key en el servidor (nunca expuesta al frontend).
- Validación Robusta: 
  - Validación de tamaño de imagen (máximo 4MB en base64).
  - Validación de longitud de instrucción (3-500 caracteres).
  - Sanitización de inputs para prevenir inyección.
- Manejo de Errores: Gestión completa de errores de red, HTTP, bloqueos de seguridad, y respuestas inválidas.
- Modelo Estable: Uso de `gemini-1.5-flash` para producción (rápido y confiable).
- Timeout Configurado: 60 segundos de timeout para evitar bloqueos.
  
# 📊Panel de Administración Completo
Pantalla principal "Sistema de Pedidos Fotográficos"** con 4 secciones:

# Tab 1: Listado de Pedidos
- Redirección al CPT estándar de WordPress con columnas personalizadas.
- Visualización de información de pago, total, y estado operativo.
- Filtros y búsqueda nativos de WordPress.

# Tab 2: Claves de Pago
- **Stripe**: Configuración de Public Key, Secret Key, y Webhook Secret.
- **Mercado Pago**: Configuración de Public Key, Access Token, y Webhook Secret.
- Almacenamiento seguro en opciones de WordPress (`update_option`).

# Tab 3: Gestionar Catálogo
- CRUD completo de productos fotográficos.
- Campos: ID fijo (ej: `10x15`, `A4`), Título, Precio (S/), Descripción, URL de imagen, Ratio (ancho/alto).
- Almacenamiento en opciones de WordPress como array serializado.

# Tab 4: Integración Externa
- **API Key**: Token para autenticar peticiones REST desde Django.
- **URL Webhook Django**: Endpoint donde se enviarán notificaciones automáticas.
- **Shared Secret**: Secret compartido para validar integridad de webhooks.
- **Checkbox de Habilitación**: Control para activar/desactivar webhooks salientes.

# REST API — GET /wp-json/ai/v1/pedidos
Autenticación: header X-API-KEY: <token>
Parámetros opcionales:
estado (pago): pagado | pendiente_pago | pagado_parcial
desde (ISO8601)

# 📡 Sistema de Webhooks Salientes
Notificación Automática a Django** cuando un pedido pasa a estado `pagado`:

- Trigger: Hook de WordPress `ai_pedido_pagado` se dispara automáticamente.
- Headers de Seguridad**:
  - `Content-Type: application/json`
  - `X-Integration-Source: wp_photo_plugin`
  - `X-Integration-Secret: <SECRET_COMPARTIDO>`
- Payload: Mismo formato JSON que el endpoint REST GET.
- Configuración: URL y secret configurables desde el panel de administración.
- Logging: Registro de intentos de envío en `/uploads/ai_integracion/integration.log`.

# 👥 Sistema de Roles y Permisos

Rol Personalizado "Operador de Minilab"** (`minilab_operator`):

- Permisos Limitados: Solo puede ver y editar "Pedidos Fotográficos".
- Sin Acceso a Configuración: No puede acceder a claves de pago, catálogo, ni configuración de integración.
- Operaciones Permitidas:
  - Ver lista de pedidos.
  - Editar estados operativos (archivos_listos, enviado_minilab, impreso, entregado).
  - Descargar archivos individuales o ZIP.
  - Marcar archivos como revisados o enviados al minilab.
  - Agregar notas internas del operador.

 # 📐 Cálculo Automático de DPI

Sistema de validación de calidad de imagen**:

- Cálculo Automático: Al guardar un pedido, se calcula el DPI de cada imagen basado en:
  - Dimensiones de la imagen (ancho x alto en píxeles).
  - Tamaño de impresión seleccionado (del catálogo de productos).
- Clasificación:
  - **OK**: DPI >= 300 (calidad óptima para impresión).
  - **Warning**: 200 <= DPI < 300 (calidad aceptable pero no óptima).
  - **Low**: DPI < 200 (calidad baja, puede verse pixelada).
- **Visualización**: Indicador de color en la interfaz de administración.

# 📂 Estructura del Proyecto

```
pablo/
├── arte-ideas.php              # Archivo principal del plugin
│   ├── Definición de constantes (API keys)
│   ├── Enqueue de scripts (React, ReactDOM, Babel)
│   ├── Inyección de CSS y JS
│   ├── Shortcode principal
│   ├── AJAX: cristopher_crear_preferencia (Mercado Pago)
│   └── AJAX: cristopher_editar_imagen_ia (Google Gemini)
│
├── assets/
│   ├── script.js              # Frontend React (inyectado directamente)
│   └── style.css              # Estilos CSS (inyectados en <head>)
│
└── includes/
    ├── orders-system.php       # CPT "Pedidos Fotográficos"
    │   ├── Registro del CPT
    │   ├── Meta boxes (Info, Archivos, Estados)
    │   ├── Función: ai_crear_pedido()
    │   └── Función: ai_actualizar_pedido_pagado()
    │
    ├── admin-settings.php      # Panel de administración
    │   ├── Menú principal "Sistema de Pedidos Fotográficos"
    │   ├── Tab: Listado (redirección)
    │   ├── Tab: Claves de Pago (Stripe, MP)
    │   ├── Tab: Gestionar Catálogo (CRUD productos)
    │   └── Tab: Integración Externa (API key, webhook URL, secret)
    │
    ├── rest-api-endpoints.php  # REST API para Django
    │   └── GET /wp-json/ai/v1/pedidos
    │       ├── Autenticación por header X-API-KEY
    │       ├── Filtros: estado, desde
    │       └── Formato JSON establecido
    │
    ├── webhook-outgoing.php    # Webhook saliente a Django
    │   ├── Función: ai_notificar_django()
    │   ├── Hook: ai_pedido_pagado
    │   ├── Headers de seguridad
    │   └── Logging de intentos
    │
    ├── webhook-handler.php     # Procesar webhooks de pago
    │   ├── AJAX: ai_webhook_mp (Mercado Pago)
    │   ├── AJAX: ai_webhook_stripe (Stripe)
    │   └── Validación y actualización de pedidos
    │
    ├── roles.php               # Rol de operador
    │   └── Registro de rol "minilab_operator" con capabilities
    │
    ├── download-zip.php        # Descarga de archivos
    │   ├── AJAX: ai_descargar_zip
    │   └── Generación de ZIP con todas las imágenes del pedido
    │
    └── dpi-calculator.php      # Cálculo de DPI
        └── Función: ai_calculate_dpi_status()
```

 # 🚀 Instalación y Configuración

### Requisitos Previos

- WordPress 5.0 o superior
- PHP 7.4 o superior
- MySQL 5.6 o superior
- Composer (para instalar dependencias de Mercado Pago)

# 🔐 Seguridad y Validaciones

# Validaciones Implementadas

1. **Nonces de WordPress**: Todas las peticiones AJAX validan nonces para prevenir CSRF.
2. **Sanitización de Inputs**: Todos los datos de usuario se sanitizan con funciones de WordPress (`sanitize_text_field`, `sanitize_email`, etc.).
3. **Validación de Permisos**: Verificación de capabilities antes de operaciones sensibles.
4. **Validación de API Keys**: Verificación de tokens antes de procesar peticiones REST.
5. **Validación de Webhooks**: Verificación de secrets compartidos antes de procesar notificaciones.
6. **Validación de Tamaños**: Límites en tamaño de imágenes y longitud de instrucciones.
7. **Escape de Outputs**: Todos los datos se escapan antes de mostrar en HTML.

# Mejores Prácticas Aplicadas

- **Principio de Menor Privilegio**: Rol de operador con permisos mínimos necesarios.
- **Separación de Responsabilidades**: Módulos separados por funcionalidad.
- **Logging**: Registro de operaciones críticas para debugging.
- **Manejo de Errores**: Try-catch en operaciones que pueden fallar.
- **Timeouts**: Configuración de timeouts en peticiones externas.

# 📊 Base de Datos

### Estructura de Datos

**Custom Post Type**: `ai_pedido`

**Metadatos Principales**:
- `_ai_cliente_nombre`: Nombre del cliente
- `_ai_cliente_email`: Email del cliente
- `_ai_cliente_telefono`: Teléfono del cliente
- `_ai_cliente_dni`: DNI del cliente
- `_ai_entrega_modo`: `domicilio` o `tienda`
- `_ai_entrega_direccion`: Dirección completa (si es domicilio)
- `_ai_entrega_distrito`: Distrito
- `_ai_entrega_provincia`: Provincia
- `_ai_entrega_departamento`: Departamento
- `_ai_pago_metodo`: `mercado_pago` o `stripe`
- `_ai_pago_monto_total`: Monto total del pedido
- `_ai_pago_moneda`: `PEN` o `EUR`
- `_ai_pago_status`: `pendiente_pago`, `pagado_parcial`, `pagado`
- `_ai_produccion_status`: `archivos_listos`, `enviado_minilab`, `impreso`, `entregado`
- `_ai_items`: Array serializado de ítems del pedido
- `_ai_archivos`: Array serializado de archivos/imágenes
- `_ai_mp_preference_id`: ID de preferencia de Mercado Pago
- `_ai_nota_operador`: Nota interna del operador

**Opciones de WordPress**:
- `ai_stripe_public_key`: Clave pública de Stripe
- `ai_stripe_secret_key`: Clave secreta de Stripe
- `ai_stripe_webhook_secret`: Secret del webhook de Stripe
- `ai_mp_public_key`: Clave pública de Mercado Pago
- `ai_mp_access_token`: Access token de Mercado Pago
- `ai_mp_webhook_secret`: Secret del webhook de Mercado Pago
- `ai_catalogo_productos`: Array serializado de productos
- `ai_integracion_api_key`: API key para REST API
- `ai_integracion_webhook_url`: URL del webhook Django
- `ai_integracion_shared_secret`: Secret compartido
- `ai_integracion_webhook_enabled`: Boolean (habilitado/deshabilitado)

# 🔧 Funciones Principales del Backend

### `ai_crear_pedido($datos)`
Crea un nuevo pedido en WordPress a partir de los datos del checkout.

**Parámetros**:
- `$datos`: Array con información del cliente, entrega, ítems, e imágenes.

**Retorna**: ID del pedido creado o `false` en caso de error.

### `ai_actualizar_pedido_pagado($pedido_id)`
Actualiza el estado de un pedido a "pagado" y guarda información de transacción.

**Parámetros**:
- `$pedido_id`: ID del pedido a actualizar.

**Efectos**:
- Actualiza estado de pago a `pagado`.
- Guarda información de transacción.
- Dispara hook `ai_pedido_pagado`.

### `ai_calculate_dpi_status($image_path, $print_width, $print_height)`
Calcula el estado DPI de una imagen para un tamaño de impresión dado.

**Parámetros**:
- `$image_path`: Ruta al archivo de imagen.
- `$print_width`: Ancho de impresión en centímetros.
- `$print_height`: Alto de impresión en centímetros.

**Retorna**: `'ok'`, `'warning'`, o `'low'`.

### `ai_notificar_django($pedido_id)`
Envía notificación webhook a Django cuando un pedido se paga.

**Parámetros**:
- `$pedido_id`: ID del pedido pagado.

**Efectos**:
- Envía POST a URL configurada con payload JSON.
- Registra intento en log.

### `ai_get_integration_settings()`
Obtiene la configuración de integración externa.

**Retorna**: Array con `api_key`, `webhook_url`, `shared_secret`, `webhook_enabled`.

# 📝 Notas para el Equipo Django

## Contrato API Estable

El formato JSON del endpoint `/wp-json/ai/v1/pedidos` está **congelado**. No cambiará sin aviso previo y versión de API.

## Autenticación

Siempre incluir el header `X-API-KEY` con el token configurado en WordPress. Las peticiones sin este header serán rechazadas con código 401.

## Webhook Receptor

Si implementan el webhook receptor en Django, deben:

1. Validar el header `X-Integration-Secret` contra el secret configurado en WordPress.
2. Validar el header `X-Integration-Source` (debe ser `wp_photo_plugin`).
3. Procesar el payload JSON que tiene el mismo formato que el endpoint GET.

## Estados Predefinidos

Los estados de pago y producción son strings predefinidos. No usar valores diferentes:

**Estados de Pago**:
- `pendiente_pago`
- `pagado_parcial`
- `pagado`

**Estados de Producción**:
- `archivos_listos`
- `enviado_minilab`
- `impreso`
- `entregado`

### Formato de Fechas

Todas las fechas están en formato ISO8601 con timezone (ej: `2025-11-24T15:53:00-05:00`).

## Manejo de Errores

El endpoint REST puede retornar:
- `200`: Éxito con datos.
- `401`: No autorizado (API key inválida).
- `400`: Error en parámetros.
- `500`: Error interno del servidor.

# 🚀 Próximas Mejoras Sugeridas

1. **Cache de Respuestas REST**: Implementar cache para mejorar rendimiento en consultas frecuentes.
2. **Retry Logic en Webhooks**: Implementar reintentos automáticos si el webhook a Django falla.
3. **Dashboard de Estadísticas**: Panel con métricas de pedidos, ingresos, y estados.
4. **Notificaciones por Email**: Envío automático de emails al cliente cuando cambia el estado del pedido.
5. **Exportación de Reportes**: Generación de reportes en Excel/PDF de pedidos.
6. **API de Webhooks Más Robusta**: Implementar firma HMAC para validar integridad de webhooks.

# 📞 Soporte

Para problemas o preguntas sobre el backend, contactar al equipo de desarrollo.
Versión del Plugin: 20.0  
                     


