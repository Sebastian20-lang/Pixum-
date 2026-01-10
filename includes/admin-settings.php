<?php
// includes/admin-settings.php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Añadir la página de configuración al menú de admin.
 */
function ap_add_admin_menu() {
    add_options_page(
        'Ajustes de Pedidos y Catálogo',
        'Ajustes de Pedidos',
        'manage_options',
        'carlos-pedidos-settings',
        'ap_settings_page_html'
    );
}
add_action( 'admin_menu', 'ap_add_admin_menu' );

/**
 * Registrar los ajustes (Solo las claves, el catálogo lo guardamos manual)
 */
function ap_register_settings() {
    register_setting( 'ap_settings_group', 'ap_settings', 'ap_settings_sanitize' );
}
add_action( 'admin_init', 'ap_register_settings' );

function ap_settings_sanitize( $input ) {
    // Sanitización básica de claves API
    return array_map( 'sanitize_text_field', $input );
}

/**
 * Renderizar la página completa
 */
function ap_settings_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    // --- LÓGICA DE GUARDADO DEL CATÁLOGO ---
    if ( isset( $_POST['ap_save_catalog'] ) && check_admin_referer( 'ap_save_catalog_action', 'ap_catalog_nonce' ) ) {
        if ( ! empty( $_POST['ap_catalog'] ) && is_array( $_POST['ap_catalog'] ) ) {
            $new_catalog = [];
            foreach ( $_POST['ap_catalog'] as $key => $item ) {
                // Sanitizar cada campo
                if ( ! empty( $item['id'] ) ) {
                    $new_catalog[$item['id']] = [
                        'id'    => sanitize_text_field( $item['id'] ),
                        'title' => sanitize_text_field( $item['title'] ),
                        'price' => floatval( $item['price'] ),
                        'desc'  => sanitize_text_field( $item['desc'] ),
                        'img'   => esc_url_raw( $item['img'] ),
                        'ratio' => sanitize_text_field( $item['ratio'] ),
                    ];
                }
            }
            update_option( 'ap_catalogo_productos', $new_catalog );
            echo '<div class="notice notice-success is-dismissible"><p>Catálogo actualizado correctamente.</p></div>';
        }
    }
    
    // Obtener datos
    $api_options = get_option( 'ap_settings' );
    
    // Asegurarnos de tener la función disponible (si estamos en admin puede que no se haya cargado shortcodes.php)
    // Así que cargamos el catálogo manualmente aquí si la función no existe, o usamos get_option directo.
    $catalog = get_option( 'ap_catalogo_productos' );
    if ( empty( $catalog ) ) {
        // Datos por defecto si está vacío (misma estructura que en shortcodes.php)
        $catalog = [
            '10x10' => ['id'=>'10x10', 'title'=>'10x10 cm', 'price'=>0.35, 'desc'=>'Formato Cuadrado', 'img'=>'https://picsum.photos/400/400', 'ratio'=>'1:1'],
            '10x15' => ['id'=>'10x15', 'title'=>'10x15 cm', 'price'=>0.45, 'desc'=>'Estándar (Jumbo)', 'img'=>'https://picsum.photos/400/600', 'ratio'=>'2:3'],
            'A4'    => ['id'=>'A4',    'title'=>'21x30 cm (A4)', 'price'=>3.50, 'desc'=>'Tamaño Folio', 'img'=>'https://picsum.photos/400/565', 'ratio'=>'21:30']
        ];
    }
    
    $active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'apis';
    ?>
    <div class="wrap">
        <h1>Sistema de Pedidos Fotográficos</h1>
        
        <h2 class="nav-tab-wrapper">
            <a href="?page=carlos-pedidos-settings&tab=apis" class="nav-tab <?php echo $active_tab == 'apis' ? 'nav-tab-active' : ''; ?>">Claves de Pago</a>
            <a href="?page=carlos-pedidos-settings&tab=catalog" class="nav-tab <?php echo $active_tab == 'catalog' ? 'nav-tab-active' : ''; ?>">Gestionar Catálogo</a>
        </h2>

        <?php if ( $active_tab == 'apis' ): ?>
            <form action="options.php" method="post">
                <?php settings_fields( 'ap_settings_group' ); ?>
                <h3>Claves de Stripe</h3>
                <table class="form-table">
                    <tr><th>Clave Pública</th><td><input type="text" name="ap_settings[stripe_public_key]" value="<?php echo esc_attr( $api_options['stripe_public_key'] ?? '' ); ?>" class="regular-text"></td></tr>
                    <tr><th>Clave Secreta</th><td><input type="password" name="ap_settings[stripe_secret_key]" value="<?php echo esc_attr( $api_options['stripe_secret_key'] ?? '' ); ?>" class="regular-text"></td></tr>
                    <tr><th>Webhook Secret</th><td><input type="password" name="ap_settings[stripe_webhook_secret]" value="<?php echo esc_attr( $api_options['stripe_webhook_secret'] ?? '' ); ?>" class="regular-text"></td></tr>
                </table>
                <h3>Claves de Mercado Pago</h3>
                <table class="form-table">
                    <tr><th>Clave Pública</th><td><input type="text" name="ap_settings[mp_public_key]" value="<?php echo esc_attr( $api_options['mp_public_key'] ?? '' ); ?>" class="regular-text"></td></tr>
                    <tr><th>Access Token</th><td><input type="password" name="ap_settings[mp_access_token]" value="<?php echo esc_attr( $api_options['mp_access_token'] ?? '' ); ?>" class="regular-text"></td></tr>
                    <tr><th>Webhook Secret</th><td><input type="password" name="ap_settings[mp_webhook_secret]" value="<?php echo esc_attr( $api_options['mp_webhook_secret'] ?? '' ); ?>" class="regular-text"></td></tr>
                </table>
                <?php submit_button('Guardar Claves'); ?>
            </form>

        <?php elseif ( $active_tab == 'catalog' ): ?>
            <form method="post" action="">
                <?php wp_nonce_field( 'ap_save_catalog_action', 'ap_catalog_nonce' ); ?>
                
                <h3>Productos Disponibles</h3>
                <p>Edita los precios, nombres o imágenes de tus productos aquí. Se actualizarán automáticamente en la web.</p>
                
                <table class="widefat fixed" style="margin-bottom: 20px;">
                    <thead>
                        <tr>
                            <th width="10%">ID (Fijo)</th>
                            <th width="20%">Título</th>
                            <th width="10%">Precio (S/)</th>
                            <th width="20%">Descripción</th>
                            <th width="25%">URL Imagen</th>
                            <th width="10%">Ratio (ej: 1:1)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $i = 0;
                        foreach ( $catalog as $key => $prod ) : 
                            $bg = ($i % 2 == 0) ? '#fff' : '#f9f9f9';
                        ?>
                        <tr style="background: <?php echo $bg; ?>;">
                            <td>
                                <input type="text" name="ap_catalog[<?php echo $i; ?>][id]" value="<?php echo esc_attr($prod['id']); ?>" readonly style="background:#eee; width:100%;">
                            </td>
                            <td>
                                <input type="text" name="ap_catalog[<?php echo $i; ?>][title]" value="<?php echo esc_attr($prod['title']); ?>" style="width:100%;">
                            </td>
                            <td>
                                <input type="number" step="0.01" name="ap_catalog[<?php echo $i; ?>][price]" value="<?php echo esc_attr($prod['price']); ?>" style="width:100%;">
                            </td>
                            <td>
                                <input type="text" name="ap_catalog[<?php echo $i; ?>][desc]" value="<?php echo esc_attr($prod['desc']); ?>" style="width:100%;">
                            </td>
                            <td>
                                <input type="text" name="ap_catalog[<?php echo $i; ?>][img]" value="<?php echo esc_attr($prod['img']); ?>" style="width:100%; font-size:11px;">
                            </td>
                            <td>
                                <input type="text" name="ap_catalog[<?php echo $i; ?>][ratio]" value="<?php echo esc_attr($prod['ratio']); ?>" style="width:100%;">
                            </td>
                        </tr>
                        <?php $i++; endforeach; ?>
                    </tbody>
                </table>
                
                <p class="description">
                    * El <strong>ID</strong> no se puede cambiar para evitar romper pedidos antiguos.<br>
                    * Para cambiar la imagen, pega la URL completa (puedes subir la foto a Medios y copiar el link).
                </p>
                
                <input type="submit" name="ap_save_catalog" id="submit" class="button button-primary" value="Guardar Catálogo">
            </form>
        <?php endif; ?>
    </div>
    <?php
}