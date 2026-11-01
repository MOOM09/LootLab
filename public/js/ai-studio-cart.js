(() => {
    const host = document.getElementById('ai-products');
    if (!host) return;
    const appUrl = document.body.dataset.userAppUrl || '';
    const designId = Number(host.dataset.designId);
    const button = document.getElementById('ai-add-cart');
    const status = document.getElementById('ai-cart-feedback');
    const label = document.getElementById('ai-product-name');
    const priceLabel = document.getElementById('ai-product-price');
    const types = {
        'hoodie-black': ['Black hoodie', '10.00'], 'hoodie-white': ['White hoodie', '10.00'],
        'tshirt-black': ['Black T-shirt', '8.00'], 'tshirt-white': ['White T-shirt', '8.00'],
        'mug-white': ['White mug', '5.00']
    };
    let model = 'hoodie-black';
    host.querySelectorAll('[data-model]').forEach(btn => {
        btn.addEventListener('click', () => {
            model = btn.dataset.model;
            if (types[model]) {label.textContent = types[model][0];priceLabel.textContent = types[model][1];}
            status.textContent = '';
        });
    });
    button.addEventListener('click', async () => {
        if (button.disabled) return;
        button.disabled = true;status.textContent = 'Adding your design to cart…';
        try {
            const initial = await fetch(appUrl + '/cart', {headers:{Accept:'application/json'},credentials:'same-origin'});
            const cart = await initial.json();
            if (!initial.ok || !cart?.data?.csrf_token) throw new Error(cart?.message || 'Unable to load your cart.');
            const response = await fetch(appUrl + '/cart/add-design', {
                method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json',Accept:'application/json'},
                body:JSON.stringify({custom_design_id:designId,model_key:model,csrf_token:cart.data.csrf_token})
            });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Could not add item.');
            status.textContent = '✓ Added to your cart! Redirecting…';
            window.location.assign(result.data?.redirect || appUrl + '/cart');
        } catch (error) {status.textContent = error.message;button.disabled = false;}
    });
})();
