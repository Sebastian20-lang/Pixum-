jQuery(document).ready(function ($) {
    if (typeof lucide !== 'undefined') lucide.createIcons();

    // Configuración de Precios
    const PRICES = {
        '9x13': 0.12, '10x15': 0.15, '13x18': 0.29,
        'keychain': 9.95, 'frame': 12.95, 'magnet': 14.95
    };

    window.appState = {
        photos: [], 
        extraItems: [],
        activePhotoId: null,
        tempFilters: {},
        config: { format: 'Clásico', size: '10x15', paper: 'Brillante', border: 'Sin bordes' },
        
        init: function() {
            $('#global-file-input').on('change', (e) => this.handleFiles(e.target.files));
            
            // Listeners Editor
            ['brightness', 'contrast', 'saturation', 'rotation'].forEach(filter => {
                $(document).on('input', `#in-${filter}`, (e) => {
                    this.tempFilters[filter] = parseInt(e.target.value);
                    $(`#val-${filter}`).text(e.target.value + (filter === 'rotation' ? '°' : '%'));
                    this.updateEditorPreview();
                });
            });
            $(document).on('keypress', '#ai-input', (e) => { if(e.which === 13) this.sendAiPrompt(); });
        },

        navigateTo: function(viewId) {
            if (viewId === 'CHECKOUT') {
                if (this.photos.length === 0 && this.extraItems.length === 0) {
                    alert("El carrito está vacío."); return;
                }
                this.updateCheckoutSummary();
            }

            $('#pixum-app > section').addClass('hidden');
            $(`#view-${viewId.toLowerCase()}`).removeClass('hidden');
            window.scrollTo(0,0);

            if (viewId === 'CART') this.renderCart();
            if (viewId === 'WORKSPACE') this.refreshWorkspace();
            if (typeof lucide !== 'undefined') lucide.createIcons();
        },

        handleFiles: function(files) {
            if (files.length === 0) return;
            Array.from(files).forEach(file => {
                this.photos.push({
                    id: 'p_' + Date.now() + Math.random().toString(36).substr(2, 5),
                    url: URL.createObjectURL(file),
                    file: file,
                    filters: { brightness: 100, contrast: 100, saturation: 100, rotation: 0 },
                    selected: true, qty: 1,
                    size: this.config.size, paper: this.config.paper
                });
            });
            $('#global-file-input').val('');
            this.refreshWorkspace();
            this.navigateTo('WORKSPACE');
        },

        refreshWorkspace: function() {
            this.renderSidebar();
            this.renderGrid();
            if (typeof lucide !== 'undefined') lucide.createIcons();
        },

        // ... (renderSidebar y renderGrid se mantienen igual que la versión anterior) ...
        renderSidebar: function() {
            const container = $('#sidebar-grid'); container.empty();
            this.photos.forEach(photo => {
                const isSel = photo.selected ? 'selected' : '';
                container.append(`<div class="thumb-item ${isSel}" onclick="appState.toggleSelection('${photo.id}')"><img src="${photo.url}" style="filter:${this.getCssFilter(photo.filters)}"><div class="thumb-check"><i data-lucide="check" width="14"></i></div></div>`);
            });
        },
        renderGrid: function() {
            const container = $('#photo-grid'); container.empty();
            const active = this.photos.filter(p => p.selected);
            if (active.length === 0) { container.html('<div style="grid-column:1/-1; text-align:center; padding:4rem; color:#999;">Sin fotos seleccionadas.</div>'); return; }
            active.forEach(photo => {
                photo.size = this.config.size; photo.paper = this.config.paper;
                container.append(`<div class="photo-card"><div class="btn-delete-card" onclick="appState.deletePhoto('${photo.id}')"><i data-lucide="trash-2" width="16"></i></div><div class="pc-img-wrap" onclick="appState.openEditor('${photo.id}')"><img src="${photo.url}" style="filter:${this.getCssFilter(photo.filters)}; transform:rotate(${photo.filters.rotation}deg)"><div class="btn-edit-center"><i data-lucide="pencil" width="16"></i> EDITAR</div></div><div class="pc-details"><div class="pc-info"><h4>FORMATO</h4><p>${photo.size} <span>${photo.paper}</span></p></div><div class="qty-control"><button class="qty-btn" onclick="appState.updateQty('${photo.id}', -1)">-</button><div class="qty-val">${photo.qty}</div><button class="qty-btn" onclick="appState.updateQty('${photo.id}', 1)">+</button></div></div></div>`);
            });
        },

        // --- CARRITO ---
        renderCart: function() {
            const container = $('#cart-items-wrapper'); container.empty();
            let subtotal = 0;

            // Render Fotos
            this.photos.forEach(item => {
                const sizeKey = item.size.replace(' cm', '').trim();
                const uPrice = PRICES[sizeKey] || 0.15;
                const total = item.qty * uPrice;
                subtotal += total;
                container.append(`<div class="cart-item-row"><img src="${item.url}" class="cart-thumb" style="filter:${this.getCssFilter(item.filters)}"><div class="cart-details col-prod"><span class="cart-prod-title">Revelado (${item.size})</span><span class="cart-prod-meta">${item.paper}</span><button class="btn-edit-item" onclick="appState.openEditor('${item.id}')"><i data-lucide="pencil" width="10"></i> Editar</button></div><div class="col-price cart-price-val">${uPrice.toFixed(2)}€</div><div class="col-qty cart-price-val">${item.qty}</div><div class="col-total cart-total-val">${total.toFixed(2)}€</div><div class="col-del"><button class="btn-delete-item" onclick="appState.deletePhoto('${item.id}')"><i data-lucide="trash-2" width="18"></i></button></div></div>`);
            });

            // Render Extras
            this.extraItems.forEach((item, idx) => {
                const total = item.qty * item.price;
                subtotal += total;
                container.append(`<div class="cart-item-row"><img src="${item.img}" class="cart-thumb" style="object-fit:contain; padding:5px;"><div class="cart-details col-prod"><span class="cart-prod-title">${item.name}</span></div><div class="col-price cart-price-val">${item.price.toFixed(2)}€</div><div class="col-qty cart-price-val">${item.qty}</div><div class="col-total cart-total-val">${total.toFixed(2)}€</div><div class="col-del"><button class="btn-delete-item" onclick="appState.deleteExtraItem(${idx})"><i data-lucide="trash-2" width="18"></i></button></div></div>`);
            });

            if(this.photos.length === 0 && this.extraItems.length === 0) container.html('<div style="padding:2rem;text-align:center;">Carrito vacío</div>');

            const total = subtotal + (subtotal > 0 ? 4.99 : 0);
            $('#cart-subtotal-display').text(subtotal.toFixed(2)+' €');
            $('#cart-total-display').text(total.toFixed(2)+' €');
            this.renderCrossSell();
        },

        renderCrossSell: function() {
            const container = $('#cross-sell-slider'); container.empty();
            container.append(`<div class="cs-card"><div class="cs-import-card" onclick="document.getElementById('global-file-input').click()"><div class="cs-plus-circle"><i data-lucide="plus" width="28"></i></div><span class="cs-import-text">Importar fotos</span></div></div>`);
            
            if (this.photos.length > 0) {
                const products = [
                    { id: 'keychain', name: "Llavero personalizado", price: PRICES['keychain'], type: 'keychain' },
                    { id: 'frame', name: "Fotos premium con marco", price: PRICES['frame'], type: 'frame' }
                ];
                products.forEach((prod, i) => {
                    const photo = this.photos[i % this.photos.length];
                    const cls = prod.type === 'frame' ? 'style-frame' : 'style-keychain';
                    container.append(`<div class="cs-card"><div class="cs-img-container"><img src="${photo.url}" class="${cls}"></div><div class="cs-prod-title">${prod.name}</div><div class="cs-price">${prod.price.toFixed(2)} €</div><button class="btn-cs-action" onclick="appState.addExtraItem('${prod.id}', '${prod.name}', ${prod.price}, '${photo.url}')">AÑADIR AL CARRITO</button></div>`);
                });
            }
        },

        addExtraItem: function(id, name, price, img) {
            this.extraItems.push({ id: id+'_'+Date.now(), name, price, img, qty: 1 });
            this.renderCart();
            // Scroll suave arriba
            $('.cart-table-header')[0].scrollIntoView({behavior: "smooth"});
        },
        deleteExtraItem: function(i) { if(confirm('¿Eliminar?')) { this.extraItems.splice(i,1); this.renderCart(); } },

        // --- CHECKOUT & PAGOS ---
        updateCheckoutSummary: function() {
            let subtotal = 0;
            this.photos.forEach(p => { 
                const sk = p.size.replace(' cm','').trim(); 
                subtotal += p.qty * (PRICES[sk]||0.15); 
            });
            this.extraItems.forEach(i => subtotal += i.qty * i.price);
            
            const total = subtotal + 4.99;
            $('#chk-subtotal').text(subtotal.toFixed(2) + ' €');
            $('#chk-total').text(total.toFixed(2) + ' €');
        },

        // LÓGICA DE PAGO REAL
        processPayment: function() {
            const email = $('#chk-email').val();
            const address = $('#chk-address').val();
            const btn = $('.btn-mp-pay');

            if (!email || !address) {
                $('#checkout-error').text('Por favor, completa el email y la dirección.').show();
                return;
            }
            $('#checkout-error').hide();
            btn.prop('disabled', true).text('Procesando...');

            // 1. Preparar Datos
            const formData = new FormData();
            formData.append('action', 'ap_handle_upload'); // Acción WP
            formData.append('ap_nonce', ap_ajax.upload_nonce); // Seguridad
            
            // Datos del pedido
            formData.append('ap_metodo_pago', 'mercadopago');
            formData.append('ap_entrega', 'domicilio');
            formData.append('ap_direccion', address);
            formData.append('ap_distrito', $('#chk-city').val());
            formData.append('ap_referencia', $('#chk-ref').val());
            
            // Adjuntar Archivos Reales
            this.photos.forEach(p => {
                formData.append('ap_fotos[]', p.file); // Archivo crudo
            });

            // Adjuntar Metadata (JSON)
            const cartData = [
                ...this.photos.map(p => ({ type: 'photo', sizeKey: p.size.replace(' cm','').trim(), qty: p.qty, fileName: p.file.name })),
                ...this.extraItems.map(i => ({ type: 'extra', name: i.name, qty: i.qty, price: i.price }))
            ];
            formData.append('ap_cart_data', JSON.stringify(cartData));

            // 2. Enviar a WordPress
            $.ajax({
                url: ap_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false, contentType: false,
                success: (res) => {
                    if (res.success) {
                        // 3. Crear Preferencia Mercado Pago
                        this.createMpPreference(res.data.order_id);
                    } else {
                        alert('Error al crear pedido: ' + res.data.message);
                        btn.prop('disabled', false).text('Pagar con Mercado Pago');
                    }
                },
                error: (err) => {
                    console.error(err);
                    alert('Error de conexión con el servidor.');
                    btn.prop('disabled', false).text('Pagar con Mercado Pago');
                }
            });
        },

        createMpPreference: function(orderId) {
            $.ajax({
                url: ap_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ap_create_mercadopago_session',
                    ap_nonce: ap_ajax.upload_nonce,
                    order_id: orderId
                },
                success: (res) => {
                    if (res.success) {
                        // 4. REDIRECCIÓN A MERCADO PAGO
                        window.location.href = res.data.init_point;
                    } else {
                        alert('Error Mercado Pago: ' + res.data.message);
                        $('.btn-mp-pay').prop('disabled', false).text('Pagar con Mercado Pago');
                    }
                }
            });
        },

        // --- UTILIDADES ---
        getCssFilter: function(f) { return `brightness(${f.brightness}%) contrast(${f.contrast}%) saturate(${f.saturation}%)`; },
        toggleSelection: function(id) { const p = this.photos.find(x => x.id === id); if(p) { p.selected = !p.selected; this.refreshWorkspace(); } },
        toggleAllSelection: function() { const all = this.photos.every(p => p.selected); this.photos.forEach(p => p.selected = !all); this.refreshWorkspace(); },
        updateQty: function(id, d) { const p = this.photos.find(x => x.id === id); if(p && p.qty+d>0) { p.qty+=d; this.refreshWorkspace(); } },
        deletePhoto: function(id) { if(confirm('¿Eliminar?')) { this.photos=this.photos.filter(p=>p.id!==id); $('#view-cart').hasClass('hidden') ? this.refreshWorkspace() : this.renderCart(); } },
        updateGlobalConfig: function(k, v) { this.config[k]=v; if(k==='size'||k==='paper') this.photos.forEach(p=>p[k]=v); this.renderGrid(); },
        
        // Editor
        openEditor: function(id) {
            this.activePhotoId=id; const p=this.photos.find(x=>x.id===id); this.tempFilters={...p.filters};
            $('#editor-preview-img').attr('src', p.url); this.updateEditorPreview();
            Object.keys(this.tempFilters).forEach(k=>{$(`#in-${k}`).val(this.tempFilters[k]); $(`#val-${k}`).text(this.tempFilters[k]+(k==='rotation'?'°':'%'));});
            $('#editor-modal').addClass('active'); this.switchTab('manual');
            if(typeof lucide!=='undefined') lucide.createIcons();
        },
        closeEditor: function() { $('#editor-modal').removeClass('active'); this.activePhotoId=null; },
        updateEditorPreview: function() { const f=this.tempFilters; $('#editor-preview-img').css({'filter':`brightness(${f.brightness}%) contrast(${f.contrast}%) saturate(${f.saturation}%)`,'transform':`rotate(${f.rotation}deg)`}); },
        switchTab: function(t) { $('.tab-btn').removeClass('active'); $(`.tab-btn[data-tab="${t}"]`).addClass('active'); $('.editor-panel').hide(); if(t==='manual')$('#panel-manual').show(); else $('#panel-ai').css('display','flex'); },
        resetFilters: function() { this.tempFilters={brightness:100,contrast:100,saturation:100,rotation:0}; this.updateEditorPreview(); Object.keys(this.tempFilters).forEach(k=>{$(`#in-${k}`).val(this.tempFilters[k]); $(`#val-${k}`).text(this.tempFilters[k]+(k==='rotation'?'°':'%'));}); },
        saveEditor: function() { if(!this.activePhotoId)return; const p=this.photos.find(x=>x.id===this.activePhotoId); p.filters={...this.tempFilters}; $('#view-cart').hasClass('hidden')?this.refreshWorkspace():this.renderCart(); this.closeEditor(); },
        sendAiPrompt: function() {
            const v=$('#ai-input').val().trim(); if(!v)return;
            $('#chat-history').append(`<div class="chat-bubble" style="background:#ea580c;color:white;align-self:flex-end;margin-left:auto;border-top-right-radius:0;margin-bottom:10px;padding:1rem;">${v}</div>`); $('#ai-input').val('');
            setTimeout(()=>{$('#chat-history').append(`<div class="chat-bubble bot"><div class="bot-icon"><i data-lucide="bot" width="18"></i></div>He aplicado "${v}".</div>`); this.tempFilters.contrast=115; this.updateEditorPreview(); if(typeof lucide!=='undefined') lucide.createIcons();}, 1000);
        }
    };

    window.appState.init();
});