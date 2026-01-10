<?php
// includes/admin-columns.php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Añadir columnas personalizadas
 */
function ap_set_pedido_columns( $columns ) {
    $new_columns = [
        'cb' => $columns['cb'],
        'title' => __( 'Pedido', 'carlos-pedidos' ),
        'ap_cliente' => __( 'Cliente', 'carlos-pedidos' ),
        'ap_entrega' => __( 'Entrega', 'carlos-pedidos' ),
        'ap_pago_info' => __( 'Info. Pago', 'carlos-pedidos' ), // Columna unificada
        'ap_total' => __( 'Total Pedido', 'carlos-pedidos' ),
        'date' => $columns['date']
    ];
    return $new_columns;
}
add_filter( 'manage_ap_pedido_posts_columns', 'ap_set_pedido_columns' );

/**
 * Mostrar el contenido de las columnas
 */
function ap_show_pedido_columns_content( $column, $post_id ) {
    switch ( $column ) {
        case 'ap_cliente':
            $author_id = get_post_field( 'post_author', $post_id );
            if ( $author_id > 0 ) {
                $user = get_userdata( $author_id );
                echo esc_html( $user->display_name ) . '<br><small>' . esc_html($user->user_email) . '</small>';
            } else {
                echo __( 'Visitante', 'carlos-pedidos' );
            }
            break;

        case 'ap_entrega':
            $entrega = get_post_meta( $post_id, '_ap_entrega', true );
            echo '<strong>' . ucfirst( esc_html($entrega) ) . '</strong>';
            if ($entrega === 'domicilio') {
                $direccion = get_post_meta( $post_id, '_ap_direccion', true );
                echo '<br><small>' . esc_html($direccion) . '</small>';
            }
            break;
            
        case 'ap_pago_info': // MODIFICADO para 3 métodos
            $metodo = get_post_meta( $post_id, '_ap_metodo_pago', true );
            
            if ( $metodo === 'stripe' ) {
                $stripe_id = get_post_meta( $post_id, '_ap_stripe_payment_intent_id', true );
                echo '<strong>Stripe</strong><br>';
                if ( $stripe_id ) {
                    echo '<a href="https://dashboard.stripe.com/test/payments/' . esc_attr($stripe_id) . '" target="_blank">Ver en Stripe</a>';
                }
            } elseif ( $metodo === 'yape' ) {
                $comprobante_url = get_post_meta( $post_id, '_ap_comprobante_url', true );
                echo '<strong>Yape</strong><br>';
                if ( $comprobante_url ) {
                    echo '<a href="' . esc_url( $comprobante_url ) . '" target="_blank" style="color:green;">Ver Comprobante</a>';
                } else {
                    echo '(Pendiente de comp.)';
                }
            } elseif ( $metodo === 'mercadopago' ) {
                $mp_id = get_post_meta( $post_id, '_ap_mp_payment_id', true );
                echo '<strong>Mercado Pago</strong><br>';
                if ( $mp_id ) {
                    // Link al dashboard de MP
                    echo '<a href="https://www.mercadopago.com.pe/activities/payment/' . esc_attr($mp_id) . '" target="_blank">Ver en MP</a>';
                }
            } else {
                echo esc_html($metodo) ?: 'N/A';
            }
            break;

        case 'ap_total':
            $total = get_post_meta( $post_id, '_ap_total', true );
            echo '<strong>S/ ' . number_format( (float) $total, 2 ) . '</strong>';
            break;
    }
}
add_action( 'manage_ap_pedido_posts_custom_column', 'ap_show_pedido_columns_content', 10, 2 );

// ... (Las funciones ap_make_columns_sortable y ap_custom_orderby son idénticas) ...
function ap_make_columns_sortable( $columns ) {
    $columns['ap_total'] = '_ap_total';
    return $columns;
}
add_filter( 'manage_edit-ap_pedido_sortable_columns', 'ap_make_columns_sortable' );
function ap_custom_orderby( $query ) {
    if ( ! is_admin() || ! $query->is_main_query() ) { return; }
    $orderby = $query->get( 'orderby');
    if ( '_ap_total' === $orderby ) {
        $query->set( 'meta_key', '_ap_total' );
        $query->set( 'orderby', 'meta_value_num' );
    }
}
add_action( 'pre_get_posts', 'ap_custom_orderby' );
/**
 * --- VISOR DE FOTOS Y DETALLES DENTRO DEL PEDIDO ---
 * Agrega una caja (Meta Box) en la pantalla de edición del pedido
 * para ver las fotos y descargar los archivos originales.
 */

