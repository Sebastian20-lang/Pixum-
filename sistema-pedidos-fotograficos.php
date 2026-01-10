<?php
/**
 * Plugin Name:       A - Diseño 2 - CA
 * Description:       Sistema de pedidos de fotografías online
 * Version:           2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Autoload Composer
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// ===============================
// STRIPE (DESDE VARIABLES DE ENTORNO)
// ===============================
define( 'AP_STRIPE_PUBLIC_KEY', getenv('STRIPE_PUBLIC_KEY') ?: '' );
define( 'AP_STRIPE_SECRET_KEY', getenv('STRIPE_SECRET_KEY') ?: '' );
define( 'AP_STRIPE_WEBHOOK_SECRET', getenv('STRIPE_WEBHOOK_SECRET') ?: '' );

// ===============================
// MERCADO PAGO
// ===============================
define( 'AP_MP_PUBLIC_KEY', getenv('MP_PUBLIC_KEY') ?: '' );
define( 'AP_MP_ACCESS_TOKEN', getenv('MP_ACCESS_TOKEN') ?: '' );
define( 'AP_MP_WEBHOOK_SECRET', getenv('MP_WEBHOOK_SECRET') ?: '' );

// Cargar módulos
require_once __DIR__ . '/includes/cpt-pedidos.php';
require_once __DIR__ . '/includes/shortcodes.php';
require_once __DIR__ . '/includes/ajax-handlers.php';
