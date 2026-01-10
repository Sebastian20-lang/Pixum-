<?php
// includes/ajax-handlers.php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * PASO 1: Crear Pedido Complejo (Carrito)
 */
function ap_handle_upload_callback() {
    check_ajax_referer( 'ap_upload_nonce', 'ap_nonce' );
    
    // 1. Validar Datos Básicos
    if ( empty( $_FILES['ap_fotos'] ) || empty( $_POST['ap_cart_data'] ) ) {
        wp_send_json_error( ['message' => 'Faltan datos (fotos o información del carrito).'], 400 );
    }
    
    // 2. Decodificar el carrito (JSON enviado desde JS)
    // El JSON debe ser un array: [ { id: 'file_0', size: '10x15', qty: 2, price: 0.45 }, ... ]
    $cart_items = json_decode( stripslashes($_POST['ap_cart_data']), true );
    if ( ! is_array($cart_items) ) {
        wp_send_json_error( ['message' => 'Error en formato de datos del carrito.'], 400 );
    }

    // 3. Configuración de Envío
    $metodo_entrega = sanitize_text_field( $_POST['ap_entrega'] );
    $costo_envio = ($metodo_entrega === 'domicilio') ? ap_get_costo_envio() : 0;
    
    if ( $metodo_entrega === 'domicilio' && empty( $_POST['ap_direccion'] ) ) {
         wp_send_json_error( ['message' => 'La dirección es obligatoria para envío a domicilio.'], 400 );
    }

    // 4. Calcular Total en Backend (Por seguridad)
    $total_fotos = 0;
    $subtotal_calculado = 0;
    $precios_validos = ap_get_tamanos_precios(); // ['10x10'=>0.35, '10x15'=>0.45, 'A4'=>3.50]

    // Mapear los items del carrito para facilitar el conteo
    // Estructura esperada del Item: { 'sizeKey': '10x15', 'qty': 5, 'fileName': 'foto1.jpg' }
    foreach ($cart_items as $item) {
        $sizeKey = $item['sizeKey'];
        $qty = intval($item['qty']);
        
        if ( isset($precios_validos[$sizeKey]) ) {
            $subtotal_calculado += ( $precios_validos[$sizeKey] * $qty );
            $total_fotos += $qty;
        }
    }

    $total_pedido = $subtotal_calculado + $costo_envio;

    // 5. Crear el Post del Pedido
    $pedido_id = wp_insert_post( [
        'post_title'   => 'Pedido Web #' . time() . ' (' . $total_fotos . ' fotos)',
        'post_type'    => 'ap_pedido',
        'post_status'  => 'wc-pending',
        'post_author'  => get_current_user_id(),
    ] );
    
    if ( is_wp_error( $pedido_id ) ) {
        wp_send_json_error( ['message' => 'Error al crear pedido en BD.'], 500 );
    }

    // 6. Guardar Metadatos del Pedido
    update_post_meta( $pedido_id, '_ap_total', $total_pedido );
    update_post_meta( $pedido_id, '_ap_entrega', $metodo_entrega );
    update_post_meta( $pedido_id, '_ap_metodo_pago', sanitize_text_field($_POST['ap_metodo_pago']) );
    update_post_meta( $pedido_id, '_ap_cantidad_fotos', $total_fotos );
    
    // Guardar el JSON del carrito completo para referencia futura
    update_post_meta( $pedido_id, '_ap_cart_json', json_encode($cart_items) );

    if ( $metodo_entrega === 'domicilio' ) {
        update_post_meta( $pedido_id, '_ap_direccion', sanitize_textarea_field( $_POST['ap_direccion'] ) );
        update_post_meta( $pedido_id, '_ap_distrito', sanitize_text_field( $_POST['ap_distrito'] ) );
        update_post_meta( $pedido_id, '_ap_referencia', sanitize_text_field( $_POST['ap_referencia'] ) );
    }

    // 7. Procesar y Subir Archivos
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
    require_once( ABSPATH . 'wp-admin/includes/image.php' );
    
    $files = $_FILES['ap_fotos'];
    $upload_overrides = ['test_form' => false];
    $attachment_ids = [];
    $errores_subida = [];

    if ( ! empty( $files['name'][0] ) ) {
        foreach ( $files['name'] as $key => $value ) {
            if ( $files['name'][$key] ) {
                $file = [
                    'name'     => $files['name'][$key],
                    'type'     => $files['type'][$key],
                    'tmp_name' => $files['tmp_name'][$key],
                    'error'    => $files['error'][$key],
                    'size'     => $files['size'][$key]
                ];

                $movefile = wp_handle_upload( $file, $upload_overrides );

                if ( $movefile && ! isset( $movefile['error'] ) ) {
                    $attachment = [
                        'guid'           => $movefile['url'], 
                        'post_mime_type' => $movefile['type'],
                        'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $movefile['file'] ) ),
                        'post_content'   => '',
                        'post_status'    => 'inherit'
                    ];
                    
                    $attach_id = wp_insert_attachment( $attachment, $movefile['file'], $pedido_id );
                    $attach_data = wp_generate_attachment_metadata( $attach_id, $movefile['file'] );
                    wp_update_attachment_metadata( $attach_id, $attach_data );
                    
                    // Relacionar imagen con el pedido
                    add_post_meta( $pedido_id, '_ap_foto_adjunto_id', $attach_id );
                    $attachment_ids[] = $attach_id;
                } else {
                    $errores_subida[] = $file['name'];
                }
            }
        }
    }

    if (count($errores_subida) > 0) {
        // Opcional: Manejar errores parciales, pero por ahora seguimos si al menos una subió
    }

    // 8. Responder con éxito para pasar al pago
    wp_send_json_success( [
        'order_id' => $pedido_id,
        'total' => $total_pedido,
        'metodo_pago' => $_POST['ap_metodo_pago']
    ] );
}
add_action( 'wp_ajax_ap_handle_upload', 'ap_handle_upload_callback' );
add_action( 'wp_ajax_nopriv_ap_handle_upload', 'ap_handle_upload_callback' );


