<?php
// includes/cpt-pedidos.php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Registrar el Custom Post Type (CPT) 'ap_pedido'.
 */
function ap_register_cpt_pedido() {
    $labels = [
        'name'               => _x( 'Pedidos Fotográficos', 'post type general name', 'carlos-pedidos' ),
        'singular_name'      => _x( 'Pedido', 'post type singular name', 'carlos-pedidos' ),
        'add_new'            => _x( 'Añadir nuevo', 'pedido', 'carlos-pedidos' ),
        'add_new_item'       => __( 'Añadir nuevo pedido', 'carlos-pedidos' ),
        'edit_item'          => __( 'Editar pedido', 'carlos-pedidos' ),
        'new_item'           => __( 'Nuevo pedido', 'carlos-pedidos' ),
        'view_item'          => __( 'Ver pedido', 'carlos-pedidos' ),
        'search_items'       => __( 'Buscar pedidos', 'carlos-pedidos' ),
        'not_found'          => __( 'No se encontraron pedidos', 'carlos-pedidos' ),
        'not_found_in_trash' => __( 'No se encontraron pedidos en la papelera', 'carlos-pedidos' ),
    ];

    $args = [
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => ['slug' => 'pedido-foto'],
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 20,
        'menu_icon'          => 'dashicons-camera-alt',
        'supports'           => ['title', 'editor', 'author', 'custom-fields'],
    ];

    register_post_type( 'ap_pedido', $args );
}
add_action( 'init', 'ap_register_cpt_pedido' );

/**
 * Registrar los estados de pedido personalizados.
 */
function ap_register_custom_post_status() {
    
    // Pendiente de Pago (Reusamos 'pending')
    register_post_status( 'wc-pending', [
        'label'                     => _x( 'Pendiente de pago', 'post status', 'carlos-pedidos' ),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Pendiente de pago <span class="count">(%s)</span>', 'Pendientes de pago <span class="count">(%s)</span>', 'carlos-pedidos' ),
    ]);
    
    // Pagado / Procesando (Reusamos 'processing')
    register_post_status( 'wc-processing', [
        'label'                     => _x( 'Pagado (Procesando)', 'post status', 'carlos-pedidos' ),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Pagado <span class="count">(%s)</span>', 'Pagados <span class="count">(%s)</span>', 'carlos-pedidos' ),
    ]);

    // Impreso (Nuevo)
    register_post_status( 'ap-impreso', [
        'label'                     => _x( 'Impreso', 'post status', 'carlos-pedidos' ),
        'public'                    => true,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Impreso <span class="count">(%s)</span>', 'Impresos <span class="count">(%s)</span>', 'carlos-pedidos' ),
    ]);

    // Entregado (Reusamos 'completed')
    register_post_status( 'wc-completed', [
        'label'                     => _x( 'Entregado', 'post status', 'carlos-pedidos' ),
        'public'                    => true,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Entregado <span class="count">(%s)</span>', 'Entregados <span class="count">(%s)</span>', 'carlos-pedidos' ),
    ]);
}
add_action( 'init', 'ap_register_custom_post_status' );

/**
 * Añadir los nuevos estados al desplegable de edición rápida del admin.
 */
function ap_add_custom_statuses_to_dropdown( $statuses ) {
    global $post;
    if ( $post && $post->post_type == 'ap_pedido' ) {
        $statuses['ap-impreso'] = _x( 'Impreso', 'post status', 'carlos-pedidos' );
    }
    return $statuses;
}
add_filter( 'post_statuses', 'ap_add_custom_statuses_to_dropdown' );