# 📘 Documentación Técnica Backend | ArteIDEAS

**Proyecto:** Sistema de Gestión de Pedidos Fotográficos con IA
**Versión del API:** 1.0 (Plugin v20.0)
**Desarrollador Backend:** **Carlos Landa**
**Última actualización:** Diciembre 2025

---

## 📋 Resumen del Sistema

El backend de **ArteIDEAS** es una solución robusta construida sobre **WordPress (PHP)**, diseñada para orquestar el flujo completo de pedidos de impresión fotográfica. El sistema actúa como un *middleware inteligente* entre múltiples servicios:

* **Frontend (React):** Captura de pedidos, carga de imágenes y edición.
* **Pasarelas de Pago:** Mercado Pago y Stripe.
* **Inteligencia Artificial:** Google Gemini para procesamiento y mejora de imágenes.
* **Sistema Externo:** Integración vía **REST API y Webhooks** con un ERP/Logística desarrollado en **Django**.

---

## ⚙️ Arquitectura y Flujo de Datos

El backend centraliza la lógica de negocio y garantiza consistencia entre pagos, procesamiento de imágenes y despacho logístico. El flujo crítico del sistema conecta:

1. Creación del pedido desde el frontend.
2. Procesamiento del pago mediante pasarela seleccionada.
3. Actualización automática de estados financieros y operativos.
4. Notificación al sistema externo (Django) vía Webhooks.
5. Gestión y entrega de archivos finales.

---

## 🌟 Módulos Principales Desarrollados

### 1. 🛒 Core: Gestión de Pedidos (CPT)

* Implementación de un **Custom Post Type (`ai_pedido`)** optimizado para altos volúmenes de datos.
* **Persistencia estructurada** mediante `meta_keys` para:

  * Datos del cliente
  * Dirección de entrega
  * Especificaciones técnicas de impresión
* **Máquina de estados** desacoplada en dos flujos:

  * **Financiero:** `pendiente → pagado`
  * **Operativo:** `archivos_listos → enviado_minilab → entregado`
* **Gestor de assets:** Manejo de múltiples imágenes por pedido y generación dinámica de archivos **ZIP** para descargas masivas.

---

### 2. 💳 Pasarelas de Pago (Mercado Pago & Stripe)

* Integración **servidor-a-servidor** para máxima seguridad e integridad de transacciones.

**Mercado Pago (SDK PHP):**

* Generación de preferencias con `metadata` enlazada al ID del pedido.
* **Webhook Listener** dedicado:

  * Validación de firma de seguridad.
  * Actualización automática del estado del pedido en tiempo real.

**Stripe:**

* Arquitectura paralela a Mercado Pago.
* Permite **switch dinámico de pasarela** mediante configuración desde el panel administrativo.

---

### 3. 🤖 Módulo de IA (Integración con Google Gemini)

* Servicio backend que funciona como **proxy seguro** para edición y procesamiento de imágenes.

**Seguridad:**

* La API Key de Gemini **nunca se expone al cliente**.
* Almacenamiento exclusivo en servidor (`wp-config.php` o constantes).

**Validación y Control:**

* Límite de tamaño: **4MB** por imagen en Base64.
* Sanitización de prompts para prevenir inyecciones.

**Resiliencia:**

* Manejo de **timeouts (60s)**.
* Reintentos automáticos ante saturación de la API de Google.

---

### 4. 🔌 API REST & Webhooks (Integración con Django)

* Diseño de una **API RESTful** para consumo externo (ERP/Logística).

**Endpoint principal:**

```
GET /wp-json/ai/v1/pedidos
```

**Autenticación:**

* Header personalizado: `X-API-KEY`.

**Webhooks salientes:**

* Trigger automático cuando un pedido cambia a estado **pagado**.
* Envío de payload JSON estructurado al sistema Django.
* Firma de seguridad mediante **Shared Secret**.

---

## 🛠️ Stack Tecnológico

| Componente    | Tecnología       | Descripción                               |
| ------------- | ---------------- | ----------------------------------------- |
| Lenguaje      | PHP 7.4+         | Lógica del servidor y reglas de negocio   |
| Framework     | WordPress 5.0+   | CMS base y REST API nativa                |
| Base de Datos | MySQL            | Persistencia de pedidos y configuraciones |
| Librerías     | Composer         | Gestión de dependencias (SDKs de pago)    |
| IA            | Gemini 1.5 Flash | Procesamiento de imágenes                 |
| Intercambio   | JSON / cURL      | Transporte de datos                       |

---

## 📂 Estructura de Archivos del Plugin