// 1. Registrar la caja (Meta Box)
function ap_add_photos_metabox() {
    add_meta_box(
        'ap_fotos_pedido_box',          // ID
        '📸 Fotos del Pedido y Detalles', // Título
        'ap_render_photos_metabox',     // Función que muestra el contenido
        'ap_pedido',                    // Post Type
        'normal',                       // Posición (normal = columna principal)
        'high'                          // Prioridad
    );
}
add_action( 'add_meta_boxes', 'ap_add_photos_metabox' );

// 2. Mostrar el contenido (HTML)
function ap_render_photos_metabox( $post ) {
    // Recuperar datos
    $cart_json = get_post_meta( $post->ID, '_ap_cart_json', true );
    $cart_items = json_decode( $cart_json, true );
    $attach_ids = get_post_meta( $post->ID, '_ap_foto_adjunto_id', false ); // false devuelve array de todos
    
    // Recuperar datos de envío
    $entrega = get_post_meta( $post->ID, '_ap_entrega', true );
    $direccion = get_post_meta( $post->ID, '_ap_direccion', true );
    $distrito = get_post_meta( $post->ID, '_ap_distrito', true );
    $referencia = get_post_meta( $post->ID, '_ap_referencia', true );
    
    echo '<div style="background: #fdfdfd; padding: 10px;">';
    
    // A) INFORMACIÓN DE ENVÍO
    echo '<h4>🚚 Datos de Entrega</h4>';
    echo '<p><strong>Método:</strong> ' . ucfirst($entrega) . '</p>';
    if ( $entrega === 'domicilio' ) {
        echo '<p><strong>Dirección:</strong> ' . esc_html($direccion) . ' (' . esc_html($distrito) . ')</p>';
        if($referencia) echo '<p><strong>Referencia:</strong> ' . esc_html($referencia) . '</p>';
    }
    echo '<hr>';

    // B) DETALLE DEL CARRITO (Tabla)
    echo '<h4>🛒 Detalle de Productos</h4>';
    if ( $cart_items && is_array($cart_items) ) {
        echo '<table class="widefat fixed" cellspacing="0" style="margin-bottom: 15px;">';
        echo '<thead><tr><th>Archivo Original</th><th>Tamaño</th><th>Cantidad</th><th>Precio Unit.</th></tr></thead>';
        echo '<tbody>';
        foreach ( $cart_items as $item ) {
            echo '<tr>';
            echo '<td>' . esc_html($item['fileName']) . '</td>';
            echo '<td><strong>' . esc_html($item['sizeKey']) . '</strong></td>'; // Ej: 10x15
            echo '<td>' . esc_html($item['qty']) . '</td>';
            echo '<td>S/ ' . number_format($item['price'], 2) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p><em>No hay detalles del carrito guardados (Pedido antiguo o error).</em></p>';
    }
    
    echo '<hr>';

    // C) GALERÍA DE DESCARGA
    echo '<h4>⬇️ Descargar Archivos para Imprimir</h4>';
    
    if ( ! empty( $attach_ids ) ) {
        echo '<div style="display: flex; flex-wrap: wrap; gap: 15px;">';
        
        foreach ( $attach_ids as $attach_id ) {
            $url_full = wp_get_attachment_url( $attach_id ); // URL Alta Calidad
            $thumb = wp_get_attachment_image_src( $attach_id, 'thumbnail' ); // Miniatura
            $filename = basename( get_attached_file( $attach_id ) );
            
            if ( $url_full ) {
                echo '<div style="border: 1px solid #ddd; padding: 10px; border-radius: 5px; text-align: center; width: 140px; background: #fff;">';
                
                // Mostrar miniatura
                if ( $thumb ) {
                    echo '<img src="' . esc_url( $thumb[0] ) . '" style="max-width: 100%; height: auto; border-radius: 4px; margin-bottom: 5px;">';
                } else {
                    echo '<div style="background:#eee; height:100px; display:flex; align-items:center; justify-content:center;">Sin vista previa</div>';
                }
                
                // Nombre archivo
                echo '<div style="font-size: 11px; color: #666; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-bottom: 5px;">' . esc_html($filename) . '</div>';
                
                // Botón Descargar
                echo '<a href="' . esc_url( $url_full ) . '" class="button button-primary" download target="_blank">Descargar Original</a>';
                
                echo '</div>';
            }
        }
        echo '</div>';
        
        // Botón para descargar todo (Truco: abre todos los enlaces)
        echo '<p style="margin-top: 20px;"><button type="button" class="button" onclick="document.querySelectorAll(\'.button-primary[download]\').forEach(l => window.open(l.href));">Abrir todas las imágenes</button></p>';
        
    } else {
        echo '<p>No se han encontrado archivos adjuntos.</p>';
    }
    
    echo '</div>';
}