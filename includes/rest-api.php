<?php
// includes/rest-api.php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Registrar endpoints REST para Webhooks.
 */
function ap_register_rest_endpoints() {
    
    // Endpoint para Stripe
    register_rest_route( 'ap-pedidos/v1', '/webhook-stripe', [
        'methods' => 'POST',
        'callback' => 'ap_handle_stripe_webhook',
        'permission_callback' => '__return_true',
    ] );

    // Endpoint para Mercado Pago
    register_rest_route( 'ap-pedidos/v1', '/webhook-mercadopago', [
        'methods' => 'POST',
        'callback' => 'ap_handle_mercadopago_webhook',
        'permission_callback' => '__return_true',
    ] );
}
add_action( 'rest_api_init', 'ap_register_rest_endpoints' );

/**
 * Manejador del Webhook de Stripe.
 */
function ap_handle_stripe_webhook( $request ) {
    $payload = $request->get_body();
    $sig_header = $request->get_header( 'stripe-signature' );
    
    // Lee la clave desde las constantes (como tú querías)
    $endpoint_secret = AP_STRIPE_WEBHOOK_SECRET; 
    
    if ( ! $endpoint_secret ) { return new WP_Error( 'config_error', 'Falta el secreto del webhook de Stripe.', ['status' => 500] ); }
    try {
        $event = \Stripe\Webhook::constructEvent( $payload, $sig_header, $endpoint_secret );
    } catch( \UnexpectedValueException $e ) {
        return new WP_Error( 'bad_request', 'Invalid payload', ['status' => 400] );
    } catch( \Stripe\Exception\SignatureVerificationException $e ) {
        return new WP_Error( 'bad_signature', 'Invalid signature', ['status' => 400] );
    }
    if ( $event->type == 'checkout.session.completed' ) {
        $session = $event->data->object;
        $order_id = $session->client_reference_id; 
        if ( ! $order_id ) { return new WP_Error( 'missing_data', 'No client_reference_id en la sesión.', ['status' => 200] ); }
        $pedido = get_post( $order_id );
        if ( $pedido && $pedido->post_type == 'ap_pedido' && $pedido->post_status == 'wc-pending' ) {
            wp_update_post( [ 'ID' => $order_id, 'post_status' => 'wc-processing' ] );
            update_post_meta( $order_id, '_ap_stripe_payment_intent_id', $session->payment_intent );
            update_post_meta( $order_id, '_ap_stripe_session_id', $session->id );
        } else {
             return new WP_Error( 'pedido_invalido', 'Pedido no encontrado o ya procesado.', ['status' => 200] );
        }
    }
    return new WP_REST_Response( ['status' => 'recibido'], 200 );
}


/**
 * Manejador del Webhook de Mercado Pago (CON LA CORRECCIÓN)
 */
function ap_handle_mercadopago_webhook( $request ) {
    
    // 1. Validar la firma
    // Lee la clave desde las constantes (como tú querías)
    $secret = AP_MP_WEBHOOK_SECRET; 
    
    if ( ! $secret ) {
        return new WP_Error( 'config_error', 'Falta el secreto del webhook de MP.', ['status' => 500] );
    }

    $sig_header = $request->get_header( 'x-signature' );
    $body = $request->get_body();
    
    if ( ! $sig_header ) {
        return new WP_Error( 'bad_request', 'Falta cabecera x-signature.', ['status' => 400] );
    }

    $parts = [];
    parse_str( str_replace(',', '&', $sig_header), $parts );
    $ts = $parts['ts'] ?? null;
    $hash = $parts['v1'] ?? null;

    if ( ! $ts || ! $hash ) {
        return new WP_Error( 'bad_signature', 'Formato de firma inválido.', ['status' => 400] );
    }

    $params = $request->get_params();
    $data_id = $params['data']['id'] ?? null;
    $topic = $params['type'] ?? null; 

    $manifest = "id:$data_id;ts:$ts;";
    $expected_hash = hash_hmac( 'sha256', $manifest, $secret );

    // Comparar los hashes
    if ( ! hash_equals( $expected_hash, $hash ) ) {
        // ¡¡AQUÍ ESTÁ LA CORRECIÓN!! (Quité el guion bajo)
        return new WP_Error( 'bad_signature', 'Firma de Webhook no válida.', ['status' => 403] );
    }
    
    if ( abs( time() - (int)$ts ) > 300 ) {
         return new WP_Error( 'old_request', 'Timestamp de Webhook viejo (Replay Attack?).', ['status' => 400] );
    }

    // 2. Obtener los datos (Solo si la firma fue válida)
    if ( empty($topic) || $topic !== 'payment' || empty($data_id) ) {
         return new WP_Error( 'bad_request', 'Notificación no válida.', ['status' => 200] );
    }

    // 3. Obtener la información del pago desde la API de MP
    try {
        // Lee la clave desde las constantes (como tú querías)
        \MercadoPago\SDK::setAccessToken( AP_MP_ACCESS_TOKEN ); 
        
        $payment_id = $data_id;
        $payment = \MercadoPago\Payment::find_by_id($payment_id);

        if ( ! $payment ) {
            return new WP_Error( 'not_found', 'Pago no encontrado en MP.', ['status' => 200] );
        }

        // 4. Verificar si el pago fue aprobado
        if ( $payment->status == 'approved' && ! empty( $payment->external_reference ) ) {
            
            $order_id = (int) $payment->external_reference;
            $pedido = get_post( $order_id );

            // 5. Actualizar nuestro pedido
            if ( $pedido && $pedido->post_type == 'ap_pedido' && $pedido->post_status == 'wc-pending' ) {
                
                wp_update_post( [
                    'ID' => $order_id,
                    'post_status' => 'wc-processing'
                ] );

                update_post_meta( $order_id, '_ap_mp_payment_id', $payment_id );
                update_post_meta( $order_id, '_ap_metodo_pago', 'mercadopago' );
            }
        }
        
        return new WP_REST_Response( ['status' => 'recibido'], 200 );

    } catch (Exception $e) {
        return new WP_Error( 'mp_error', 'Error de API de Mercado Pago: ' . $e->getMessage(), ['status' => 500] );
    }
}