/**
 * PASO 2 (Solo Stripe)
 */
function ap_create_stripe_session_callback() {
    check_ajax_referer( 'ap_upload_nonce', 'ap_nonce' );
    
    if ( empty( $_POST['order_id'] ) ) { 
        wp_send_json_error(['message' => 'No se proporcionó ID de pedido.'], 400); 
    }
    
    $order_id = absint( $_POST['order_id'] );
    $pedido = get_post( $order_id );
    
    if ( ! $pedido || $pedido->post_type !== 'ap_pedido' || $pedido->post_status !== 'wc-pending' ) { 
        wp_send_json_error(['message' => 'El pedido no es válido o ya fue procesado.'], 400); 
    }
    
    $total_a_pagar = (float) get_post_meta( $order_id, '_ap_total', true );
    if ( $total_a_pagar <= 0 ) { 
        wp_send_json_error(['message' => 'El total del pedido no es válido.'], 400); 
    }
    
    try {
        // Verificar si la clase de Stripe existe antes de usarla
        if ( ! class_exists( '\Stripe\Stripe' ) ) {
            throw new Exception( 'La librería de Stripe no está cargada. Revisa la carpeta vendor.' );
        }

        // Verificar si la constante de la clave existe
        if ( ! defined( 'AP_STRIPE_SECRET_KEY' ) ) {
            throw new Exception( 'La constante AP_STRIPE_SECRET_KEY no está definida.' );
        }

        \Stripe\Stripe::setApiKey( AP_STRIPE_SECRET_KEY );
        
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'pen',
                    'product_data' => [
                        'name' => 'Pedido Fotográfico #' . $order_id,
                        'description' => get_the_title( $order_id ),
                    ],
                    'unit_amount' => (int) ($total_a_pagar * 100),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'client_reference_id' => (string) $order_id,
            'success_url' => home_url('/gracias?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url' => home_url('/cancelado?order_id=' . $order_id),
        ]);
        
        wp_send_json_success( ['session_id' => $session->id] );
        
    } catch ( \Throwable $e ) {  // USAR \Throwable CAPTURA ERRORES FATALES
        error_log('Error Stripe Session: ' . $e->getMessage());
        wp_send_json_error([
            'message' => 'Error del Sistema: ' . $e->getMessage()
        ], 500); 
    }
}
add_action( 'wp_ajax_ap_create_stripe_session', 'ap_create_stripe_session_callback' );
add_action( 'wp_ajax_nopriv_ap_create_stripe_session', 'ap_create_stripe_session_callback' );


