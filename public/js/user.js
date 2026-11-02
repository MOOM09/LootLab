(() => {
    'use strict';

    const body = document.body;
    const appUrl = body?.dataset.userAppUrl || '';
    const liveRegion = document.querySelector('[data-user-live-region]');
    let liveRegionTimer;

    const announce = (message, state = 'info') => {
        if (!liveRegion || !message) return;
        liveRegion.textContent = message;
        liveRegion.dataset.state = state;
        liveRegion.classList.add('user-is-visible');
        window.clearTimeout(liveRegionTimer);
        if (state !== 'error') {
            liveRegionTimer = window.setTimeout(
                () => liveRegion.classList.remove('user-is-visible'),
                3600
            );
        }
    };

    const parseResponse = async (response) => {
        const contentType = response.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
            throw new Error('The server returned an unexpected response.');
        }
        return response.json();
    };

    const navbar = document.querySelector('[data-user-navbar]');
    if (navbar) {
        const updateNavbar = () => navbar.classList.toggle('user-is-scrolled', window.scrollY > 8);
        updateNavbar();
        window.addEventListener('scroll', updateNavbar, { passive: true });
    }

    const menuToggle = document.querySelector('[data-user-menu-toggle]');
    const nav = document.querySelector('[data-user-nav]');
    if (menuToggle && nav) {
        const closeMenu = (restoreFocus = false) => {
            nav.classList.remove('user-is-open');
            menuToggle.setAttribute('aria-expanded', 'false');
            if (restoreFocus) menuToggle.focus();
        };
        menuToggle.addEventListener('click', () => {
            const isOpen = menuToggle.getAttribute('aria-expanded') === 'true';
            menuToggle.setAttribute('aria-expanded', String(!isOpen));
            nav.classList.toggle('user-is-open', !isOpen);
            if (isOpen) menuToggle.focus();
        });
        nav.addEventListener('click', (event) => {
            if (event.target.closest('a')) closeMenu();
        });
        document.addEventListener('click', (event) => {
            if (!nav.contains(event.target) && !menuToggle.contains(event.target)) closeMenu();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                document.querySelectorAll('[data-user-account-menu][open]').forEach((menu) => {
                    menu.open = false;
                });
                if (menuToggle.getAttribute('aria-expanded') === 'true') closeMenu(true);
            }
        });
    }

    // Close the account dropdown on an outside click or Escape.
    // Kept independent of the mobile navigation so it works on desktop too.
    const accountMenus = document.querySelectorAll('[data-user-account-menu]');

    document.addEventListener('pointerdown', (event) => {
        accountMenus.forEach((menu) => {
            if (menu.open && !menu.contains(event.target)) {
                menu.open = false;
            }
        });
    });

    accountMenus.forEach((menu) => {
        menu.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && menu.open) {
                event.preventDefault();
                menu.open = false;
                menu.querySelector('summary')?.focus();
            }
        });

        menu.addEventListener('toggle', () => {
            if (!menu.open) return;
            accountMenus.forEach((other) => {
                if (other !== menu) other.open = false;
            });
        });
    });

    document.querySelectorAll('[data-user-unavailable]').forEach((element) => {
        element.addEventListener('click', (event) => {
            event.preventDefault();
            announce(element.dataset.userUnavailable, 'error');
        });
    });

    // Account dropdown preferences: POST is CSRF-protected, persisted to PHP session + cookie.
    document.querySelectorAll('[data-loot-pref]').forEach((button) => {
        button.addEventListener('click', async () => {
            const type = button.dataset.lootPref;
            const value = button.dataset.lootValue;
            if (button.getAttribute('aria-pressed') === 'true') return;
            const previous = document.documentElement.dataset.lootTheme;
            if (type === 'theme') document.documentElement.dataset.lootTheme = value;
            button.disabled = true;
            try {
                const body = new URLSearchParams({
                    type, value, csrf_token: document.body.dataset.userCsrf || ''
                });
                const response = await fetch(document.body.dataset.userPreferences, {
                    method: 'POST', credentials: 'same-origin',
                    headers: { Accept: 'application/json' }, body
                });
                const result = await parseResponse(response);
                if (!response.ok || !result.success) throw new Error(result.message || 'Could not save your preference.');
                if (type === 'lang') {
                    location.reload();
                    return;
                }
                document.body.dataset.lootTheme = value;
                document.querySelectorAll(`[data-loot-pref="${type}"]`).forEach((item) => {
                    item.setAttribute('aria-pressed', String(item.dataset.lootValue === value));
                });
                announce('Appearance saved.', 'success');
            } catch (error) {
                if (type === 'theme') document.documentElement.dataset.lootTheme = previous;
                announce(error.message || 'Could not save your preference.', 'error');
            } finally { button.disabled = false; }
        });
    });
    const lootT = (message) => window.LootI18n?.t(message) || message;

    // All catalog filter and pagination state lives in catalog-filter-fix.js.
    document.querySelectorAll('[data-user-logout]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!window.confirm(lootT('Are you sure you want to log out?'))) return;
            const button = form.querySelector('button[type="submit"]');
            if (button) button.disabled = true;
            try {
                const response = await fetch(form.action, { method: 'POST', headers: { Accept: 'application/json' }, credentials: 'same-origin', body: new URLSearchParams(new FormData(form)) });
                const result = await parseResponse(response);
                if (!response.ok || !result.success) throw new Error(result.message || 'Could not log out.');
                location.href = result.data?.redirect || appUrl + '/login';
            } catch (e) {
                announce(e.message || 'Could not log out.', 'error');
                if (button) button.disabled = false;
            }
        });
    });
    const loote = document.querySelector('[data-loote-assistant]');
    if (loote) {
        const greeting = loote.querySelector('[data-loote-greeting]');
        if (document.body.dataset.looteLanding === 'true') {
            const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (!reducedMotion) {
                const cloud = loote.querySelector('.loote-face');
                loote.classList.add('loote-arriving');

                // Follow the actual animated cloud position. Each ember remains
                // behind Loote and fades separately, rather than moving as a group.
                let flightStart = null;
                let lastSpark = -100;
                const flightDuration = 2250;
                const scatterSpark = (rect) => {
                    for (let i = 0; i < 2; i++) {
                        const ember = document.createElement('span');
                        ember.className = 'loote-trail-particle';
                        const x = rect.left + rect.width * 0.18 + (Math.random() - 0.5) * 24;
                        const y = rect.top + rect.height * 0.33 + (Math.random() - 0.5) * 24;
                        ember.style.left = x + 'px';
                        ember.style.top = y + 'px';
                        ember.style.setProperty('--trail-dx', (-24 - Math.random() * 35) + 'px');
                        ember.style.setProperty('--trail-dy', (-18 - Math.random() * 30) + 'px');
                        ember.style.animationDuration = (550 + Math.random() * 350) + 'ms';
                        document.body.appendChild(ember);
                        ember.addEventListener('animationend', () => ember.remove(), { once: true });
                    }
                };
                const animateTrail = (timestamp) => {
                    if (flightStart === null) flightStart = timestamp;
                    const elapsed = timestamp - flightStart;
                    if (elapsed < flightDuration) {
                        if (elapsed - lastSpark >= 65 && cloud) {
                            scatterSpark(cloud.getBoundingClientRect());
                            lastSpark = elapsed;
                        }
                        window.requestAnimationFrame(animateTrail);
                    }
                };
                if (cloud) window.requestAnimationFrame(animateTrail);
            }
            window.setTimeout(() => { if (greeting) greeting.hidden = false; }, reducedMotion ? 0 : 2400);
        }
        let looteDismissed = false;
        loote.querySelector('[data-loote-dismiss]')?.addEventListener('click', () => {
            looteDismissed = true;
            if (greeting) greeting.hidden = true;
        });
        const motionAllowed = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const cloud = loote.querySelector('.loote-face');
        if (cloud && motionAllowed) {
            const phrases = [
                ['MY NAME IS LOOTE!', 'CAN I HELP YOU?'],
                ['NEED A GIFT?', 'LET ME FIND SOMETHING COOL!'],
                ['HEY, GAMER!', 'ASK ME ABOUT OUR PRODUCTS!'],
            ];
            let turn = 0;
            // One bounded flight every ~18s; never obstructs clicks or the product cards.
            window.setTimeout(() => {
                loote.classList.remove('loote-arriving');
                window.setInterval(() => {
                    if (document.hidden || !cloud.isConnected) return;
                    cloud.animate([
                        { transform: 'translate(0,0) rotate(0deg)' },
                        { transform: 'translate(-65px,-85px) rotate(-12deg)', offset: .32 },
                        { transform: 'translate(-130px,-38px) rotate(10deg)', offset: .69 },
                        { transform: 'translate(0,0) rotate(0deg)' },
                    ], { duration: 3200, easing: 'ease-in-out' });
                    if (greeting && !looteDismissed) {
                        turn = (turn + 1) % phrases.length;
                        const strong = greeting.querySelector('strong');
                        const subtitle = greeting.querySelector('span');
                        if (strong) strong.textContent = phrases[turn][0];
                        if (subtitle) subtitle.textContent = phrases[turn][1];
                        greeting.hidden = false;
                    }
                }, 18000);
            }, 3000);
        }
    }

    const productCards = document.querySelectorAll('[data-user-product-card]');
    if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        const observer = new IntersectionObserver((entries, currentObserver) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('user-is-visible');
                    currentObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.08 });
        productCards.forEach((card, index) => {
            card.classList.add('user-reveal');
            card.style.transitionDelay = `${(index % 8) * 70}ms`;
            observer.observe(card);
        });
    }

    document.querySelectorAll('[data-user-product-image]').forEach((image) => {
        image.addEventListener('error', () => {
            image.hidden = true;
            const fallback = image.parentElement?.querySelector('[data-user-image-fallback]');
            if (fallback) fallback.hidden = false;
        }, { once: true });
    });

    const favoriteButtons = Array.from(document.querySelectorAll('[data-user-favorite-button]'));
    const favoriteCount = document.querySelector('[data-user-favorite-count]');
    if (favoriteButtons.length || favoriteCount) {
        let csrfToken = '';
        let favoriteIds = new Set();
        let favoritesLoaded = false;
        let favoritesRequest;
        const pendingFavoriteIds = new Set();
        const favoriteUrl = `${appUrl}/favorites`;
        const favoritesPage = document.querySelector('[data-favorites-list]');
        const favoritesStatus = document.querySelector('[data-favorites-status]');
        const setFavoriteState = (button, isFavorite) => {
            button.setAttribute('aria-pressed', String(isFavorite));
            button.textContent = isFavorite ? '♥' : '♡';
            button.setAttribute(
                'aria-label',
                isFavorite ? 'Remove from favorites' : 'Add to favorites'
            );
        };
        const updateCount = () => {
            if (!favoriteCount) return;
            favoriteCount.textContent = String(favoriteIds.size);
            favoriteCount.hidden = favoriteIds.size === 0;
        };
        const loadFavorites = async () => {
            if (favoritesLoaded) return true;
            if (!favoritesRequest) {
                favoritesRequest = (async () => {
                    const response = await fetch(favoriteUrl, {
                        headers: { Accept: 'application/json' },
                        credentials: 'same-origin'
                    });
                    const result = await parseResponse(response);
                    if (!response.ok || !result.success || !result.data) {
                        throw new Error(result.message || 'Could not load favorites.');
                    }
                    csrfToken = result.data.csrf_token || '';
                    favoriteIds = new Set((result.data.items || []).map((item) => String(item.product_id)));
                    favoritesLoaded = true;
                    favoriteButtons.forEach((button) => setFavoriteState(
                        button,
                        favoriteIds.has(String(button.dataset.productId))
                    ));
                    updateCount();
                    return true;
                })().catch((error) => {
                    if (favoritesStatus) {
                        favoritesStatus.textContent = error.message || 'Could not load favorites.';
                        favoritesStatus.dataset.state = 'error';
                    }
                    announce(error.message || 'Could not load favorites.', 'error');
                    return false;
                }).finally(() => {
                    favoritesRequest = null;
                });
            }
            return favoritesRequest;
        };
        favoriteButtons.forEach((button) => {
            button.addEventListener('click', async () => {
                if (button.disabled) return;
                const productId = String(button.dataset.productId || '');
                if (!productId || pendingFavoriteIds.has(productId)) return;
                pendingFavoriteIds.add(productId);
                favoriteButtons
                    .filter((item) => item.dataset.productId === productId)
                    .forEach((item) => { item.disabled = true; });
                try {
                    if (!csrfToken && !(await loadFavorites())) return;
                    if (!csrfToken) {
                        throw new Error('The favorites security token is missing. Reload the page and try again.');
                    }
                    const wasFavorite = favoriteIds.has(productId);
                    if (wasFavorite && !window.confirm(lootT('Are you sure you want to remove this product from favorites?'))) return;
                    const response = await fetch(favoriteUrl, {
                        method: wasFavorite ? 'DELETE' : 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json'
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            csrf_token: csrfToken,
                            product_id: productId
                        })
                    });
                    const result = await parseResponse(response);
                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Could not update favorites.');
                    }
                    if (wasFavorite) favoriteIds.delete(productId);
                    else favoriteIds.add(productId);
                    favoriteButtons
                        .filter((item) => item.dataset.productId === productId)
                        .forEach((item) => {
                            setFavoriteState(item, !wasFavorite);
                            item.classList.remove('user-is-pulsing');
                            void item.offsetWidth;
                            item.classList.add('user-is-pulsing');
                        });
                    updateCount();
                    if (favoritesPage && wasFavorite) {
                        const card = favoritesPage.querySelector(
                            `[data-favorite-product="${CSS.escape(productId)}"]`
                        );
                        card?.remove();
                        if (!favoritesPage.querySelector('[data-favorite-product]')) {
                            const emptyState = document.createElement('div');
                            emptyState.className = 'user-empty-state';
                            emptyState.dataset.favoritesEmpty = '';
                            const icon = document.createElement('span');
                            icon.className = 'user-empty-icon';
                            icon.setAttribute('aria-hidden', 'true');
                            icon.textContent = '♡';
                            const title = document.createElement('h2');
                            title.textContent = 'No favorites saved yet';
                            const description = document.createElement('p');
                            description.textContent = 'Save a product from the collection and it will appear here.';
                            const browseLink = document.createElement('a');
                            browseLink.className = 'user-button-secondary';
                            browseLink.href = `${appUrl}/products`;
                            browseLink.textContent = 'Browse products';
                            emptyState.append(icon, title, description, browseLink);
                            favoritesPage.appendChild(emptyState);
                        }
                    }
                    announce(result.message || (wasFavorite ? 'Removed from favorites.' : 'Added to favorites.'), 'success');
                } catch (error) {
                    announce(error.message || 'Could not update favorites.', 'error');
                } finally {
                    pendingFavoriteIds.delete(productId);
                    favoriteButtons
                        .filter((item) => item.dataset.productId === productId)
                        .forEach((item) => { item.disabled = false; });
                }
            });
        });
        loadFavorites();
    }

    const cartUrl = `${appUrl}/cart`;
    const cartPage = document.querySelector('[data-user-cart]');
    const cartAddButtons = Array.from(document.querySelectorAll('[data-user-add-to-cart]'));
    const cartCounts = Array.from(document.querySelectorAll('[data-user-cart-count]'));
    const updateCartCount = (data) => {
        const count = (data.items || []).reduce((sum, item) => sum + Number(item.quantity || 0), 0);
        cartCounts.forEach((badge) => {
            badge.textContent = String(count);
            badge.hidden = count === 0;
        });
    };
    let cartCsrfToken = '';
    let cartDataRequest;

    const loadCartData = async () => {
        if (!cartDataRequest) {
            cartDataRequest = (async () => {
                const response = await fetch(cartUrl, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin'
                });
                const result = await parseResponse(response);
                if (!response.ok || !result.success || !result.data) {
                    throw new Error(result.message || 'Could not load your cart.');
                }
                if (!result.data.csrf_token) {
                    throw new Error('The cart security token is missing. Reload the page and try again.');
                }
                cartCsrfToken = result.data.csrf_token;
                updateCartCount(result.data);
                return result.data;
            })().finally(() => {
                cartDataRequest = null;
            });
        }
        return cartDataRequest;
    };

    const updateCart = async (method, values) => {
        if (!cartCsrfToken) await loadCartData();
        const response = await fetch(cartUrl + (
            method === 'POST' ? '/add' : method === 'PUT' ? '/update' : '/remove'
        ), {
            method,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                ...values,
                csrf_token: cartCsrfToken
            })
        });
        const result = await parseResponse(response);
        if (!response.ok || !result.success) {
            throw new Error(result.message || 'The cart request could not be completed.');
        }
        // Refresh the actual quantities, including additions capped by stock.
        loadCartData().catch(() => {});
        return result;
    };

    if (cartCounts.length && !cartPage) loadCartData().catch(() => {});

    document.addEventListener('loot:cart-added', (event) => {
        const button = event.detail?.button;
        if (button) button.classList.add('user-cart-added');
        announce(event.detail?.message || 'Product added to cart.', 'success');
        loadCartData().catch(() => {});
        document.querySelectorAll('.user-nav-cart').forEach((link) => {
            link.classList.remove('user-cart-confirmed');
            void link.offsetWidth;
            link.classList.add('user-cart-confirmed');
            window.setTimeout(() => link.classList.remove('user-cart-confirmed'), 500);
        });
    });

    cartAddButtons.forEach((button) => {
        button.addEventListener('click', async () => {
            if (button.disabled) return;
            const productId = Number(button.dataset.productId);
            if (!Number.isInteger(productId) || productId < 1) {
                announce('This product could not be added because its ID is invalid.', 'error');
                return;
            }

            const originalText = button.textContent;
            button.disabled = true;
            button.textContent = 'Adding…';
            button.setAttribute('aria-busy', 'true');
            let added = false;
            try {
                const result = await updateCart('POST', {
                    product_id: productId,
                    quantity: 1
                });
                document.dispatchEvent(new CustomEvent('loot:cart-added', {
                    detail: { button, message: result.message }
                }));
                added = true;
                button.textContent = lootT('✓ Added to cart');
            } catch (error) {
                announce(error.message || 'The product could not be added to your cart.', 'error');
            } finally {
                button.removeAttribute('aria-busy');
                const reset = () => {
                    button.disabled = false;
                    button.textContent = originalText;
                    button.classList.remove('user-cart-added');
                };
                if (added) window.setTimeout(reset, 1400);
                else reset();
            }
        });
    });

    if (cartPage) {
        const cartStatus = cartPage.querySelector('[data-cart-status]');
        const emptyState = cartPage.querySelector('[data-cart-empty]');
        const itemsContainer = cartPage.querySelector('[data-cart-items]');
        const summary = cartPage.querySelector('[data-cart-summary]');
        const subtotalTarget = cartPage.querySelector('[data-cart-subtotal]');
        const discountTarget = cartPage.querySelector('[data-cart-discount]');
        const totalTarget = cartPage.querySelector('[data-cart-total]');
        const pendingItems = new Set();
        const money = (value) => Number(value || 0).toFixed(2);
        const showCartStatus = (message, state = '') => {
            if (!cartStatus) return;
            cartStatus.textContent = message;
            if (state) cartStatus.dataset.state = state;
            else delete cartStatus.dataset.state;
            cartStatus.hidden = message === '';
        };

      
const renderCart = (data) => {
    const items = Array.isArray(data.items) ? data.items : [];

    itemsContainer?.replaceChildren();

    if (emptyState) {
        emptyState.hidden = items.length !== 0;
    }

    if (summary) {
        summary.hidden = items.length === 0;
    }

    items.forEach((item) => {
        const row = document.createElement('article');
        row.className = 'user-cart-item';
        row.dataset.cartItemId = String(item.id);

        // Product information
        const productInfo = document.createElement('div');
        productInfo.className = 'cart-product-info';

        // Product image
        const image = document.createElement('img');
        image.className = 'cart-product-image';
        image.alt = String(item.name || 'Product');
        image.loading = 'lazy';

        
const imagePath = String(item.image || '').trim();

if (imagePath) {
    // Use the same image path as the Products page.
    image.src = imagePath;
} else {
    image.hidden = true;
}

image.onerror = () => {
    image.hidden = true;
};


        productInfo.appendChild(image);

        // Product details
        const details = document.createElement('div');
        details.className = 'cart-product-details';

        const title = document.createElement('h2');
        const productLink = document.createElement('a');

        productLink.href =
            `${appUrl}/products/show?id=${encodeURIComponent(item.product_id)}`;

        productLink.textContent = String(item.name || 'Product');

        title.appendChild(productLink);
        details.appendChild(title);

        // AI design link
        if (item.custom_design_id) {
            const designLink = document.createElement('a');

            designLink.href =
                `${appUrl}/aiImage.php?design_id=${encodeURIComponent(item.custom_design_id)}`;

            designLink.textContent = `✦ Your AI design #${item.custom_design_id}`;
            designLink.className = 'loothub-cart-design-link';

            details.appendChild(designLink);
        }

        const unitPrice = document.createElement('p');
        unitPrice.textContent = `Unit price: ${money(item.price)}`;
        details.appendChild(unitPrice);

        const lineTotal = document.createElement('p');
        lineTotal.textContent = `Line total: ${money(item.line_total)}`;
        details.appendChild(lineTotal);

        productInfo.appendChild(details);
        row.appendChild(productInfo);

        // Cart controls
        const controls = document.createElement('div');
        controls.className = 'user-cart-item-controls';

        const updateForm = document.createElement('form');
        updateForm.dataset.cartUpdateForm = '';

        const quantityLabel = document.createElement('label');
        quantityLabel.textContent = 'Quantity';

        const quantityInput = document.createElement('input');
        quantityInput.type = 'number';
        quantityInput.name = 'quantity';
        quantityInput.min = '1';
        quantityInput.step = '1';
        quantityInput.required = true;
        quantityInput.value = String(item.quantity);

        quantityLabel.appendChild(quantityInput);
        updateForm.appendChild(quantityLabel);

        const updateButton = document.createElement('button');
        updateButton.className = 'user-button-secondary';
        updateButton.type = 'submit';
        updateButton.dataset.cartAction = 'update';
        updateButton.textContent = 'Update';

        updateForm.appendChild(updateButton);
        controls.appendChild(updateForm);

        const removeButton = document.createElement('button');
        removeButton.className = 'user-button-quiet';
        removeButton.type = 'button';
        removeButton.dataset.cartAction = 'remove';
        removeButton.textContent = 'Remove';

        controls.appendChild(removeButton);
        row.appendChild(controls);

        itemsContainer?.appendChild(row);
    });

    // Order totals
    if (data.totals) {
        if (subtotalTarget) {
            subtotalTarget.textContent = money(data.totals.subtotal);
        }

        if (discountTarget) {
            discountTarget.textContent = money(data.totals.discount);
        }

        if (totalTarget) {
            totalTarget.textContent = money(data.totals.total);
        }
    }
};

        const refreshCart = async (showLoading = true) => {
            if (showLoading) showCartStatus('Loading your cart…', 'loading');
            try {
                const data = await loadCartData();
                renderCart(data);
                showCartStatus('');
            } catch (error) {
                showCartStatus(error.message || 'Your cart could not be loaded.', 'error');
                announce(error.message || 'Your cart could not be loaded.', 'error');
            }
        };

        const performCartAction = async (row, method) => {
            const itemId = Number(row.dataset.cartItemId);
            if (!Number.isInteger(itemId) || itemId < 1 || pendingItems.has(itemId)) return;

            const payload = { item_id: itemId };
            if (method === 'PUT') {
                const quantityInput = row.querySelector('[name="quantity"]');
                const quantity = Number(quantityInput?.value);
                if (!Number.isInteger(quantity) || quantity < 1) {
                    showCartStatus('Enter a quantity of at least 1.', 'error');
                    return;
                }
                payload.quantity = quantity;
            }

            pendingItems.add(itemId);
            row.querySelectorAll('button, input').forEach((control) => {
                control.disabled = true;
            });
            try {
                const result = await updateCart(method, payload);
                announce(result.message || (
                    method === 'PUT' ? 'Cart quantity updated.' : 'Cart item removed.'
                ), 'success');
                await refreshCart(false);
            } catch (error) {
                showCartStatus(error.message || 'The cart could not be updated.', 'error');
                announce(error.message || 'The cart could not be updated.', 'error');
            } finally {
                pendingItems.delete(itemId);
                row.querySelectorAll('button, input').forEach((control) => {
                    control.disabled = false;
                });
            }
        };

        itemsContainer?.addEventListener('submit', (event) => {
            const form = event.target.closest('[data-cart-update-form]');
            if (!form) return;
            event.preventDefault();
            const row = form.closest('[data-cart-item-id]');
            if (row) performCartAction(row, 'PUT');
        });
        itemsContainer?.addEventListener('click', (event) => {
            const button = event.target.closest('[data-cart-action="remove"]');
            if (!button || button.disabled) return;
            if (!window.confirm(lootT('Are you sure you want to remove this item from your cart?'))) return;
            const row = button.closest('[data-cart-item-id]');
            if (row) performCartAction(row, 'DELETE');
        });

        refreshCart();
    }

    const setFormMessage = (form, message, state = '') => {
        const target = form.querySelector('[data-user-form-message]');
        if (!target) return;
        target.textContent = message;
        if (state) target.dataset.state = state;
        else delete target.dataset.state;
    };

    const submitAsyncForm = async (form, options = {}) => {
        const button = form.querySelector('[data-user-submit]');
        if (button?.disabled) return;
        const method = (form.dataset.userMethod || form.method || 'POST').toUpperCase();
        const formData = new FormData(form);
        const headers = { Accept: 'application/json' };
        let body;
        if (form.dataset.userJson === 'true') {
            headers['Content-Type'] = 'application/json';
            body = JSON.stringify(Object.fromEntries(formData.entries()));
        } else {
            body = new URLSearchParams(formData);
        }

        const confirmation = options.confirmMessage || (form.id === 'logout-form' ? lootT('Are you sure you want to log out?') : (method === 'DELETE' ? lootT('Are you sure you want to delete this?') : ''));
        if (confirmation && !window.confirm(lootT(confirmation))) return;
        if (button) {
            button.disabled = true;
            button.dataset.originalText = button.textContent;
            button.textContent = 'Please wait…';
        }
        setFormMessage(form, 'Sending your request…', 'loading');

        try {
            const response = await fetch(form.action, {
                method,
                headers,
                body,
                credentials: 'same-origin'
            });
            const result = await parseResponse(response);
            if (!response.ok || !result.success) {
                const details = result.errors
                    ? Object.values(result.errors).flat().join(' ')
                    : result.message;
                throw new Error(details || 'The request could not be completed.');
            }
            const successMessage = result.message || form.dataset.userSuccess || 'Done.';
            setFormMessage(form, successMessage, 'success');
            announce(successMessage, 'success');
            if (options.onSuccess) await options.onSuccess(result);
            const redirect = result.data?.redirect || form.dataset.userRedirect;
            if (redirect) {
                window.location.assign(redirect);
                return;
            }
            if (form.dataset.userResetOnSuccess === 'true') form.reset();
            if (form.dataset.userAutoHide === 'true') {
                window.setTimeout(() => setFormMessage(form, ''), 3600);
            }
        } catch (error) {
            const message = error.message || 'Unable to complete the request. Please try again.';
            setFormMessage(form, message, 'error');
            announce(message, 'error');
        } finally {
            if (button) {
                button.disabled = false;
                button.textContent = button.dataset.originalText || 'Submit';
            }
        }
    };

    document.querySelectorAll('[data-user-async-form]:not([data-user-review-form])').forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            submitAsyncForm(form, {
                confirmMessage: form.dataset.userConfirm || ''
            });
        });
    });

    const reviewSection = document.querySelector('[data-user-review-section]');
    if (reviewSection) {
        const reviewsUrl = reviewSection.dataset.reviewsUrl;
        const list = reviewSection.querySelector('[data-user-review-list]');
        const status = reviewSection.querySelector('[data-user-review-status]');
        const reviewForm = reviewSection.querySelector('[data-user-review-form]');
        let reviewCsrfToken = '';

        const loadReviews = async () => {
            if (!reviewsUrl || !list) return;
            if (status) status.textContent = 'Loading reviews…';
            try {
                const response = await fetch(reviewsUrl, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin'
                });
                const result = await parseResponse(response);
                if (!response.ok || !result.success || !result.data) {
                    throw new Error(result.message || 'Reviews could not be loaded.');
                }
                reviewCsrfToken = result.data.csrf_token || '';
                const canReview = result.data.can_review === true;
                if (reviewForm) reviewForm.hidden = !canReview;
                const eligibility = reviewSection.querySelector('[data-user-review-eligibility]');
                if (eligibility) eligibility.hidden = canReview || !reviewForm;
                const tokenInput = reviewForm?.querySelector('[name="csrf_token"]');
                if (tokenInput) tokenInput.value = reviewCsrfToken;
                const reviews = Array.isArray(result.data.reviews) ? result.data.reviews : [];
                list.replaceChildren();
                if (status) status.textContent = reviews.length
                    ? `${reviews.length} customer review${reviews.length === 1 ? '' : 's'}`
                    : 'No reviews yet. Be the first to share your experience.';
                reviews.forEach((review) => {
                    const article = document.createElement('article');
                    article.className = 'user-review-card';
                    const top = document.createElement('div');
                    top.className = 'user-review-top';
                    const rating = Number(review.rating);
                    const ratingLabel = document.createElement('span');
                    ratingLabel.className = 'user-review-rating';
                    ratingLabel.textContent = `${'★'.repeat(Math.max(0, Math.min(5, rating)))}${'☆'.repeat(5 - Math.max(0, Math.min(5, rating)))} (${rating}/5)`;
                    top.appendChild(ratingLabel);
                    if (review.created_at) {
                        const date = document.createElement('time');
                        date.className = 'user-review-date';
                        date.dateTime = String(review.created_at);
                        const parsedDate = new Date(String(review.created_at).replace(' ', 'T'));
                        date.textContent = Number.isNaN(parsedDate.getTime())
                            ? String(review.created_at)
                            : new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' }).format(parsedDate);
                        top.appendChild(date);
                    }
                    article.appendChild(top);
                    const comment = document.createElement('p');
                    comment.textContent = String(review.comment || '');
                    comment.dir = 'auto';
                    article.appendChild(comment);
                    if (
                        reviewForm
                        && reviewSection.dataset.userId
                        && String(review.user_id) === reviewSection.dataset.userId
                    ) {
                        const removeForm = document.createElement('form');
                        removeForm.className = 'user-form-actions';
                        removeForm.dataset.userReviewDelete = '';
                        const hiddenFields = {
                            csrf_token: reviewCsrfToken,
                            product_id: reviewSection.dataset.productId,
                            review_id: review.id
                        };
                        Object.entries(hiddenFields).forEach(([name, value]) => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = name;
                            input.value = String(value || '');
                            removeForm.appendChild(input);
                        });
                        const removeButton = document.createElement('button');
                        removeButton.className = 'user-button-quiet';
                        removeButton.type = 'submit';
                        removeButton.textContent = 'Delete your review';
                        removeForm.appendChild(removeButton);
                        article.appendChild(removeForm);
                    }
                    list.appendChild(article);
                });
            } catch (error) {
                if (status) status.textContent = error.message || 'Reviews could not be loaded.';
                announce(error.message || 'Reviews could not be loaded.', 'error');
            }
        };

        reviewForm?.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!reviewCsrfToken) {
                setFormMessage(reviewForm, 'Review security token is not ready. Try again.', 'error');
                return;
            }
            await submitAsyncForm(reviewForm, {
                onSuccess: async () => {
                    reviewForm.reset();
                    const tokenInput = reviewForm.querySelector('[name="csrf_token"]');
                    if (tokenInput) tokenInput.value = reviewCsrfToken;
                    await loadReviews();
                }
            });
        });

        reviewSection.addEventListener('submit', async (event) => {
            const removeForm = event.target.closest('[data-user-review-delete]');
            if (!removeForm) return;
            event.preventDefault();
            if (!window.confirm(lootT('Are you sure you want to delete this review?'))) return;
            try {
                const response = await fetch(`${appUrl}/reviews/delete`, {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(Object.fromEntries(new FormData(removeForm).entries()))
                });
                const result = await parseResponse(response);
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Your review could not be deleted.');
                }
                announce(result.message || 'Review deleted.', 'success');
                await loadReviews();
            } catch (error) {
                announce(error.message || 'Your review could not be deleted.', 'error');
            }
        });
        loadReviews();
    }
})();