```plaintext
arte-ideas/
├── arte-ideas.php              # Bootstrap del plugin y constantes globales
├── includes/
│   ├── orders-system.php       # Lógica del CPT y Meta Boxes
│   ├── rest-api-endpoints.php  # Definición de rutas API
│   ├── webhook-handler.php     # Recepción de pagos (MP / Stripe)
│   ├── webhook-outgoing.php    # Envío de notificaciones a Django
│   ├── admin-settings.php      # Panel de configuración (Settings API)
│   ├── dpi-calculator.php      # Algoritmo de validación de calidad
│   └── download-zip.php        # Generación de archivos ZIP
└── assets/                     # Recursos estáticos (JS / CSS)
```

---

## 📖 Guía de Integración para Terceros

### 1. Consumo de API (Pull)

* **URL:**

```
https://tudominio.com/wp-json/ai/v1/pedidos
```

* **Header obligatorio:**

```
X-API-KEY: <TOKEN_SECRETO>
```

* **Parámetros opcionales:**

  * `estado`: `pagado | pendiente`
  * `desde`: Fecha en formato ISO8601

### 2. Recepción de Webhooks (Push)

* Endpoint receptor configurado en el sistema externo.
* **Payload:** JSON completo del pedido.
* **Validación:** Comparar el header `X-Integration-Secret` con el valor compartido.

---

## 🔒 Seguridad Implementada

* **Nonces:** Protección CSRF en todas las llamadas AJAX.
* **Capabilities:** Control estricto de roles (`minilab_operator`, `administrator`).
* **Sanitización:** Uso sistemático de `sanitize_text_field` y `wp_unslash`.
* **Logs de auditoría:** Registro de errores de integración en:

```
/uploads/ai_integracion/integration.log
```

---

---

# 📘 Documentación Técnica Frontend | Pixum

**Proyecto:** Sistema de Gestión de Pedidos Fotográficos con IA
**Desarrollador Backend:** **Ayrton Yactayo**
**Última actualización:** Diciembre 2025


## 📝 Descripción General

**Pixum** es una aplicación web moderna desarrollada con **React y TypeScript**, diseñada para permitir a los usuarios **subir, visualizar, editar y procesar imágenes** mediante técnicas de **inteligencia artificial generativa**.

La aplicación se integra con la **API Gemini de Google**, permitiendo análisis y transformaciones inteligentes sobre las imágenes proporcionadas por el usuario.

El proyecto utiliza **Vite** como entorno de desarrollo, garantizando arranque rápido, recarga en caliente (*HMR*) y una experiencia de desarrollo eficiente. Pixum presenta un enfoque **visual, interactivo y modular**, orientado al procesamiento de imágenes con IA.

---

## 🛠️ Tecnologías Utilizadas

* **React** – Desarrollo de interfaces de usuario basadas en componentes reutilizables.
* **TypeScript** – Tipado estático para mejorar la calidad del código y reducir errores.
* **Vite** – Bundler y servidor de desarrollo rápido.
* **API Gemini (IA Generativa)** – Procesamiento inteligente de imágenes.
* **HTML5 / CSS** – Estructura semántica y estilos visuales.
* **Variables de entorno** – Gestión segura de claves y configuraciones sensibles.

---

## 📂 Estructura del Proyecto Frontend

```plaintext
pixum/
│── components/
│   ├── PhotoEditor.tsx
│   └── UploadSelection.tsx
│
│── services/
│   └── geminiService.ts
│
│── App.tsx
│── index.tsx
│── index.html
│── types.ts
│── metadata.json
│── package.json
│── tsconfig.json
│── vite.config.ts
│── .env.local
│── .gitignore
│── README.md
```

La estructura del proyecto está organizada de forma **modular**, separando claramente:

* Interfaz de usuario
* Lógica de negocio
* Configuración del entorno

---

## 🔍 Análisis de Archivos Principales

### 🔹 App.tsx

* Controla el flujo general de la aplicación.
* Gestiona el estado principal.
* Renderiza los componentes clave.
* Funciona como contenedor raíz del sistema.

### 🔹 index.tsx

* Punto de entrada de React.
* Renderiza el componente `<App />` en el DOM.
* Conecta la aplicación con `index.html`.

### 🔹 components/UploadSelection.tsx

Componente responsable de:

* Permitir la carga de imágenes por parte del usuario.
* Validar tipo y formato de archivos.
* Preparar las imágenes para edición o envío al servicio de IA.

### 🔹 components/PhotoEditor.tsx

Componente central del sistema que:

* Muestra la imagen seleccionada.
* Permite realizar ajustes o ediciones.
* Envía la imagen al servicio de IA.
* Recibe y presenta los resultados genera