/**
 * PASO 2 (Solo Mercado Pago) - CORREGIDO PARA SDK v3.x
 * Crear la preferencia de pago de MP y devolver el init_point (URL de pago)
 */
function ap_create_mercadopago_session_callback() {
    check_ajax_referer( 'ap_upload_nonce', 'ap_nonce' );
    
    if ( empty( $_POST['order_id'] ) ) {
        wp_send_json_error(['message' => 'No se proporcionó ID de pedido.'], 400);
    }
    
    $order_id = absint( $_POST['order_id'] );
    $pedido = get_post( $order_id );

    if ( ! $pedido || $pedido->post_type !== 'ap_pedido' || $pedido->post_status !== 'wc-pending' ) {
        wp_send_json_error(['message' => 'El pedido no es válido o ya fue procesado.'], 400);
    }
    
    $total_a_pagar = (float) get_post_meta( $order_id, '_ap_total', true );
    if ( $total_a_pagar <= 0 ) {
         wp_send_json_error(['message' => 'El total del pedido no es válido.'], 400);
    }

    try {
        // Usar el nuevo SDK v3.x de Mercado Pago
        $client = new \MercadoPago\Client\Preference\PreferenceClient();
        
        $preference_data = [
            'items' => [
                [
                    'id' => (string) $order_id,
                    'title' => 'Pedido Fotográfico #' . $order_id,
                    'description' => get_the_title( $order_id ),
                    'quantity' => 1,
                    'currency_id' => 'PEN', // Soles Peruanos
                    'unit_price' => $total_a_pagar
                ]
            ],
            'back_urls' => [
                'success' => home_url('/gracias/'),
                'failure' => home_url('/cancelado/'),
                'pending' => home_url('/pendiente/')
            ],
            'auto_return' => 'approved',
            'external_reference' => (string) $order_id,
            'notification_url' => home_url('/wp-json/ap-pedidos/v1/webhook-mercadopago'),
            'statement_descriptor' => 'PEDIDO FOTOS',
            'expires' => true,
            'expiration_date_from' => date('c'),
            'expiration_date_to' => date('c', strtotime('+2 hours'))
        ];

        // Configurar el access token
        \MercadoPago\MercadoPagoConfig::setAccessToken( AP_MP_ACCESS_TOKEN );
        
        // Crear la preferencia
        $preference = $client->create($preference_data);

        if ( ! empty( $preference->init_point ) ) {
            // Guardar el preference_id para referencia
            update_post_meta( $order_id, '_ap_mp_preference_id', $preference->id );
            
            wp_send_json_success( [
                'init_point' => $preference->init_point,
                'preference_id' => $preference->id
            ] );
        } else {
            throw new Exception('No se generó init_point');
        }

   //...
// ... en includes/ajax-handlers.php, dentro de ap_create_mercadopago_session_callback

} catch (Exception $e) {
    
    $errorMessage = $e->getMessage();
    
    // --- ESTA ES LA PARTE NUEVA ---
    // Revisa si la excepción es de la API de MP y tiene más detalles
    if (is_callable([$e, 'getApiResponse'])) {
        try {
            $apiResponse = $e->getApiResponse();
            $errorDetails = $apiResponse->getContent();
            
            // Convierte los detalles (que pueden ser un array) a un string
            if (is_array($errorDetails) || is_object($errorDetails)) {
                $errorMessage = 'Status ' . $apiResponse->getStatusCode() . ': ' . json_encode($errorDetails, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            } else {
                $errorMessage = 'Status ' . $apiResponse->getStatusCode() . ': ' . $errorDetails;
            }
        } catch (Exception $innerEx) {
            // Si falla al obtener detalles, nos quedamos con el mensaje original
            $errorMessage = $e->getMessage();
        }
    }
    // --- FIN DE LA PARTE NUEVA ---

    error_log('Error Mercado Pago Preference: ' . $errorMessage);
    
    wp_send_json_error([
        'message' => 'Error real de MP: ' . $errorMessage
    ], 500);
}
//...
}
add_action( 'wp_ajax_ap_create_mercadopago_session', 'ap_create_mercadopago_session_callback' );
add_action( 'wp_ajax_nopriv_ap_create_mercadopago_session', 'ap_create_mercadopago_session_callback' );


/**
 * Helper para subir comprobantes
 */
function ap_custom_upload_dir_comprobantes( $dirs ) {
    $dirs['subdir'] = '/comprobantes';
    $dirs['path'] = $dirs['basedir'] . '/comprobantes';
    $dirs['url'] = $dirs['baseurl'] . '/comprobantes';
    return $dirs;
}

/**
 * PASO 2 (Yape con comprobante)
 */
function ap_confirm_yape_payment_callback() {
    check_ajax_referer( 'ap_yape_confirm_nonce', 'ap_nonce' );
    
    if ( empty( $_POST['ap_pedido_id'] ) || empty( $_FILES['ap_comprobante_yape'] ) ) { 
        wp_send_json_error( ['message' => 'Faltan datos. Asegúrate de subir el comprobante.'], 400 ); 
    }
    
    $order_id = absint( $_POST['ap_pedido_id'] );
    $pedido = get_post( $order_id );
    
    if ( ! $pedido || $pedido->post_type !== 'ap_pedido' || $pedido->post_status !== 'wc-pending' ) { 
        wp_send_json_error(['message' => 'El pedido no es válido o ya fue procesado.'], 400); 
    }
    
    if ( ! function_exists( 'wp_handle_upload' ) ) { 
        require_once( ABSPATH . 'wp-admin/includes/file.php' ); 
    }
    
    $comprobante_overrides = [
        'test_form' => false,
        'mimes' => [
            'jpg|jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'pdf' => 'application/pdf'
        ],
    ];
    
    add_filter( 'upload_dir', 'ap_custom_upload_dir_comprobantes' );
    $movefile_comp = wp_handle_upload( $_FILES['ap_comprobante_yape'], $comprobante_overrides );
    remove_filter( 'upload_dir', 'ap_custom_upload_dir_comprobantes' );
    
    if ( $movefile_comp && ! isset( $movefile_comp['error'] ) ) {
        wp_update_post( [
            'ID' => $order_id,
            'post_status' => 'wc-processing'
        ] );
        
        update_post_meta( $order_id, '_ap_comprobante_url', esc_url_raw($movefile_comp['url']) );
        update_post_meta( $order_id, '_ap_metodo_pago', 'yape' );
        update_post_meta( $order_id, '_ap_payment_date', current_time('mysql') );
        
        wp_send_json_success( [
            'message' => '¡Pago confirmado! Redirigiendo...',
            'redirect_url' => home_url('/gracias/')
        ] );
    } else {
        wp_send_json_error( [
            'message' => 'Error al subir comprobante: ' . (isset($movefile_comp['error']) ? $movefile_comp['error'] : 'Error desconocido')
        ], 500 );
    }
}
add_action( 'wp_ajax_ap_confirm_yape_payment', 'ap_confirm_yape_payment_callback' );
add_action( 'wp_ajax_nopriv_ap_confirm_yape_payment', 'ap_confirm_yape_payment_callback' );

/* --- AL FINAL DE ajax-handlers.php --- */

/**
 * PASO EXTRA: Procesar edición con Gemini AI
 * Proxy PHP para evitar exponer keys o usar Node SDK
 */
function ap_process_gemini_edit_callback() {
    // 1. Seguridad
    check_ajax_referer( 'ap_upload_nonce', 'ap_nonce' );

    $image_url = isset($_POST['image_url']) ? esc_url_raw($_POST['image_url']) : '';
    $prompt = isset($_POST['prompt']) ? sanitize_text_field($_POST['prompt']) : '';

    if ( empty($image_url) || empty($prompt) ) {
        wp_send_json_error(['message' => 'Faltan datos (imagen o prompt).']);
    }

    // 2. Obtener API Key (Definida en wp-config o constantes del plugin)
    // Asegúrate de definir define('GEMINI_API_KEY', 'tu_api_key'); en tu config
    $api_key = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
    if ( empty($api_key) ) {
        // Fallback para demo si no hay key definida
        $api_key = get_option('ap_settings')['gemini_api_key'] ?? '';
    }

    if ( empty($api_key) ) {
        wp_send_json_error(['message' => 'Error de configuración: API Key no encontrada.']);
    }

    // 3. Preparar Imagen (Convertir URL a Base64 para la API)
    // Nota: Para producción, valida que la URL sea local o confiable
    $image_data = file_get_contents($image_url);
    if ($image_data === false) {
        wp_send_json_error(['message' => 'No se pudo procesar la imagen original.']);
    }
    $base64_image = base64_encode($image_data);
    $mime_type = 'image/jpeg'; // Simplificado, podrías detectar el mime real

    // 4. Llamada a la API REST de Gemini (Model: gemini-1.5-flash)
    $api_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $api_key;

    $payload = [
        "contents" => [
            [
                "parts" => [
                    [
                        "text" => "Eres un editor de fotos experto. Instrucción del usuario: " . $prompt . ". IMPORTANTE: Si puedes editar la imagen, hazlo. Si no puedes generar píxeles directamente, describe detalladamente cómo se vería la imagen editada para usarla como alt text o caption creativo." 
                        // NOTA: La API estándar de generateContent de Gemini devuelve TEXTO. 
                        // Para devolver IMÁGENES reales editadas, se requiere Imagen 2/3 en Vertex AI.
                        // Para este clon, simularemos la respuesta si la API solo devuelve texto, 
                        // o manejaremos la respuesta si el modelo multimodal devuelve blobs (experimental).
                    ],
                    [
                        "inline_data" => [
                            "mime_type" => $mime_type,
                            "data" => $base64_image
                        ]
                    ]
                ]
            ]
        ]
    ];

    $response = wp_remote_post($api_url, [
        'headers' => ['Content-Type' => 'application/json'],
        'body'    => json_encode($payload),
        'timeout' => 30
    ]);

    if ( is_wp_error($response) ) {
        wp_send_json_error(['message' => 'Error de conexión con IA: ' . $response->get_error_message()]);
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    // 5. Procesar Respuesta
    // En un escenario real con Imagen 3, extraeríamos el base64 de la imagen generada.
    // Como gemini-1.5-flash retorna texto principalmente, devolveremos el texto
    // y para la DEMO simularemos un "efecto" visual en el frontend (filtros) si la API no devuelve imagen.
    
    $ai_text = $body['candidates'][0]['content']['parts'][0]['text'] ?? 'Edición procesada.';
    
    // SIMULACIÓN: En este entorno de "Clone", devolvemos la misma imagen (o una url de placeholder editado)
    // para mostrar que el flujo funciona, ya que la API pública gratuita no edita píxeles aún.
    // Si tuvieras acceso a Vertex AI Imagen, aquí iría el base64 real.
    
    wp_send_json_success([
        'text' => $ai_text,
        'image' => $image_url, // Devolvemos la misma URL (o base64 real si la API lo soportara)
        'is_simulation' => true // Flag para que el JS aplique un filtro CSS "mágico"
    ]);
}
add_action( 'wp_ajax_ap_process_gemini_edit', 'ap_process_gemini_edit_callback' );
add_action( 'wp_ajax_nopriv_ap_process_gemini_edit', 'ap_process_gemini_edit_callback' );