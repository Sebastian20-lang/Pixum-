<?php
// includes/shortcodes.php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * SHORTCODE PRINCIPAL: Renderiza el Frontend del Plugin
 */
function ap_shortcode_pedidos_carlos_form() {
    // Scripts y estilos necesarios
    wp_enqueue_script( 'ap-frontend-js' );
    wp_enqueue_style( 'ap-frontend-css' );
    // Librería de iconos Lucide (ligera y moderna)
    wp_enqueue_script( 'lucide-icons', 'https://unpkg.com/lucide@latest', [], null, false );

    ob_start();
    ?>
    
    <div id="pixum-app">
        
        <section id="view-upload">
            
            <h2 class="main-title">Escoge tus fotos</h2>
            
            <div class="upload-container">
                <div class="upload-heading">
                    Insertar fotos aquí para subirlas o<br>bien elegirlas
                </div>

                <div class="options-grid">
                    
                    <div class="option-item">
                        <div class="icon-circle">
                            <div style="position: relative;">
                                <i data-lucide="monitor" color="#9ca3af" stroke-width="1.5" width="64" height="64"></i>
                                <div style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); color: #3b82f6; animation: bounce 2s infinite;">
                                    <i data-lucide="arrow-down" stroke-width="3" width="32" height="32"></i>
                                </div>
                            </div>
                            <span class="deco-star">✦</span>
                            <span class="deco-dot">●</span>
                        </div>
                        <button class="btn-pill" onclick="document.getElementById('global-file-input').click()">
                            De este dispositivo
                        </button>
                    </div>

                    <div class="option-item">
                        <div class="icon-circle">
                            <svg viewBox="0 0 24 24" width="56" height="56" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1)); background:white; border-radius:50%; padding:4px;">
                                <path fill="#EA4335" d="M12 12H6a6 6 0 0 1 6-6v6z" />
                                <path fill="#4285F4" d="M12 12V6a6 6 0 0 1 6 6h-6z" />
                                <path fill="#FBBC04" d="M12 12h6a6 6 0 0 1-6 6v-6z" />
                                <path fill="#34A853" d="M12 12v6a6 6 0 0 1-6-6h6z" />
                            </svg>
                            <span class="deco-star" style="left:10px; top:40px; color:#fdba74; font-size:12px;">✦</span>
                            <span class="deco-dot" style="right:20px; bottom:30px; color:#93c5fd;">✦</span>
                        </div>
                        <button class="btn-pill" onclick="alert('Integración con Google Fotos próximamente.')">
                            Google Fotos
                        </button>
                    </div>

                </div>

                <p class="upload-footer-text">
                    Extensión de archivo .jpeg .jpg .png .heic .heif, Tamaño de archivo < 50MB.<br>
                    Las fotos en la bandeja de entrada se guardan por 21 días.
                </p>
            </div>

        </section>

        <input type="file" id="global-file-input" class="hidden" multiple accept="image/*">
        
        <section id="view-workspace" class="workspace-container hidden">
            
            <div class="workspace-toolbar">
                <div class="toolbar-left">
                    <button class="btn-add-more" onclick="document.getElementById('global-file-input').click()">
                        <i data-lucide="upload" width="16"></i> Subir fotos
                    </button>
                    <span class="toolbar-title">Fotos subidas</span>
                </div>
                
                <div class="toolbar-controls">
                    <div class="control-group">
                        <label class="control-label">Formato</label>
                        <select class="control-select" onchange="appState.updateGlobalConfig('format', this.value)">
                            <option value="Clásico">Formato clásico</option>
                            <option value="Original">Original</option>
                        </select>
                    </div>
                    <div class="control-group">
                        <label class="control-label">Tamaño</label>
                        <select class="control-select" id="global-size-select" onchange="appState.updateGlobalConfig('size', this.value)">
                            <option value="9x13">9x13 cm</option>
                            <option value="10x15" selected>10x15 cm</option>
                            <option value="13x18">13x18 cm</option>
                        </select>
                    </div>
                    <div class="control-group">
                        <label class="control-label">Tipos de papel</label>
                        <select class="control-select" onchange="appState.updateGlobalConfig('paper', this.value)">
                            <option value="Brillante" selected>Brillante</option>
                            <option value="Mate">Mate</option>
                        </select>
                    </div>
                    <div class="control-group">
                        <label class="control-label">Bordes Blancos</label>
                        <select class="control-select" onchange="appState.updateGlobalConfig('border', this.value)">
                            <option value="Sin bordes">sin bordes</option>
                            <option value="Con bordes">con bordes</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="workspace-body">
                
                <aside class="workspace-sidebar">
                    <div class="sidebar-header">
                        <button class="btn-deselect" onclick="appState.toggleAllSelection()">
                            <i data-lucide="check-circle" width="16"></i> Deseleccionar todas
                        </button>
                    </div>
                    <div class="sidebar-scroll">
                        <div id="sidebar-grid" class="sidebar-grid">
                            </div>
                    </div>
                </aside>

                <main class="workspace-stage">
                    <div class="stage-container">
                        <div class="stage-header">
                            <i data-lucide="trash-2" width="20" onclick="appState.removeSelected()"></i>
                        </div>
                        <div id="photo-grid" class="photo-grid">
                            </div>
                    </div>
                </main>
            </div>

            <footer class="workspace-footer">
                <div class="price-info">
                    <p class="price-unit">1 foto: 0,25 €</p>
                    <p class="price-legal">IVA incl., gastos de envío excl.</p>
                </div>
                <button class="btn-add-cart" onclick="appState.navigateTo('CART')">
                    <i data-lucide="shopping-cart" width="18"></i> Añadir al carrito
                </button>
            </footer>

        </section>
            
        <section id="view-cart" class="hidden cart-view-container">
            
            <h1 class="cart-title">Tu carrito</h1>

            <div class="cart-notification">
                <div class="notif-content">
                    <i data-lucide="image" width="24" color="#009fe3"></i>
                    <span>¡Genial! Estos son tus productos personalizados:</span>
                </div>
                <button class="btn-mini-proceed" onclick="appState.navigateTo('CHECKOUT')">Proceder al pago</button>
            </div>

            <div style="background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <div class="cart-table-header">
                    <div class="col-prod">Tu(s) productos(s)</div>
                    <div class="col-price">Precio por ud.</div>
                    <div class="col-qty">Cantidad</div>
                    <div class="col-total">Total</div>
                    <div class="col-del"></div>
                </div>
                
                <div id="cart-items-wrapper">
                    </div>
            </div>

            <div class="cross-sell-section">
                <h3 class="cross-sell-title">Mis fotos más recientes</h3>
                
                <div class="slider-wrapper">
                    <div id="cross-sell-slider" class="cross-sell-slider">
                        </div>
                    
                    <div class="nav-arrow-right" onclick="appState.scrollCrossSell(200)">
                    </div>
                </div>
            </div>

            <div class="cart-summary-box">
                <div class="summary-date">
                    Fecha prevista de envío: <span style="font-weight:400; margin-left:10px;">02.12.25 - 03.12.25</span>
                </div>
                <div class="summary-totals">
                    <div class="summary-row">
                        <span>Importe del carrito:</span>
                        <span id="cart-subtotal-display">0.00 €</span>
                    </div>
                    <div class="summary-row">
                        <span>Gastos de envío:</span>
                        <span>4,99 €</span>
                    </div>
                    <div class="summary-row final">
                        <span>Importe IVA incl.</span>
                        <span id="cart-total-display">0.00 €</span>
                    </div>
                </div>
            </div>

            <div class="cart-actions">
                <button class="btn-gray" onclick="appState.navigateTo('WORKSPACE')">Añadir más productos</button>
                <div class="actions-right">
                    <button class="btn-proceed-large" onclick="appState.navigateTo('CHECKOUT')">Proceder al pago</button>
                </div>
            </div>

        </section>

        <section id="view-checkout" class="hidden checkout-container">
            <h1 class="main-title" style="text-align:left;">Finalizar Pedido</h1>
            
            <div class="checkout-grid">
                
                <div class="checkout-left">
                    <div class="checkout-form-box">
                        <h3 class="form-title"><i data-lucide="user" width="20"></i> Tus Datos</h3>
                        <div class="form-group">
                            <label class="form-label">Correo Electrónico *</label>
                            <input type="email" id="chk-email" class="form-input" placeholder="tu@email.com">
                        </div>
                    </div>

                    <div class="checkout-form-box">
                        <h3 class="form-title"><i data-lucide="map-pin" width="20"></i> Dirección de Envío</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Nombres *</label>
                                <input type="text" id="chk-name" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Apellidos *</label>
                                <input type="text" id="chk-lastname" class="form-input">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Dirección / Calle y Número *</label>
                            <input type="text" id="chk-address" class="form-input" placeholder="Av. Principal 123">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Distrito / Ciudad *</label>
                                <input type="text" id="chk-city" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Referencia</label>
                                <input type="text" id="chk-ref" class="form-input">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="checkout-right">
                    <div class="checkout-summary">
                        <h3 class="form-title">Resumen</h3>
                        <div class="cs-row">
                            <span>Subtotal</span>
                            <span id="chk-subtotal">0.00 €</span>
                        </div>
                        <div class="cs-row">
                            <span>Envío</span>
                            <span id="chk-shipping">4.99 €</span>
                        </div>
                        <div class="cs-total">
                            <span>Total</span>
                            <span id="chk-total">0.00 €</span>
                        </div>

                        <div id="checkout-error" style="color:red; font-size:0.8rem; margin-top:10px; display:none;"></div>

                        <button class="btn-mp-pay" onclick="appState.processPayment()">
                            Pagar con Mercado Pago
                        </button>
                        
                        <p style="font-size:0.75rem; color:#9ca3af; text-align:center; margin-top:1rem;">
                            Serás redirigido a Mercado Pago para completar tu compra de forma segura.
                        </button>
                    </div>
                </div>

            </div>
        </section>
        
        <div id="editor-modal" class="modal-overlay">
            <div class="editor-window">
                
                <div class="editor-preview-area">
                    <img id="editor-preview-img" src="" alt="Previsualización">
                    
                    <button class="btn-close-modal" onclick="appState.closeEditor()">
                        <i data-lucide="x" width="20"></i>
                    </button>
                </div>

                <div class="editor-sidebar-area">
                    
                    <div class="editor-header">
                        <h3 class="editor-title">Editar foto</h3>
                        <button class="btn-close-modal" onclick="appState.closeEditor()" style="position:static; width:28px; height:28px; box-shadow:none; border:1px solid #eee;">
                            <i data-lucide="x" width="16"></i>
                        </button>
                    </div>

                    <div class="editor-tabs">
                        <button class="tab-btn active" data-tab="manual" onclick="appState.switchTab('manual')">
                            Manual
                        </button>
                        <button class="tab-btn" data-tab="ai" onclick="appState.switchTab('ai')">
                            <i data-lucide="wand-2" width="16"></i> AI Magic
                        </button>
                    </div>

                    <div id="panel-manual" class="editor-panel active">
                        <div class="slider-group">
                            <div class="slider-header"><span>Brillo</span> <span class="slider-val" id="val-brightness">100%</span></div>
                            <input type="range" class="orange-slider" id="in-brightness" min="0" max="200" value="100">
                        </div>
                        <div class="slider-group">
                            <div class="slider-header"><span>Contraste</span> <span class="slider-val" id="val-contrast">100%</span></div>
                            <input type="range" class="orange-slider" id="in-contrast" min="0" max="200" value="100">
                        </div>
                        <div class="slider-group">
                            <div class="slider-header"><span>Saturación</span> <span class="slider-val" id="val-saturation">100%</span></div>
                            <input type="range" class="orange-slider" id="in-saturation" min="0" max="200" value="100">
                        </div>
                        <div class="slider-group">
                            <div class="slider-header"><span>Rotación</span> <span class="slider-val" id="val-rotation">0°</span></div>
                            <input type="range" class="orange-slider" id="in-rotation" min="0" max="360" value="0">
                        </div>
                        
                        <button class="btn-reset" onclick="appState.resetFilters()">
                            <i data-lucide="rotate-ccw" width="12"></i> Restablecer todo
                        </button>
                    </div>

                    <div id="panel-ai" class="editor-panel" style="display:none; flex-direction:column; padding:0; height:100%; overflow:hidden;">
                        
                        <div class="ai-chat-container">
                            
                            <div id="chat-history" class="chat-history">
                                <div class="chat-bubble bot">
                                    <div class="bot-icon">
                                        <i data-lucide="bot" width="18" height="18"></i>
                                    </div>
                                    ¡Hola! Soy tu asistente de edición mágica. Describe cómo quieres transformar tu foto y lo haré por ti.
                                </div>
                            </div>
                            
                            <div class="chat-input-wrapper">
                                <div class="chat-input-container">
                                    <input type="text" id="ai-input" class="chat-input" placeholder="Ej: Hazla parecer vintage...">
                                    <button class="btn-send-chat" onclick="appState.sendAiPrompt()">
                                        <i data-lucide="send" width="14" height="14"></i>
                                    </button>
                                </div>
                                <p class="ai-disclaimer">La IA puede cometer errores. Revisa el resultado.</p>
                            </div>

                        </div>
                    </div>

                    <div class="editor-footer">
                        <div class="footer-price">
                            <span class="fp-label">Precio por ud.</span>
                            <span class="fp-amount">$0.29</span>
                        </div>
                        <button class="btn-save-changes" onclick="appState.saveEditor()">
                            <i data-lucide="check" width="18"></i> Guardar
                        </button>
                    </div>

                </div>
            </div>
        </div>

    </div>
    
    <style>
        /* Animación para la flechita del icono */
        @keyframes bounce {
            0%, 100% { transform: translate(-50%, -25%); animation-timing-function: cubic-bezier(0.8,0,1,1); }
            50% { transform: translate(-50%, 0); animation-timing-function: cubic-bezier(0,0,0.2,1); }
        }
    </style>

    <?php
    return ob_get_clean();
}
add_shortcode( 'pedidos_carlos', 'ap_shortcode_pedidos_carlos_form' );