<?php
// includes/assets.php

if ( ! defined( 'ABSPATH' ) ) exit;

function ap_enqueue_frontend_assets() {
    global $post; 
    
    wp_register_style( 
        'ap-frontend-css', 
        AP_PLUGIN_URL . 'assets/css/style.css', 
        [], 
        AP_VERSION 
    );
    
    wp_register_script( 
        'stripe-js', 
        'https://js.stripe.com/v3/', 
        [], 
        false, 
        true 
    );

    // No necesitamos el JS de Mercado Pago para Checkout Pro (redirect)

    wp_register_script( 
        'ap-frontend-js', 
        AP_PLUGIN_URL . 'assets/js/frontend.js', 
        ['jquery', 'stripe-js'],
        AP_VERSION, 
        true 
    );
    
    wp_register_script(
        'ap-yape-confirm-js',
        AP_PLUGIN_URL . 'assets/js/yape-confirm.js',
        ['jquery'],
        AP_VERSION,
        true
    );

    // Pasar datos de PHP a JS
    $ajax_data = [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'stripe_public_key' => AP_STRIPE_PUBLIC_KEY, 
        
        // Acciones AJAX
        'action_upload' => 'ap_handle_upload',
        'action_create_session' => 'ap_create_stripe_session',
        'action_yape_confirm' => 'ap_confirm_yape_payment',
        'action_create_mp_session' => 'ap_create_mercadopago_session' // NUEVA ACCIÓN
    ];
    
    // Cargar script del formulario principal
    if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'pedidos_carlos' ) ) {
        wp_enqueue_script( 'ap-frontend-js' );
        wp_enqueue_style( 'ap-frontend-css' );
        $ajax_data['upload_nonce'] = wp_create_nonce( 'ap_upload_nonce' );
        wp_localize_script( 'ap-frontend-js', 'ap_ajax', $ajax_data );
    }

    // Cargar script de la página de Yape
    if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'yape_confirmacion_carlos' ) ) {
        wp_enqueue_script( 'ap-yape-confirm-js' );
        wp_enqueue_style( 'ap-frontend-css' );
        $ajax_data['yape_nonce'] = wp_create_nonce( 'ap_yape_confirm_nonce' );
        wp_localize_script( 'ap-yape-confirm-js', 'ap_ajax', $ajax_data );
    }
    
    // Cargar CSS para el historial de pagos
    if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'pagos_carlos' ) ) {
         wp_enqueue_style( 'ap-frontend-css' );
    }
}
add_action( 'wp_enqueue_scripts', 'ap_enqueue_frontend_assets' );