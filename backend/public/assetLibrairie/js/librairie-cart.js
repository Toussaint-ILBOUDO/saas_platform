const LibrairieCart = {
    STORAGE_KEY: 'librairie_cart',

    get() {
        return JSON.parse(localStorage.getItem(this.STORAGE_KEY) || '[]');
    },

    save(cart) {
        localStorage.setItem(this.STORAGE_KEY, JSON.stringify(cart));
        this.updateBadge();
    },

    add(id, nom, prix, image, quantite) {
        const qte = Math.max(1, parseInt(quantite) || 1);
        let cart = this.get();
        const existing = cart.find(item => item.produit_id === id);
        if (existing) {
            existing.quantite += qte;
        } else {
            cart.push({ produit_id: id, nom, prix: parseFloat(prix), image: image || '', quantite: qte });
        }
        this.save(cart);
        this.toast(`« ${nom} » ajouté au panier`, 'success');
        return cart;
    },

    remove(index) {
        let cart = this.get();
        if (index >= 0 && index < cart.length) {
            cart.splice(index, 1);
            this.save(cart);
        }
        return cart;
    },

    updateQty(index, delta) {
        let cart = this.get();
        if (index >= 0 && index < cart.length) {
            cart[index].quantite = Math.max(1, cart[index].quantite + delta);
            this.save(cart);
        }
        return cart;
    },

    setQty(index, value) {
        let cart = this.get();
        if (index >= 0 && index < cart.length) {
            cart[index].quantite = Math.max(1, Math.min(99, parseInt(value) || 1));
            this.save(cart);
        }
        return cart;
    },

    clear() {
        this.save([]);
    },

    count() {
        return this.get().length;
    },

    total() {
        return this.get().reduce((sum, item) => sum + (parseFloat(item.prix) * item.quantite), 0);
    },

    updateBadge() {
        const count = this.count();
        const hasItems = count > 0;

        const setBadge = (id) => {
            const el = document.getElementById(id);
            if (!el) return;
            el.textContent = count;
            el.style.display = hasItems ? '' : 'none';
        };
        setBadge('cart-badge');
        setBadge('floating-cart-badge');

        const floatingCart = document.getElementById('lib-floating-cart');
        if (floatingCart) {
            floatingCart.classList.toggle('has-items', hasItems);
        }
    },

    toast(message, type) {
        const container = document.getElementById('toast-container');
        if (!container) return;
        const icons = {
            success: 'bi-check-circle-fill',
            error: 'bi-x-circle-fill',
            info: 'bi-info-circle-fill',
            warning: 'bi-exclamation-triangle-fill'
        };
        const bg = {
            success: 'text-bg-success',
            error: 'text-bg-danger',
            info: 'text-bg-primary',
            warning: 'text-bg-warning'
        };
        const t = type || 'success';
        const id = 't-' + Date.now() + Math.random().toString(36).slice(2, 6);
        container.insertAdjacentHTML('beforeend',
            `<div id="${id}" class="toast ${bg[t] || 'text-bg-success'} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body"><i class="bi ${icons[t] || 'bi-check-circle-fill'} me-2"></i>${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>`
        );
        const el = document.getElementById(id);
        if (el) {
            const bsToast = new bootstrap.Toast(el, { delay: 3500 });
            bsToast.show();
            el.addEventListener('hidden.bs.toast', () => el.remove());
        }
    },

    formatPrix(amount) {
        return new Intl.NumberFormat('fr-FR').format(amount) + ' FCFA';
    },

    renderCart(containerId) {
        const cart = this.get();
        const tbody = document.getElementById(containerId || 'cart-body');
        if (!tbody) return;
        const empty = document.getElementById('cart-empty');
        const itemsDiv = document.getElementById('cart-items');
        const btnCheckout = document.getElementById('btn-checkout');
        const checkoutSection = document.getElementById('checkout-section');

        if (cart.length === 0) {
            if (empty) empty.style.display = '';
            if (itemsDiv) itemsDiv.style.display = 'none';
            if (btnCheckout) btnCheckout.style.display = 'none';
            if (checkoutSection) checkoutSection.style.display = 'none';
            return;
        }

        if (empty) empty.style.display = 'none';
        if (itemsDiv) itemsDiv.style.display = '';
        if (btnCheckout) btnCheckout.style.display = '';

        tbody.innerHTML = '';
        let subtotal = 0;

        cart.forEach((item, index) => {
            const lineTotal = parseFloat(item.prix) * item.quantite;
            subtotal += lineTotal;
            tbody.innerHTML += `
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            ${item.image
                                ? `<img src="${item.image}" alt="${item.nom.replace(/['"]/g, '')}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;" loading="lazy">`
                                : `<div class="bg-light d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; border-radius: 8px;"><i class="bi bi-image text-muted"></i></div>`
                            }
                            <span class="fw-semibold">${item.nom}</span>
                        </div>
                    </td>
                    <td class="text-center">
                        <div class="input-group input-group-sm" style="width: 120px; margin: 0 auto;">
                            <button type="button" class="btn btn-outline-secondary" onclick="LibrairieCart.updateQty(${index}, -1); LibrairieCart.renderCart();" title="Diminuer la quantité"><i class="bi bi-dash"></i></button>
                            <input type="number" class="form-control text-center cart-qty-input" value="${item.quantite}" min="1" max="99" data-index="${index}" onchange="LibrairieCart.setQty(${index}, this.value); LibrairieCart.renderCart();">
                            <button type="button" class="btn btn-outline-secondary" onclick="LibrairieCart.updateQty(${index}, 1); LibrairieCart.renderCart();" title="Augmenter la quantité"><i class="bi bi-plus"></i></button>
                        </div>
                    </td>
                    <td class="text-end">${this.formatPrix(item.prix)}</td>
                    <td class="text-end fw-bold">${this.formatPrix(lineTotal)}</td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="LibrairieCart.remove(${index}); LibrairieCart.renderCart();" title="Supprimer">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        const subtotalEl = document.getElementById('cart-subtotal');
        const totalEl = document.getElementById('cart-total');
        if (subtotalEl) subtotalEl.textContent = this.formatPrix(subtotal);
        if (totalEl) totalEl.textContent = this.formatPrix(subtotal);
    }
};

function initFloatingCartDrag() {
    const cart = document.getElementById('lib-floating-cart');
    if (!cart) return;

    let isDragging = false;
    let startX, startY, origX, origY;
    let moved = false;

    function onStart(e) {
        const touch = e.touches ? e.touches[0] : e;
        isDragging = true;
        moved = false;
        startX = touch.clientX;
        startY = touch.clientY;
        const rect = cart.getBoundingClientRect();
        origX = rect.left;
        origY = rect.top;
        cart.style.left = origX + 'px';
        cart.style.top = origY + 'px';
        cart.style.right = 'auto';
        cart.style.bottom = 'auto';
        cart.style.transition = 'none';
    }

    function onMove(e) {
        if (!isDragging) return;
        const touch = e.touches ? e.touches[0] : e;
        const dx = touch.clientX - startX;
        const dy = touch.clientY - startY;
        if (Math.abs(dx) > 3 || Math.abs(dy) > 3) moved = true;
        cart.style.left = (origX + dx) + 'px';
        cart.style.top = (origY + dy) + 'px';
        e.preventDefault();
    }

    function onEnd() {
        if (!isDragging) return;
        isDragging = false;
        cart.style.transition = '';
        if (!moved) {
            const link = cart.querySelector('.lib-floating-cart-link');
            if (link && link.getAttribute('href')) {
                window.location.href = link.getAttribute('href');
            }
        }
    }

    cart.addEventListener('mousedown', onStart);
    document.addEventListener('mousemove', onMove);
    document.addEventListener('mouseup', onEnd);

    cart.addEventListener('touchstart', onStart, { passive: true });
    document.addEventListener('touchmove', onMove, { passive: false });
    document.addEventListener('touchend', onEnd);
}

document.addEventListener('DOMContentLoaded', () => {
    LibrairieCart.updateBadge();
    initFloatingCartDrag();
});
