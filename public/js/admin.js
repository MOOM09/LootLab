(() => {
    'use strict';
    const B = JSON.parse(document.getElementById('admin-bootstrap').textContent);
    const $ = (s, r = document) => r.querySelector(s),
        $$ = (s, r = document) => [...r.querySelectorAll(s)];
    const esc = (v) =>
        String(v ?? '').replace(
            /[&<>"']/g,
            (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c],
        );
    const icon = (name, cls = '') => `<svg class="${cls}" aria-hidden="true"><use href="#i-${name}"/></svg>`;
    const money = (v) =>
        new Intl.NumberFormat('en-JO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(
            Number(v) || 0,
        );
    const num = (v) => new Intl.NumberFormat('en-US').format(Number(v) || 0);
    const date = (v) => {
        if (!v) return '—';
        const d = new Date(String(v).replace(' ', 'T'));
        return Number.isNaN(+d)
            ? esc(v)
            : d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
    };
    const initials = (v) =>
        String(v || 'LH')
            .trim()
            .split(/\s+/)
            .slice(0, 2)
            .map((x) => [...x][0] || '')
            .join('')
            .toUpperCase();
    const avatar = (v, id = 0) =>
        `<span class="admin-initials" data-color="${Number(id) % 4}">${esc(initials(v))}</span>`;
    const badge = (v) =>
        `<span class="admin-badge ${esc(v)}">${esc(String(v).replace(/^./, (s) => s.toUpperCase()))}</span>`;
    const imageUrl = (v) => {
        if (!v) return '';
        const s = String(v);
        if (
            /^https?:\/\//i.test(s) ||
            s.startsWith('blob:') ||
            (B.preview && s.startsWith('data:image/svg+xml'))
        )
            return s;
        if (/^\/?(?:uploads|images)\//.test(s)) return B.appUrl + '/' + s.replace(/^\//, '');
        return '';
    };
    const thumb = (p) =>
        `<span class="admin-product-thumb">${imageUrl(p.image) ? `<img src="${esc(imageUrl(p.image))}" alt="" loading="lazy">` : icon('box')}</span>`;
    const button = (text, action, extra = '', classes = '') =>
        `<button type="button" class="admin-button ${classes}" data-action="${action}" ${extra}>${text}</button>`;
    const empty = (title, text, action = '') =>
        `<div class="admin-empty">${icon('box')}<h3>${esc(title)}</h3><p>${esc(text)}</p>${action}</div>`;
    const main = $('#admin-main'),
        drawer = $('#admin-drawer'),
        modal = $('#admin-modal');
    const names = {
        overview: 'Overview',
        users: 'Users',
        products: 'Products',
        categories: 'Categories',
        orders: 'Orders',
        inventory: 'Inventory',
        promos: 'Promo codes',
        reviews: 'Reviews',
        settings: 'Account settings',
    };
    let state = { page: 'overview', query: { page: 1 }, range: '33d' },
        meta = { categories: [], subcategories: [] },
        rows = [],
        selected = new Set(),
        loadVersion = 0,
        listAbort = null,
        searchTimer = null,
        editorProduct = null,
        previewObjectUrl = null;
    function toast(text, error = false) {
        const n = document.createElement('div');
        n.className = 'admin-toast' + (error ? ' error' : '');
        n.setAttribute('role', error ? 'alert' : 'status');
        n.innerHTML = icon(error ? 'alert' : 'check') + `<span>${esc(text)}</span>`;
        $('#admin-toasts').append(n);
        setTimeout(() => n.remove(), error ? 7000 : 4200);
    }
    async function api(name, params = {}, method = 'GET', signal) {
        if (B.preview && window.AdminDemo) return window.AdminDemo.request(name, params, method);
        const url = new URL(B.appUrl + '/admin.php', location.origin);
        url.searchParams.set('api', name);
        const options = {
            method,
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
            signal,
        };
        if (method === 'GET')
            Object.entries(params).forEach(([k, v]) => {
                if (v !== '' && v !== null && v !== undefined) url.searchParams.set(k, String(v));
            });
        else if (params instanceof FormData) {
            params.set('csrf_token', B.csrf);
            options.body = params;
        } else {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify({ ...params, csrf_token: B.csrf });
        }
        const response = await fetch(url, options);
        let result;
        try {
            result = await response.json();
        } catch {
            throw new Error('The server returned an unexpected response. Check the PHP error log.');
        }
        if (!response.ok || !result.success)
            throw new Error(result.message || 'The request could not be completed.');
        return result.data;
    }
    function setAdminIdentity(user, photo = B.avatar) {
        B.user = { ...B.user, ...user };
        B.avatar = photo || '';
        $$('[data-admin-name]').forEach((el) => (el.textContent = B.user.name || 'Administrator'));
        $$('[data-admin-avatar]').forEach((el) => {
            el.innerHTML = photo ? `<img src="${esc(imageUrl(photo))}" alt="">` : esc(initials(B.user.name));
        });
    }
    function syncUrl(replace = true) {
        const url = new URL(location.href);
        url.search = '';
        url.searchParams.set('page', state.page);
        Object.entries(state.query).forEach(([k, v]) => {
            if (v !== '' && v !== undefined && v !== null && !(k === 'page' && Number(v) === 1))
                url.searchParams.set(k === 'page' ? 'p' : k, String(v));
        });
        history[replace ? 'replaceState' : 'pushState'](null, '', url);
    }
    function closeDialog(el) {
        if (el.open) el.close();
    }
    function mobile(open) {
        const sidebar = $('#admin-sidebar'),
            small = matchMedia('(max-width:700px)').matches;
        sidebar.classList.toggle('is-open', open);
        document.body.classList.toggle('admin-menu-open', open && small);
        sidebar.inert = small && !open;
        sidebar.setAttribute('aria-hidden', String(small && !open));
        $('#admin-mobile-scrim').hidden = !open;
        $('[data-action="menu"]').setAttribute('aria-expanded', String(open));
    }
    function navigate(page, query = {}, replace = false) {
        if (!names[page]) page = 'overview';
        closeDialog(drawer);
        closeDialog(modal);
        const accountMenu = document.querySelector('[data-admin-account-menu]');
        if (accountMenu) accountMenu.open = false;
        mobile(false);
        selected.clear();
        state = {
            ...state,
            page,
            query: { page: 1, ...(page === 'inventory' ? { stock: 'attention' } : {}), ...query },
        };
        syncUrl(replace);
        loadPage();
        window.scrollTo({ top: 0, behavior: 'instant' });
    }
    const title = (eyebrow, heading, description, actions = '') =>
        `<div class="admin-page-heading"><div><p class="admin-eyebrow">${esc(eyebrow)}</p><h1>${esc(heading)}</h1><p>${esc(description)}</p></div><div class="admin-heading-actions">${actions}</div></div>`;
    const filter = (key, options) =>
        `<select class="admin-select" data-filter="${key}" aria-label="${esc(key.replaceAll('_', ' '))}">${options.map(([value, label]) => `<option value="${esc(value)}" ${String(state.query[key] ?? '') === String(value) ? 'selected' : ''}>${esc(label)}</option>`).join('')}</select>`;
    const search = (placeholder = 'Search…') =>
        `<label class="admin-search-field">${icon('search')}<input type="search" data-filter="q" aria-label="Search this list" placeholder="${esc(placeholder)}" value="${esc(state.query.q || '')}" maxlength="150"></label>`;
    const periods = () =>
        filter('period', [
            ['', 'All time'],
            ['today', 'Today'],
            ['week', 'Last 7 days'],
            ['month', 'Last 30 days'],
        ]);
    function pagination(data) {
        const page = Number(data.page),
            pages = Number(data.pages);
        const pageNums = new Set([1, pages, page - 1, page, page + 1]);
        let previous = 0,
            html = '';
        [...pageNums]
            .filter((n) => n >= 1 && n <= pages)
            .sort((a, b) => a - b)
            .forEach((n) => {
                if (previous && n - previous > 1) html += '<span>…</span>';
                html += `<button type="button" data-action="paginate" data-p="${n}" ${n === page ? 'aria-current="page"' : ''}>${n}</button>`;
                previous = n;
            });
        return `<div class="admin-pagination"><span>${data.total ? `${(page - 1) * 10 + 1}–${Math.min(page * 10, data.total)}` : '0'} of ${num(data.total)} results</span><div class="admin-page-buttons"><button data-action="paginate" data-p="${page - 1}" ${page <= 1 ? 'disabled' : ''} aria-label="Previous page">‹</button>${html}<button data-action="paginate" data-p="${page + 1}" ${page >= pages ? 'disabled' : ''} aria-label="Next page">›</button></div></div>`;
    }
    function mount(html) {
        const focused = document.activeElement?.dataset.filter;
        const selection = document.activeElement?.selectionStart;
        main.innerHTML =
            (B.preview
                ? '<div class="admin-preview-banner">DESIGN PREVIEW · Example records only. Changes stay in this browser session.</div>'
                : '') + html;
        if (focused) {
            const field = $(`[data-filter="${focused}"]`, main);
            if (field) {
                field.focus({ preventScroll: true });
                if (field.type === 'search' && selection !== null)
                    field.setSelectionRange(selection, selection);
            }
        }
        bindChart();
        animateCounters();
    }
    function rowOrders(items, compact = false) {
        return `<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Order</th><th>Customer</th>${compact ? '' : '<th>Items</th>'}<th>Amount · JOD</th><th>Status</th>${compact ? '' : '<th>Placed</th>'}<th></th></tr></thead><tbody>${items.map((o) => `<tr data-action="order" data-id="${Number(o.id)}"><td class="admin-primary-text">#LH-${String(o.id).padStart(4, '0')}</td><td><div class="admin-row-name">${avatar(o.name, o.user_id)}<div><span class="admin-primary-text">${esc(o.name)}</span>${compact ? '' : `<small class="admin-secondary-text">${esc(o.email)}</small>`}</div></div></td>${compact ? '' : `<td>${num(o.items_count)}</td>`}<td class="admin-primary-text">${money(o.total_amount)}</td><td>${badge(o.status)}</td>${compact ? '' : `<td>${date(o.created_at)}</td>`}<td><button type="button" data-action="order" data-id="${Number(o.id)}" class="admin-icon-button" aria-label="View order ${Number(o.id)}">${icon('chevron')}</button></td></tr>`).join('')}</tbody></table></div>`;
    }
    function linePath(
        values,
        width = 640,
        height = 205,
        left = 45,
        right = 15,
        top = 16,
        bottom = 30,
        maxValue = null,
    ) {
        const max = maxValue ?? Math.max(1, ...values);
        const points = values.map((v, i) => [
            left + (width - left - right) * (values.length <= 1 ? 0 : i / (values.length - 1)),
            height - bottom - ((height - top - bottom) * Number(v)) / max,
        ]);
        return {
            points,
            d: points
                .map(([x, y], i) => {
                    if (!i) return `M${x.toFixed(2)},${y.toFixed(2)}`;
                    const [px, py] = points[i - 1],
                        mid = (px + x) / 2;
                    return `C${mid.toFixed(2)},${py.toFixed(2)} ${mid.toFixed(2)},${y.toFixed(2)} ${x.toFixed(2)},${y.toFixed(2)}`;
                })
                .join(' '),
            max,
        };
    }
    function spark(values) {
        const { points } = linePath(values, 85, 34, 0, 0, 2, 2);
        return `<svg class="admin-metric-spark" viewBox="0 0 85 34" aria-hidden="true"><polyline points="${points.map((p) => p.join(',')).join(' ')}" fill="none" stroke="#b58bff" stroke-width="1.4"/></svg>`;
    }
    function chart(series) {
        const values = series.map((p) => Number(p.completed)),
            ordered = series.map((p) => Number(p.ordered)),
            max = Math.max(100, ...values, ...ordered);
        const a = linePath(values, 640, 260, 44, 13, 18, 35, max),
            b = linePath(ordered, 640, 260, 44, 13, 18, 35, max);
        const baseline = 225;
        return `<div class="admin-chart-wrap"><svg class="admin-chart" viewBox="0 0 640 260" role="img" aria-labelledby="admin-chart-title"><title id="admin-chart-title">Completed and non-cancelled order value in JOD</title><defs><linearGradient id="admin-chart-fill" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#b58bff" stop-opacity=".30"/><stop offset="100%" stop-color="#b58bff" stop-opacity="0"/></linearGradient></defs>${[
            0, 0.25, 0.5, 0.75, 1,
        ]
            .map((v) => {
                const y = 225 - 207 * v;
                return `<line class="admin-gridline" x1="44" y1="${y}" x2="627" y2="${y}"/><text x="0" y="${y + 3}">${max * v >= 1000 ? `${((max * v) / 1000).toFixed(1)}k` : Math.round(max * v)}</text>`;
            })
            .join(
                '',
            )}<path class="admin-line secondary" d="${b.d}"/><path class="admin-area" fill="url(#admin-chart-fill)" d="${a.d} L627,${baseline} L44,${baseline} Z"/><path class="admin-line" d="${a.d}"/>${series.map((point, i) => `<circle cx="${a.points[i][0]}" cy="${a.points[i][1]}" r="3" tabindex="0" data-chart-label="${esc(point.label)}" data-chart-value="${money(point.completed)}" aria-label="${esc(point.label)}: ${money(point.completed)} JOD"/>${series.length <= 12 || i % 5 === 0 ? `<text x="${a.points[i][0]}" y="252" text-anchor="middle">${esc(point.label)}</text>` : ''}`).join('')}</svg><div class="admin-chart-tooltip" hidden></div></div>`;
    }
    function overview(data) {
        const s = data.stats,
            series = data.series;
        const sum = (key) => series.reduce((a, p) => a + Number(p[key] || 0), 0);
        const stats = [
            ['wallet', 'Completed order value', s.revenue, 'JOD', 'All-time · completed orders', 'completed'],
            ['users', 'Customers', s.customers, '', 'Registered customer accounts', 'customers'],
            ['box', 'Active products', s.products, '', `${num(s.low_stock)} need stock attention`, 'ordered'],
            ['bag', 'Total orders', s.orders, '', `${num(s.pending)} awaiting processing`, 'orders'],
        ];
        const controls = `<div class="admin-range" role="group" aria-label="Chart period">${[
            ['7d', '7D'],
            ['30d', '30D'],
            ['33d', '33D'],
            ['12m', '12M'],
        ]
            .map(
                ([v, l]) =>
                    `<button type="button" data-action="range" data-range="${v}" class="${state.range === v ? 'is-active' : ''}">${l}</button>`,
            )
            .join('')}</div>`;
        mount(
            title(
                'Your store, at a glance',
                `Welcome back, ${String(B.user.name || 'Admin').split(' ')[0]}`,
                "A little clarity for everything you're building.",
                button(icon('plus') + 'Add product', 'new-product'),
            ) +
                `<div class="admin-metrics">${stats.map(([ic, label, value, unit, note, key]) => `<article class="admin-metric"><div class="admin-metric-label">${icon(ic)}${label}<span class="admin-metric-menu">···</span></div><div class="admin-metric-value"><span data-count="${Number(value)}" data-decimal="${unit ? 2 : 0}">${unit ? money(value) : num(value)}</span>${unit ? `<small>${unit}</small>` : ''}</div><div class="admin-metric-note"><i></i>${esc(note)}</div>${spark(series.map((p) => p[key]))}</article>`).join('')}</div>
<div class="admin-dashboard-grid"><section class="admin-panel"><div class="admin-panel-header"><div><h2>Sales performance</h2><p>Order value over time</p></div>${controls}</div><div class="admin-chart-body"><div class="admin-chart-summary"><div class="admin-chart-total">${money(sum('completed'))}<small>JOD</small></div><div class="admin-legend"><span><i></i>Completed</span><span><i></i>All non-cancelled</span></div></div>${chart(series)}<div class="admin-chart-foot"><span>Completed orders · not a payment ledger</span><span>${esc(series[0]?.date || '')} — ${esc(series.at(-1)?.date || '')}</span></div></div></section>
<div class="admin-mini-stack"><section class="admin-panel"><div class="admin-panel-header"><h2>Order activity</h2><a href="?page=orders" class="admin-link" data-nav="orders">View orders ${icon('arrow')}</a></div><div class="admin-mini-content"><div class="admin-mini-value">${num(sum('orders'))}<small>orders this period</small></div><div class="admin-bars" role="img" aria-label="Order counts by period">${series.map((p, i) => `<span class="admin-bar" title="${esc(p.label)}: ${Number(p.orders)} orders" style="--height:${Math.max(Number(p.orders) ? 5 : 1, (Number(p.orders) / Math.max(1, ...series.map((x) => Number(x.orders)))) * 100)}%;--index:${i}"></span>`).join('')}</div><div class="admin-axis"><span>${esc(series[0]?.label)}</span><span>${esc(series.at(-1)?.label)}</span></div></div></section>
<section class="admin-panel"><div class="admin-panel-header"><h2>New customers</h2><a href="?page=users" class="admin-link" data-nav="users">View users ${icon('arrow')}</a></div><div class="admin-mini-content"><div class="admin-mini-value">${num(sum('customers'))}<small>joined this period</small></div><svg class="admin-mini-line" viewBox="0 0 300 65" role="img" aria-label="New customer registrations"><polyline points="${linePath(
                    series.map((p) => Number(p.customers)),
                    300,
                    65,
                    0,
                    0,
                    5,
                    5,
                )
                    .points.map((p) => p.join(','))
                    .join(
                        ' ',
                    )}"/></svg><div class="admin-mini-caption"><span>A growing community.</span><span>${state.range === '12m' ? 'Last 12 months' : state.range === '7d' ? 'Last 7 days' : state.range === '33d' ? 'Last 33 days' : 'Last 30 days'}</span></div></div></section></div></div>
<div class="admin-two-panels"><section class="admin-panel"><div class="admin-panel-header"><div><h2>Recent orders</h2><p>The latest from your store</p></div><a class="admin-link" href="?page=orders" data-nav="orders">View all ${icon('arrow')}</a></div>${data.recent_orders.length ? rowOrders(data.recent_orders.slice(0, 5), true) : empty('Your first order starts here', 'New orders will appear as customers check out.')}<div class="admin-panel-bottom"><span>Newest orders first</span><span>Updated just now</span></div></section>
<section class="admin-panel"><div class="admin-panel-header"><div><h2>Best-selling products</h2><p>Processing, shipped & completed orders</p></div>${icon('star')}</div><div class="admin-top-products">${data.top_products.length ? data.top_products.map((p) => `<a class="admin-top-product" href="?page=products" data-action="product" data-id="${Number(p.id)}">${thumb(p)}<div><strong>${esc(p.name)}</strong><small>${esc(p.category_name)}</small></div><span class="admin-sales">${num(p.units_sold)}<small>units</small></span></a>`).join('') : empty('Make room for your next hit', 'Add products to start your collection.')}</div><div class="admin-panel-bottom"><span>Built for your next favorite</span><a class="admin-link" href="?page=products&sort=sold-desc" data-action="top-products">Explore products ${icon('arrow')}</a></div></section></div>
${Number(s.low_stock) ? `<div class="admin-alert-banner">${icon('alert')}<span><strong>${num(s.low_stock)} products</strong> are running low or out of stock.</span><a class="admin-link" href="?page=inventory" data-nav="inventory">Review inventory ${icon('arrow')}</a></div>` : ''}`,
        );
        const pending = $('#admin-pending-count');
        pending.textContent = num(s.pending);
        pending.hidden = !Number(s.pending);
    }
    function usersPage(data) {
        rows = data.items;
        const s = data.summary || {};
        mount(
            title(
                'Community',
                'Your customers',
                'Every account, one clear view. Newest registrations appear first.',
                button(icon('download') + 'Export this page', 'export-users', '', 'secondary'),
            ) +
                `<div class="admin-summary-pills"><span class="admin-summary-pill">Total customers<strong>${num(s.total)}</strong></span><span class="admin-summary-pill">Active accounts<strong>${num(s.active)}</strong></span><span class="admin-summary-pill">Joined today<strong>${num(s.today)}</strong></span></div><section class="admin-panel"><div class="admin-toolbar">${search('Search name, email or phone…')}<div class="admin-filters">${periods()}${filter(
                    'orders',
                    [
                        ['', 'All customers'],
                        ['yes', 'Placed an order'],
                        ['no', 'No orders yet'],
                    ],
                )}${filter('active', [
                    ['', 'All statuses'],
                    ['1', 'Active'],
                    ['0', 'Inactive'],
                ])}</div></div>${data.items.length ? `<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Customer</th><th>Phone</th><th>Joined</th><th>Orders</th><th>Status</th><th>Actions</th></tr></thead><tbody>${data.items.map((u) => `<tr data-action="user" data-id="${Number(u.id)}"><td><div class="admin-row-name">${avatar(u.name, u.id)}<div><span class="admin-primary-text">${esc(u.name)}</span><small class="admin-secondary-text">${esc(u.email)}</small></div></div></td><td>${esc(u.phone || '—')}</td><td>${date(u.created_at)}</td><td>${num(u.orders_count)}</td><td>${badge(Number(u.is_active) ? 'active' : 'inactive')}</td><td><div class="admin-cell-actions"><button class="admin-button secondary small" type="button" data-action="user" data-id="${Number(u.id)}" aria-label="Manage ${esc(u.name)}">Manage</button></div></td></tr>`).join('')}</tbody></table></div>` : empty('No customers match', 'Try another search or clear the date and status filters.')}${pagination(data)}</section>`,
        );
    }
    function productsTable(data) {
        rows = data.items;
        return `<section class="admin-panel"><div class="admin-toolbar">${search('Search products or descriptions…')}<div class="admin-filters">${periods()}${filter(
            'sort',
            [
                ['', 'Newest first'],
                ['oldest', 'Oldest first'],
                ['price-desc', 'Price: high to low'],
                ['price-asc', 'Price: low to high'],
                ['sold-desc', 'Most purchased'],
                ['sold-asc', 'Least purchased'],
            ],
        )}${filter('active', [
            ['', 'Active products'],
            ['0', 'Archived'],
            ['all', 'All products'],
        ])}${
            state.page === 'inventory'
                ? filter('stock', [
                      ['attention', 'Needs attention (≤5)'],
                      ['', 'All stock levels'],
                      ['low', 'Low stock (1–5)'],
                      ['out', 'Out of stock'],
                  ])
                : ''
        }</div></div><div class="admin-bulk" id="admin-bulk" ${selected.size ? '' : 'hidden'}><span><strong id="admin-selected-count">${selected.size}</strong> selected</span>${button('Move to category', 'move-products', '', 'small secondary')}<button class="admin-link" data-action="clear-selection">Clear</button></div>${data.items.length ? `<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th><input type="checkbox" data-select-all aria-label="Select products on this page"></th><th>Product</th><th>Price · JOD</th><th>Description</th><th>Category</th><th>Stock</th><th>Sold</th><th>Actions</th></tr></thead><tbody>${data.items.map((p) => `<tr><td><input type="checkbox" data-product-select="${Number(p.id)}" ${selected.has(String(p.id)) ? 'checked' : ''} aria-label="Select ${esc(p.name)}"></td><td><a class="admin-row-name" href="?page=products" data-action="product" data-id="${Number(p.id)}">${thumb(p)}<div><span class="admin-primary-text">${esc(p.name)}</span><small class="admin-secondary-text">#LH-${Number(p.id)} ${Number(p.is_active) ? '' : '· Archived'}</small></div></a></td><td class="admin-primary-text">${money(p.discount_price ?? p.price)}${p.discount_price !== null && p.discount_price !== undefined ? `<small class="admin-secondary-text"><del>${money(p.price)}</del></small>` : ''}</td><td><button type="button" class="admin-description-button" data-action="product" data-id="${Number(p.id)}" title="Read and edit full description">${esc(p.description)}</button></td><td>${esc(p.category_name)}<small class="admin-secondary-text">${esc(p.subcategory_name)}</small></td><td><span class="admin-stock ${Number(p.stock_quantity) === 0 ? 'out' : Number(p.stock_quantity) <= 5 ? 'low' : ''}">${num(p.stock_quantity)} units</span></td><td>${num(p.units_sold)}</td><td><div class="admin-cell-actions"><button type="button" class="admin-icon-button" data-action="product" data-id="${Number(p.id)}" aria-label="Edit ${esc(p.name)}">${icon('edit')}</button><button type="button" class="admin-icon-button danger" data-action="archive" data-id="${Number(p.id)}" aria-label="Archive ${esc(p.name)}" ${Number(p.is_active) ? '' : 'disabled'}>${icon('trash')}</button></div></td></tr>`).join('')}</tbody></table></div>` : empty('No products found', 'Adjust your filters or add a product to your collection.', button(icon('plus') + 'Add product', 'new-product'))}${pagination(data)}</section>`;
    }
    function categoryAside() {
        const chosen = String(state.query.category_id || '');
        const subcats = meta.subcategories.filter((s) => String(s.category_id) === chosen);
        return `<aside class="admin-category-aside"><h2>Collections</h2><label class="admin-search-field">${icon('search')}<input type="search" data-category-search placeholder="Find a category…" aria-label="Search categories"></label><div class="admin-category-items"><button class="admin-category-item ${!chosen ? 'is-active' : ''}" data-action="category" data-id="">${icon('grid')}All products<small>${meta.categories.reduce((a, c) => a + Number(c.products_count || 0), 0)}</small></button>${meta.categories.map((c) => `<button class="admin-category-item ${chosen === String(c.id) ? 'is-active' : ''}" data-action="category" data-id="${Number(c.id)}" data-category-label="${esc(c.name.toLowerCase())}">${icon('category')}${esc(c.name)}<small>${num(c.products_count)}</small></button>`).join('')}</div>${chosen ? `<div class="admin-subcategory-list">${subcats.map((s) => `<div><button class="admin-link" data-action="subcategory-filter" data-id="${Number(s.id)}">${esc(s.name)}</button><span><button data-action="edit-subcategory" data-id="${Number(s.id)}" aria-label="Rename ${esc(s.name)}">✎</button><button data-action="delete-subcategory" data-id="${Number(s.id)}" aria-label="Delete empty subcategory ${esc(s.name)}">×</button></span></div>`).join('') || '<p class="admin-preview-note">Add a subcategory to start organizing products.</p>'}</div><div class="admin-category-tools"><button class="admin-link" data-action="edit-category" data-id="${Number(chosen)}">Rename</button><button class="admin-link" data-action="new-subcategory" data-category="${Number(chosen)}">+ Subcategory</button><button class="admin-link" data-action="delete-category" data-id="${Number(chosen)}">Delete</button></div>` : ''}</aside>`;
    }
    function productsPage(data) {
        const cats = state.page === 'categories',
            inventory = state.page === 'inventory';
        const heading = cats
            ? 'Everything in its place.'
            : inventory
              ? 'Keep your shelves ready.'
              : 'Your collection, curated.';
        const subtitle = cats
            ? 'Browse collections, move existing products, or create something new.'
            : inventory
              ? 'Filter stock levels and update quantities from the product editor.'
              : 'Create, refine and manage the products your customers discover.';
        const actions =
            (cats ? button(icon('plus') + 'New category', 'new-category', '', 'secondary') : '') +
            button(icon('plus') + 'Add product', 'new-product');
        mount(
            title(
                cats ? 'Catalog organization' : inventory ? 'Stockroom' : 'Catalog',
                heading,
                subtitle,
                actions,
            ) +
                (cats
                    ? `<div class="admin-category-layout">${categoryAside()}<div>${productsTable(data)}</div></div>`
                    : productsTable(data)),
        );
    }
    function ordersPage(data) {
        rows = data.items;
        mount(
            title(
                'Fulfilment',
                'Every order. Every detail.',
                'Open an order to review the customer, delivery address and purchased items.',
            ) +
                `<section class="admin-panel"><div class="admin-toolbar">${search('Search customer, email or order ID…')}<div class="admin-filters">${periods()}${filter('status', [['', 'All statuses'], ...['pending', 'processing', 'shipped', 'completed', 'cancelled'].map((x) => [x, x[0].toUpperCase() + x.slice(1)])])}${state.query.user_id ? button('All customers', 'all-orders', '', 'small secondary') : ''}</div></div>${data.items.length ? rowOrders(data.items) : empty('No orders match', 'Try another search or date range.')}${pagination(data)}</section>`,
        );
    }
    function promosPage(data) {
        rows = data.items;
        mount(
            title(
                'A little extra',
                'Promo codes',
                'Create a reason to come back. Expired codes stay in order history.',
                button(icon('plus') + 'New promo code', 'new-promo'),
            ) +
                `<section class="admin-panel">${
                    data.items.length
                        ? `<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Code</th><th>Discount</th><th>Expiration</th><th>Status</th><th>Actions</th></tr></thead><tbody>${data.items
                              .map((p) => {
                                  const expired =
                                      p.expires_at &&
                                      new Date(String(p.expires_at).replace(' ', 'T')) < new Date();
                                  return `<tr><td class="admin-primary-text">${esc(p.code)}</td><td>${p.discount_type === 'percentage' ? `${money(p.discount_value)}%` : `${money(p.discount_value)} JOD`}</td><td>${p.expires_at ? date(p.expires_at) : 'No expiration'}</td><td>${badge(expired ? 'inactive' : 'active')}</td><td><div class="admin-cell-actions"><button class="admin-icon-button" data-action="promo" data-id="${Number(p.id)}" aria-label="Edit code">${icon('edit')}</button><button class="admin-icon-button danger" data-action="expire-promo" data-id="${Number(p.id)}" aria-label="Expire code" ${expired ? 'disabled' : ''}>${icon('power')}</button></div></td></tr>`;
                              })
                              .join('')}</tbody></table></div>`
                        : empty(
                              'Good things are coming',
                              'Create your first promotion to share with customers.',
                          )
                }</section>`,
        );
    }
    function reviewsPage(data) {
        rows = data.items;
        mount(
            title(
                'Community voices',
                'Customer reviews',
                'Read feedback and remove comments that do not belong in your community.',
            ) +
                `<section class="admin-panel"><div class="admin-toolbar">${search('Search customer, product or comment…')}</div>${data.items.length ? `<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Customer</th><th>Product</th><th>Rating</th><th>Comment</th><th>Posted</th><th></th></tr></thead><tbody>${data.items.map((r) => `<tr><td class="admin-primary-text">${esc(r.name)}</td><td>${esc(r.product_name)}</td><td><span class="admin-stars" aria-label="${Number(r.rating)} of 5 stars">${'★'.repeat(Math.min(5, Math.max(0, Number(r.rating))))}</span></td><td class="admin-review-comment">${esc(r.comment)}</td><td>${date(r.created_at)}</td><td><button class="admin-icon-button danger" data-action="delete-review" data-id="${Number(r.id)}" aria-label="Delete review">${icon('trash')}</button></td></tr>`).join('')}</tbody></table></div>` : empty('Nothing to review just yet', 'Feedback will appear here when customers post reviews.')}${pagination(data)}</section>`,
        );
    }
    function settingsPage(u) {
        const passwords = Number(u.has_password)
            ? `<details class="admin-password-panel" data-admin-password-panel>
                <summary><span>Change password</span><span class="admin-password-chevron">⌄</span></summary>
                <div class="admin-password-fields">
                    <p class="admin-preview-note">Password fields are only enabled when you open this section. Your current password is required to change it.</p>
                    ${field('Current password', 'current_password', '', 'password', 'autocomplete="off" maxlength="128" data-admin-password-field disabled')}
                    <div class="admin-form-row">
                        ${field('New password', 'new_password', '', 'password', 'autocomplete="new-password" minlength="12" maxlength="128" data-admin-password-field disabled')}
                        ${field('Confirm new password', 'confirm_password', '', 'password', 'autocomplete="new-password" minlength="12" maxlength="128" data-admin-password-field disabled')}
                    </div>
                    <p class="admin-preview-note">Use at least 12 characters. Changing the password signs out other sessions.</p>
                </div>
            </details>`
            : '<p class="admin-privacy-note">This account uses Google sign-in. Manage its password with your Google account.</p>';
        mount(
            title('Make it yours', 'Your admin profile', 'Keep your information up to date and your account secure.') +
            `<div class="admin-settings-layout">
                <section class="admin-panel admin-profile-card">
                    <span class="admin-avatar" data-admin-avatar>${u.avatar ? `<img src="${esc(imageUrl(u.avatar))}" alt="">` : esc(initials(u.name))}</span>
                    <div><h2>${esc(u.name)}</h2><p>Administrator</p></div>
                    <label class="admin-profile-upload">Change photo<input type="file" accept="image/png,image/jpeg,image/webp" data-admin-photo aria-label="Upload admin profile photo"></label>
                </section>
                <section class="admin-panel">
                    <form class="admin-settings-form" data-form="settings" autocomplete="off">
                        <h2>Account information</h2>
                        <div class="admin-form-error" role="alert" hidden></div>
                        <div class="admin-form-row">
                            ${field('Full name', 'name', u.name, 'text', 'required maxlength="100"')}
                            ${field('Phone number', 'phone', u.phone || '', 'tel', 'maxlength="25"')}
                        </div>
                        ${field('Email address', 'email', u.email, 'email', 'readonly')}
                        <small class="admin-preview-note">Email is your sign-in identity and is read-only here.</small>
                        <hr class="admin-settings-divider">
                        ${passwords}
                        <button class="admin-button" type="submit">${icon('check')}Save changes</button>
                    </form>
                </section>
            </div>`,
        );
    }
    async function loadPage(showLoader = true) {
        const version = ++loadVersion;
        if (listAbort) listAbort.abort();
        listAbort = new AbortController();
        $$('[data-nav]').forEach((a) => {
            const active = a.dataset.nav === state.page;
            a.classList.toggle('is-active', active);
            if (a.closest('nav')) {
                if (active) a.setAttribute('aria-current', 'page');
                else a.removeAttribute('aria-current');
            }
        });
        $('#admin-crumb').textContent = names[state.page];
        document.title = names[state.page] + ' — LOOT HUB Admin';
        if (showLoader)
            main.innerHTML = '<div class="admin-loading"><span></span>Loading your workspace…</div>';
        try {
            const endpoint =
                state.page === 'categories' || state.page === 'inventory' ? 'products' : state.page;
            const data = await api(
                endpoint,
                state.page === 'overview' ? { range: state.range } : state.query,
                'GET',
                listAbort.signal,
            );
            if (version !== loadVersion) return;
            ({
                overview,
                users: usersPage,
                products: productsPage,
                categories: productsPage,
                inventory: productsPage,
                orders: ordersPage,
                promos: promosPage,
                reviews: reviewsPage,
                settings: settingsPage,
            })[state.page](data);
        } catch (error) {
            if (error.name === 'AbortError' || version !== loadVersion) return;
            mount(
                `<div class="admin-error" role="alert">${esc(error.message)}<p>${button('Try again', 'retry', '', 'secondary small')}</p></div>`,
            );
        }
    }
    function field(label, name, value = '', type = 'text', extra = '') {
        return `<label class="admin-field"><span>${esc(label)}</span><input class="admin-input" name="${name}" type="${type}" value="${esc(value)}" ${extra}></label>`;
    }
    function dialogHeader(eyebrow, heading, kind = 'drawer') {
        return `<header class="admin-dialog-header"><div><p class="admin-eyebrow">${esc(eyebrow)}</p><h2>${esc(heading)}</h2></div><button type="button" class="admin-icon-button" data-action="close-${kind}" aria-label="Close dialog">${icon('close')}</button></header>`;
    }
    function showDrawer(html) {
        drawer.innerHTML = html;
        if (!drawer.open) drawer.showModal();
        drawer.scrollTop = 0;
    }
    function showModal(html) {
        modal.innerHTML = html;
        if (!modal.open) modal.showModal();
    }
    function formError(form, text) {
        let target = $('.admin-form-error', form);
        if (!target) {
            target = document.createElement('div');
            target.className = 'admin-form-error';
            target.setAttribute('role', 'alert');
            form.prepend(target);
        }
        target.textContent = text;
        target.hidden = false;
        target.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }
    function combo(name, label, items, value, extra = '') {
        const selectedItem = items.find((x) => String(x.id) === String(value));
        return `<div class="admin-field admin-combo" data-combo="${name}"><span id="${name}-label">${esc(label)}</span><input type="hidden" name="${name}" value="${esc(selectedItem?.id || '')}"><button type="button" class="admin-input admin-combo-trigger" data-action="combo-toggle" aria-expanded="false" aria-labelledby="${name}-label" aria-controls="${name}-menu"><span>${esc(selectedItem?.name || 'Choose ' + label.toLowerCase())}</span>${icon('chevron')}</button><div class="admin-combo-menu" id="${name}-menu" hidden><input type="search" class="admin-input" data-combo-search placeholder="Search ${esc(label.toLowerCase())}…" aria-label="Search ${esc(label)}"><div class="admin-combo-options" role="listbox" aria-labelledby="${name}-label">${items.map((x) => `<button type="button" class="admin-combo-option" role="option" aria-selected="${String(x.id) === String(value)}" data-action="combo-option" data-id="${Number(x.id)}" data-label="${esc(x.name)}">${esc(x.name)}</button>`).join('')}${!items.length ? '<p class="admin-combo-empty">No options yet.</p>' : ''}</div>${extra}</div></div>`;
    }
    function productCombos(p = {}) {
        return (
            combo(
                'category_id',
                'Category',
                meta.categories,
                p.category_id,
                `<button type="button" class="admin-link" data-action="new-category">+ Create category</button>`,
            ) +
            combo(
                'subcategory_id',
                'Subcategory',
                meta.subcategories.filter((s) => String(s.category_id) === String(p.category_id)),
                p.subcategory_id,
                `<button type="button" class="admin-link" data-action="new-subcategory" data-category="${Number(p.category_id) || ''}">+ Create subcategory</button>`,
            )
        );
    }
    function refreshCombos(categoryId, subId = '') {
        const form = $('[data-form="product"]', drawer) || $('[data-form="move"]', modal);
        if (!form) return;
        const cat = $('[data-combo="category_id"]', form),
            sub = $('[data-combo="subcategory_id"]', form);
        if (cat)
            cat.outerHTML = combo(
                'category_id',
                'Category',
                meta.categories,
                categoryId,
                form.dataset.form === 'product'
                    ? `<button type="button" class="admin-link" data-action="new-category">+ Create category</button>`
                    : '',
            );
        if (sub)
            sub.outerHTML = combo(
                'subcategory_id',
                'Subcategory',
                meta.subcategories.filter((s) => String(s.category_id) === String(categoryId)),
                subId,
                form.dataset.form === 'product'
                    ? `<button type="button" class="admin-link" data-action="new-subcategory" data-category="${Number(categoryId) || ''}">+ Create subcategory</button>`
                    : '',
            );
        updatePreview();
    }
    async function editProduct(id = null) {
        try {
            const p = id
                ? await api('product', { id })
                : {
                      name: '',
                      description: '',
                      price: '',
                      discount_price: '',
                      stock_quantity: 0,
                      is_active: 1,
                      category_id: state.query.category_id || '',
                      subcategory_id: state.query.subcategory_id || '',
                      image: '',
                  };
            editorProduct = p;
            showDrawer(
                dialogHeader('Catalog studio', id ? 'Edit product' : 'Create a new product') +
                    `<form data-form="product"><input type="hidden" name="id" value="${esc(p.id || '')}"><div class="admin-dialog-body"><div class="admin-form-error" role="alert" hidden></div><div class="admin-editor-grid"><div>${field('Product name', 'name', p.name, 'text', 'required maxlength="150"')}<label class="admin-field"><span>Description</span><textarea class="admin-textarea" name="description" maxlength="20000" required>${esc(p.description)}</textarea></label><div class="admin-form-row">${field('Regular price · JOD', 'price', p.price, 'number', 'required min="0" max="99999999.99" step="0.01"')}${field('Sale price · JOD (optional)', 'discount_price', p.discount_price ?? '', 'number', 'min="0" max="99999999.99" step="0.01"')}</div><div class="admin-form-row">${field('Stock quantity', 'stock_quantity', p.stock_quantity, 'number', 'required min="0" max="100000000" step="1"')}<label class="admin-field"><span>Visibility</span><select class="admin-input" name="is_active"><option value="1" ${Number(p.is_active) ? 'selected' : ''}>Active · visible in store</option><option value="0" ${!Number(p.is_active) ? 'selected' : ''}>Archived · hidden</option></select></label></div><div id="admin-product-combos">${productCombos(p)}</div><p class="admin-preview-note">Category → subcategory → product. For example: Anime → Naruto → Naruto mug.</p><label class="admin-field"><span>Product image</span><span class="admin-upload-zone">${icon('image')}<strong>Click to upload an image</strong><small>JPG, PNG or WebP · up to 5 MB</small><input type="file" name="image_file" accept="image/jpeg,image/png,image/webp" aria-label="Upload product image"></span></label>${field('Or use an image URL', 'image', p.image || '', 'text', 'maxlength="500" placeholder="https://…"')}</div><aside><div class="admin-preview-label">STOREFRONT PREVIEW<span>LIVE</span></div><article class="admin-product-preview"><div class="admin-preview-image" id="admin-preview-image">${icon('box')}<span class="admin-preview-wishlist" aria-hidden="true">♡</span></div><div class="admin-preview-copy"><small id="admin-preview-category">Your collection</small><h3 id="admin-preview-name">Your next favorite</h3><div class="admin-preview-price" id="admin-preview-price">0.00 JOD</div><p class="admin-preview-stock" id="admin-preview-stock">Stock preview</p><div class="admin-preview-buy" aria-hidden="true">Add to cart</div></div></article><p class="admin-preview-note">See how your product reads before it goes live. The storefront uses the same name, image, price and stock.</p></aside></div></div><footer class="admin-dialog-footer"><button type="button" class="admin-button secondary" data-action="close-drawer">Cancel</button><button class="admin-button" type="submit">${icon('check')}${id ? 'Save changes' : 'Create product'}</button></footer></form>`,
            );
            updatePreview();
        } catch (e) {
            toast(e.message, true);
        }
    }
    function updatePreview() {
        const form = $('[data-form="product"]', drawer);
        if (!form) return;
        const value = (name) => form.elements.namedItem(name)?.value || '';
        $('#admin-preview-name').textContent = value('name') || 'Your next favorite';
        const category = meta.categories.find((c) => String(c.id) === value('category_id'));
        $('#admin-preview-category').textContent = category?.name || 'Your collection';
        const price = Number(value('price')) || 0,
            discount = value('discount_price'),
            sale = discount !== '' && Number(discount) < price;
        $('#admin-preview-price').innerHTML =
            `${money(sale ? discount : price)} <small>JOD</small>${sale ? `<del>${money(price)}</del>` : ''}`;
        const stock = Number(value('stock_quantity')) || 0;
        $('#admin-preview-stock').textContent = stock
            ? `In stock · ${num(stock)} available`
            : 'Currently out of stock';
        const url = previewObjectUrl || imageUrl(value('image'));
        $('#admin-preview-image').innerHTML =
            (url ? `<img src="${esc(url)}" alt="Product preview">` : icon('box')) +
            '<span class="admin-preview-wishlist" aria-hidden="true">♡</span>';
    }
    async function viewUser(id) {
        try {
            const u = await api('user', { id });
            showDrawer(
                dialogHeader('Customer account', 'Customer details') +
                    `<div class="admin-dialog-body"><div class="admin-detail-heading">${avatar(u.name, u.id)}<div><h3>${esc(u.name)}</h3><p>${esc(u.email)}</p></div></div><dl class="admin-detail-facts"><div><dt>Account status</dt><dd>${badge(Number(u.is_active) ? 'active' : 'inactive')}</dd></div><div><dt>Customer ID</dt><dd>#LH-${Number(u.id)}</dd></div><div><dt>Registered</dt><dd>${date(u.created_at)}</dd></div><div><dt>Phone number</dt><dd>${esc(u.phone || 'Not provided')}</dd></div><div><dt>Total orders</dt><dd>${num(u.summary?.orders_count)}</dd></div><div><dt>Completed order value</dt><dd>${money(u.summary?.completed_value)} JOD</dd></div></dl><p class="admin-privacy-note">Read-only customer view. Personal images, AI designs and favorites are private and are not displayed here.</p><h3 class="admin-detail-section">Latest orders</h3>${
                        u.orders.length
                            ? rowOrders(
                                  u.orders.map((o) => ({
                                      ...o,
                                      name: u.name,
                                      user_id: u.id,
                                      email: u.email,
                                  })),
                                  true,
                              )
                            : empty('No orders yet', 'This customer has not placed an order.')
                    }<p>${button('View all customer orders', 'customer-orders', `data-id="${Number(u.id)}"`, 'secondary small')}</p></div><footer class="admin-dialog-footer">${button(Number(u.is_active) ? 'Deactivate account' : 'Activate account', 'user-status', `data-id="${Number(u.id)}" data-active="${Number(u.is_active) ? 0 : 1}"`, Number(u.is_active) ? 'danger' : '')}</footer>`,
            );
        } catch (e) {
            toast(e.message, true);
        }
    }
    async function viewOrder(id) {
        try {
            const o = await api('order', { id });
            showDrawer(
                dialogHeader('Order management', `Order #LH-${String(o.id).padStart(4, '0')}`) +
                    `<div class="admin-dialog-body"><div class="admin-detail-heading">${avatar(o.name, o.user_id)}<div><h3>${esc(o.name)}</h3><p>${esc(o.email)}</p></div></div><dl class="admin-detail-facts"><div><dt>Status</dt><dd>${badge(o.status)}</dd></div><div><dt>Placed on</dt><dd>${date(o.created_at)}</dd></div><div><dt>Delivery phone</dt><dd>${esc(o.phone)}</dd></div><div><dt>Delivery address</dt><dd>${esc(o.shipping_address)}</dd></div>${o.delivery_lat != null && o.delivery_lng != null ? `<div><dt>Delivery meeting point</dt><dd>${esc(o.meeting_point_label || 'Pinned location')} · <a href="https://www.google.com/maps/dir/?api=1&amp;destination=${encodeURIComponent(`${o.delivery_lat},${o.delivery_lng}`)}" target="_blank" rel="noopener noreferrer">Open directions in Google Maps</a></dd></div>` : ''}</dl><h3 class="admin-detail-section">Purchased items</h3><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Product</th><th>Quantity</th><th>Unit price · JOD</th><th>Line total · JOD</th></tr></thead><tbody>${o.items.map((p) => `<tr><td><div class="admin-row-name">${thumb(p)}<span class="admin-primary-text">${esc(p.name)}</span></div></td><td>${num(p.quantity)}</td><td>${money(p.unit_price)}</td><td>${money(Number(p.quantity) * Number(p.unit_price))}</td></tr>`).join('')}</tbody></table></div><dl class="admin-detail-facts" style="margin-top:25px"><div><dt>Promo discount</dt><dd>${money(o.promo_discount)} JOD</dd></div><div><dt>Order total</dt><dd><strong>${money(o.total_amount)} JOD</strong></dd></div></dl><p class="admin-privacy-note">Amounts are the stored order values. No payment, refund or stock action is performed by this view.</p></div><footer class="admin-dialog-footer">${button('View customer', 'user', `data-id="${Number(o.user_id)}"`, 'secondary')}${button('Close', 'close-drawer')}</footer>`,
            );
        } catch (e) {
            toast(e.message, true);
        }
    }
    function categoryModal(sub = false, id = null, categoryId = null) {
        const item = id
            ? (sub ? meta.subcategories : meta.categories).find((x) => String(x.id) === String(id))
            : null;
        const cat =
            categoryId ||
            item?.category_id ||
            state.query.category_id ||
            $('[name="category_id"]', drawer)?.value ||
            '';
        if (sub && !cat) {
            toast('Choose or create a category first.', true);
            return;
        }
        showModal(
            dialogHeader(
                'Catalog organization',
                `${id ? 'Rename' : 'New'} ${sub ? 'subcategory' : 'category'}`,
                'modal',
            ) +
                `<form data-form="${sub ? 'subcategory' : 'category'}"><div class="admin-dialog-body"><div class="admin-form-error" role="alert" hidden></div><input type="hidden" name="id" value="${esc(item?.id || '')}">${sub ? `<input type="hidden" name="category_id" value="${Number(cat)}"><p class="admin-preview-note">Inside ${esc(meta.categories.find((c) => String(c.id) === String(cat))?.name || 'selected category')}</p>` : ''}${field(sub ? 'Subcategory name' : 'Category name', 'name', item?.name || '', 'text', 'required maxlength="100"')}${!sub ? '<p class="admin-preview-note">Create a subcategory inside this collection, then add products or move existing products into it.</p>' : ''}</div><footer class="admin-dialog-footer">${button('Cancel', 'close-modal', '', 'secondary')}<button class="admin-button" type="submit">${id ? 'Save name' : 'Create'}</button></footer></form>`,
        );
    }
    function moveModal() {
        if (!selected.size) return;
        const c = state.query.category_id || '';
        showModal(
            dialogHeader(
                'Organize your catalog',
                `Move ${selected.size} selected product${selected.size === 1 ? '' : 's'}`,
                'modal',
            ) +
                `<form data-form="move"><div class="admin-dialog-body"><div class="admin-form-error" role="alert" hidden></div>${combo('category_id', 'Category', meta.categories, c)}${combo(
                    'subcategory_id',
                    'Subcategory',
                    meta.subcategories.filter((s) => String(s.category_id) === String(c)),
                    '',
                )}<p class="admin-preview-note">Existing products are reassigned. Their IDs, prices, stock and order history stay the same.</p></div><footer class="admin-dialog-footer">${button('Cancel', 'close-modal', '', 'secondary')}<button class="admin-button" type="submit">Move products</button></footer></form>`,
        );
    }
    function promoModal(id = null) {
        const p = rows.find((x) => String(x.id) === String(id)) || {};
        showModal(
            dialogHeader('Promotions', id ? 'Edit promo code' : 'Create promo code', 'modal') +
                `<form data-form="promo"><div class="admin-dialog-body"><div class="admin-form-error" role="alert" hidden></div><input name="id" type="hidden" value="${esc(p.id || '')}">${field('Promo code', 'code', p.code || '', 'text', 'required minlength="3" maxlength="100" pattern="[A-Za-z0-9_-]+"')}<div class="admin-form-row"><label class="admin-field"><span>Discount type</span><select class="admin-input" name="discount_type"><option value="percentage" ${p.discount_type === 'percentage' ? 'selected' : ''}>Percentage</option><option value="fixed" ${p.discount_type === 'fixed' ? 'selected' : ''}>Fixed · JOD</option></select></label>${field('Discount value', 'discount_value', p.discount_value || '', 'number', 'required min="0.01" step="0.01" max="99999999.99"')}</div>${field(
                    'Expires at (optional)',
                    'expires_at',
                    String(p.expires_at || '')
                        .replace(' ', 'T')
                        .slice(0, 16),
                    'datetime-local',
                )}<p class="admin-preview-note">Expiration times use Amman local time.</p></div><footer class="admin-dialog-footer">${button('Cancel', 'close-modal', '', 'secondary')}<button class="admin-button" type="submit">Save promo code</button></footer></form>`,
        );
    }
    function confirmAction(heading, description, action, values = {}) {
        showModal(
            dialogHeader('Please confirm', heading, 'modal') +
                `<form data-form="confirm" data-api="${action}"><div class="admin-dialog-body"><p class="admin-preview-note">${esc(description)}</p><div class="admin-form-error" role="alert" hidden></div>${Object.entries(
                    values,
                )
                    .map(([k, v]) => `<input type="hidden" name="${esc(k)}" value="${esc(v)}">`)
                    .join(
                        '',
                    )}</div><footer class="admin-dialog-footer">${button('Cancel', 'close-modal', '', 'secondary')}<button class="admin-button danger" type="submit">Confirm</button></footer></form>`,
        );
    }
    function globalSearch() {
        showModal(
            dialogHeader('Find your next action', 'Search workspace', 'modal') +
                `<div class="admin-dialog-body"><label class="admin-search-field">${icon('search')}<input type="search" data-global-search placeholder="Search products, customers or order IDs…" maxlength="150" aria-label="Search workspace"></label><div class="admin-global-results" id="admin-global-results" style="margin-top:14px">${Object.entries(
                    names,
                )
                    .map(
                        ([k, v]) =>
                            `<button class="admin-global-result" data-nav="${k}">${icon(k === 'users' ? 'users' : k === 'orders' ? 'bag' : k === 'settings' ? 'settings' : 'grid')}<span>${esc(v)}<small>Go to ${esc(v.toLowerCase())}</small></span>${icon('chevron')}</button>`,
                    )
                    .join('')}</div></div>`,
        );
        $('[data-global-search]').focus();
    }
    function exportUsers() {
        const headers = ['Name', 'Email', 'Phone', 'Registered', 'Orders', 'Status'];
        const safe = (v) => {
            let s = String(v ?? '');
            if (/^[\s=+@-]/u.test(s)) s = "'" + s;
            return '"' + s.replaceAll('"', '""') + '"';
        };
        const csv = [
            headers,
            ...rows.map((u) => [
                u.name,
                u.email,
                u.phone,
                u.created_at,
                u.orders_count,
                Number(u.is_active) ? 'Active' : 'Inactive',
            ]),
        ]
            .map((r) => r.map(safe).join(','))
            .join('\r\n');
        const url = URL.createObjectURL(new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' }));
        const a = document.createElement('a');
        a.href = url;
        a.download = 'loothub-customers-page-' + (state.query.page || 1) + '.csv';
        a.click();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    }
    async function reloadMeta() {
        meta = await api('metadata');
    }
    function updateBulk() {
        const area = $('#admin-bulk');
        if (area) {
            area.hidden = !selected.size;
            $('#admin-selected-count').textContent = selected.size;
        }
        const all = $('[data-select-all]');
        if (all) {
            all.checked = rows.length > 0 && rows.every((p) => selected.has(String(p.id)));
            all.indeterminate = selected.size > 0 && !all.checked;
        }
    }
    let globalSearchVersion = 0,
        globalSearchTimer;
    document.addEventListener('click', async (event) => {
        const nav = event.target.closest('[data-nav]');
        if (nav) {
            event.preventDefault();
            navigate(nav.dataset.nav);
            return;
        }
        if (!event.target.closest('.admin-combo'))
            $$('.admin-combo-menu').forEach((m) => {
                m.hidden = true;
                $('.admin-combo-trigger', m.parentElement)?.setAttribute('aria-expanded', 'false');
            });
        const el = event.target.closest('[data-action]');
        if (!el || el.disabled) return;
        event.preventDefault();
        const { action, id } = el.dataset;
        try {
            switch (action) {
                case 'menu':
                    mobile(!$('#admin-sidebar').classList.contains('is-open'));
                    break;
                case 'search':
                    globalSearch();
                    break;
                case 'retry':
                    loadPage();
                    break;
                case 'range':
                    state.range = el.dataset.range;
                    loadPage(false);
                    break;
                case 'paginate':
                    selected.clear();
                    state.query.page = Number(el.dataset.p);
                    syncUrl();
                    loadPage(false);
                    break;
                case 'user':
                    closeDialog(modal);
                    await viewUser(id);
                    break;
                case 'product':
                    closeDialog(modal);
                    await editProduct(id);
                    break;
                case 'order':
                    closeDialog(modal);
                    await viewOrder(id);
                    break;
                case 'new-product':
                    await editProduct();
                    break;
                case 'close-drawer':
                    closeDialog(drawer);
                    break;
                case 'close-modal':
                    closeDialog(modal);
                    break;
                case 'new-category':
                    categoryModal();
                    break;
                case 'edit-category':
                    categoryModal(false, id);
                    break;
                case 'new-subcategory':
                    categoryModal(true, null, el.dataset.category);
                    break;
                case 'edit-subcategory':
                    categoryModal(true, id);
                    break;
                case 'delete-category':
                    confirmAction(
                        'Delete this category?',
                        'Only an empty category can be deleted. Move products and remove its empty subcategories first.',
                        'category-delete',
                        { id, type: 'category' },
                    );
                    break;
                case 'delete-subcategory':
                    confirmAction(
                        'Delete this subcategory?',
                        'Move every product, including archived products, before deleting.',
                        'category-delete',
                        { id, type: 'subcategory' },
                    );
                    break;
                case 'category':
                    state.query = { page: 1, category_id: id };
                    selected.clear();
                    syncUrl();
                    loadPage(false);
                    break;
                case 'subcategory-filter':
                    state.query.subcategory_id = id;
                    state.query.page = 1;
                    selected.clear();
                    syncUrl();
                    loadPage(false);
                    break;
                case 'clear-selection':
                    selected.clear();
                    $$('[data-product-select]').forEach((x) => (x.checked = false));
                    updateBulk();
                    break;
                case 'move-products':
                    moveModal();
                    break;
                case 'user-status':
                    confirmAction(
                        Number(el.dataset.active) ? 'Activate this account?' : 'Deactivate this account?',
                        Number(el.dataset.active)
                            ? 'This customer can sign in again. Older sessions stay expired.'
                            : 'The customer will be unable to sign in, and existing sessions will fail their next authenticated request.',
                        'user-status',
                        { id, active: el.dataset.active },
                    );
                    break;
                case 'archive':
                    confirmAction(
                        'Archive this product?',
                        'It will disappear from the storefront. Its order history stays intact. You can restore it from the Archived filter.',
                        'product-archive',
                        { id },
                    );
                    break;
                case 'customer-orders':
                    navigate('orders', { user_id: id });
                    break;
                case 'all-orders':
                    navigate('orders');
                    break;
                case 'top-products':
                    navigate('products', { sort: 'sold-desc' });
                    break;
                case 'export-users':
                    exportUsers();
                    break;
                case 'new-promo':
                    promoModal();
                    break;
                case 'promo':
                    promoModal(id);
                    break;
                case 'expire-promo':
                    confirmAction(
                        'Expire this code?',
                        'The code will stop being valid for new checkouts. Existing order records are preserved.',
                        'promo-expire',
                        { id },
                    );
                    break;
                case 'delete-review':
                    confirmAction(
                        'Delete this review?',
                        'This permanently removes the selected review from the product page.',
                        'review-delete',
                        { id },
                    );
                    break;
                case 'combo-toggle': {
                    const box = el.closest('.admin-combo'),
                        menu = $('.admin-combo-menu', box),
                        open = menu.hidden;
                    $$('.admin-combo-menu').forEach((x) => {
                        x.hidden = true;
                        $('.admin-combo-trigger', x.parentElement)?.setAttribute('aria-expanded', 'false');
                    });
                    menu.hidden = !open;
                    el.setAttribute('aria-expanded', String(open));
                    if (open) $('input[type="search"]', menu).focus();
                    break;
                }
                case 'combo-option': {
                    const box = el.closest('.admin-combo');
                    $('input[type="hidden"]', box).value = id;
                    $('.admin-combo-trigger span', box).textContent = el.dataset.label;
                    $('.admin-combo-menu', box).hidden = true;
                    $('.admin-combo-trigger', box).setAttribute('aria-expanded', 'false');
                    $$('[role="option"]', box).forEach((x) =>
                        x.setAttribute('aria-selected', String(x === el)),
                    );
                    if (box.dataset.combo === 'category_id') {
                        const form = box.closest('form');
                        const sub = $('[data-combo="subcategory_id"]', form);
                        const subs = meta.subcategories.filter((x) => String(x.category_id) === String(id));
                        sub.outerHTML = combo(
                            'subcategory_id',
                            'Subcategory',
                            subs,
                            subs.length === 1 ? subs[0].id : '',
                            form.dataset.form === 'product'
                                ? `<button type="button" class="admin-link" data-action="new-subcategory" data-category="${Number(id)}">+ Create subcategory</button>`
                                : '',
                        );
                    }
                    updatePreview();
                    break;
                }
            }
        } catch (error) {
            toast(error.message, true);
        }
    });
    document.addEventListener('input', (event) => {
        const el = event.target;
        if (el.matches('[data-filter="q"]')) {
            clearTimeout(searchTimer);
            const value = el.value;
            searchTimer = setTimeout(() => {
                state.query.q = value;
                state.query.page = 1;
                selected.clear();
                syncUrl();
                loadPage(false);
            }, 300);
        }
        if (el.matches('[data-category-search]'))
            $$('[data-category-label]').forEach(
                (b) => (b.hidden = !b.dataset.categoryLabel.includes(el.value.trim().toLowerCase())),
            );
        if (el.matches('[data-combo-search]'))
            $$('.admin-combo-option', el.closest('.admin-combo')).forEach(
                (b) => (b.hidden = !b.dataset.label.toLowerCase().includes(el.value.trim().toLowerCase())),
            );
        if (el.closest('[data-form="product"]') && !el.matches('[type="file"]')) updatePreview();
        if (el.matches('[data-global-search]')) {
            clearTimeout(globalSearchTimer);
            const q = el.value.trim(),
                version = ++globalSearchVersion;
            if (!q) {
                $('#admin-global-results').innerHTML = empty(
                    'Search your workspace',
                    'Type a customer, product name or order ID.',
                );
                return;
            }
            globalSearchTimer = setTimeout(async () => {
                try {
                    const [products, users, orders] = await Promise.all([
                        api('products', { q }),
                        api('users', { q }),
                        api('orders', { q }),
                    ]);
                    if (version !== globalSearchVersion || !modal.open) return;
                    $('#admin-global-results').innerHTML =
                        [
                            ...products.items
                                .slice(0, 4)
                                .map(
                                    (p) =>
                                        `<button class="admin-global-result" data-action="product" data-id="${Number(p.id)}">${icon('box')}<span>${esc(p.name)}<small>Product · ${money(p.price)} JOD</small></span></button>`,
                                ),
                            ...users.items
                                .slice(0, 3)
                                .map(
                                    (u) =>
                                        `<button class="admin-global-result" data-action="user" data-id="${Number(u.id)}">${icon('users')}<span>${esc(u.name)}<small>Customer · ${esc(u.email)}</small></span></button>`,
                                ),
                            ...orders.items
                                .slice(0, 3)
                                .map(
                                    (o) =>
                                        `<button class="admin-global-result" data-action="order" data-id="${Number(o.id)}">${icon('bag')}<span>Order #${Number(o.id)}<small>${esc(o.name)}</small></span></button>`,
                                ),
                        ].join('') || empty('No results', 'Try a customer name, product or order ID.');
                } catch (error) {
                    toast(error.message, true);
                }
            }, 250);
        }
    });
    document.addEventListener('change', async (event) => {
        const el = event.target;
        if (el.matches('select[data-filter]')) {
            state.query[el.dataset.filter] = el.value;
            state.query.page = 1;
            selected.clear();
            syncUrl();
            loadPage(false);
        }
        if (el.matches('[data-product-select]')) {
            if (el.checked) selected.add(el.dataset.productSelect);
            else selected.delete(el.dataset.productSelect);
            updateBulk();
        }
        if (el.matches('[data-select-all]')) {
            rows.forEach((p) => {
                if (el.checked) selected.add(String(p.id));
                else selected.delete(String(p.id));
            });
            $$('[data-product-select]').forEach((c) => (c.checked = el.checked));
            updateBulk();
        }
        if (el.name === 'image_file') {
            if (previewObjectUrl) URL.revokeObjectURL(previewObjectUrl);
            previewObjectUrl = null;
            const file = el.files?.[0];
            if (file) {
                if (
                    !['image/png', 'image/jpeg', 'image/webp'].includes(file.type) ||
                    file.size > 5 * 1024 * 1024
                ) {
                    el.value = '';
                    toast('Choose a JPG, PNG or WebP up to 5 MB.', true);
                } else previewObjectUrl = URL.createObjectURL(file);
            }
            updatePreview();
        }
        if (el.matches('[data-admin-photo]')) {
            const file = el.files?.[0];
            if (!file) return;
            try {
                el.disabled = true;
                const data = new FormData();
                data.set('image', file);
                const image = await api('upload', data, 'POST');
                const result = await api('avatar-save', { path: image.path }, 'POST');
                setAdminIdentity(B.user, result.avatar);
                toast('Profile photo updated.');
            } catch (error) {
                toast(error.message, true);
            } finally {
                el.disabled = false;
                el.value = '';
            }
        }
    });
    document.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!form.dataset.form) return;
        event.preventDefault();
        if (form.dataset.busy === 'true') return;
        const submitter = event.submitter || $('button[type="submit"]', form);
        const original = submitter?.innerHTML;
        const values = Object.fromEntries([...new FormData(form)].filter(([, v]) => !(v instanceof File)));
        form.dataset.busy = 'true';
        if (submitter) {
            submitter.disabled = true;
            submitter.textContent = 'Saving…';
        }
        const errorBox = $('.admin-form-error', form);
        if (errorBox) errorBox.hidden = true;
        try {
            const kind = form.dataset.form;
            let result;
            if (kind === 'product') {
                const file = form.elements.namedItem('image_file')?.files?.[0];
                if (file) {
                    const data = new FormData();
                    data.set('image', file);
                    const image = await api('upload', data, 'POST');
                    values.image = image.url;
                }
                result = await api('product-save', values, 'POST');
                closeDialog(drawer);
                await reloadMeta();
                toast(values.id ? 'Product updated.' : 'Product created.');
                await loadPage(false);
            } else if (kind === 'category' || kind === 'subcategory') {
                result = await api(kind + '-save', values, 'POST');
                closeDialog(modal);
                await reloadMeta();
                if ($('[data-form="product"]', drawer)) {
                    const category = kind === 'category' ? result.id : result.category_id;
                    refreshCombos(category, kind === 'subcategory' ? result.id : '');
                }
                toast(kind === 'category' ? 'Category saved.' : 'Subcategory saved.');
                if (state.page === 'categories') await loadPage(false);
            } else if (kind === 'move') {
                await api(
                    'products-move',
                    { ids: [...selected], subcategory_id: values.subcategory_id },
                    'POST',
                );
                selected.clear();
                closeDialog(modal);
                await reloadMeta();
                toast('Selected products moved.');
                await loadPage(false);
            } else if (kind === 'promo') {
                await api('promo-save', values, 'POST');
                closeDialog(modal);
                toast('Promo code saved.');
                await loadPage(false);
            } else if (kind === 'settings') {
                result = await api('settings-save', values, 'POST');
                setAdminIdentity(result);
                form.reset();
                toast('Account settings saved.');
                await loadPage(false);
            } else if (kind === 'confirm') {
                await api(form.dataset.api, values, 'POST');
                closeDialog(modal);
                toast('Changes saved.');
                if (form.dataset.api === 'user-status' && drawer.open) {
                    await viewUser(values.id);
                }
                if (form.dataset.api === 'category-delete') {
                    state.query = { page: 1 };
                    await reloadMeta();
                }
                if (form.dataset.api === 'product-archive') await reloadMeta();
                await loadPage(false);
            }
        } catch (error) {
            formError(form, error.message);
        } finally {
            form.dataset.busy = 'false';
            if (submitter) {
                submitter.disabled = false;
                submitter.innerHTML = original;
            }
        }
    });
    function animateCounters() {
        if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        $$('[data-count]', main).forEach((el) => {
            const value = Number(el.dataset.count),
                decimal = Number(el.dataset.decimal);
            const start = performance.now();
            function frame(now) {
                if (!el.isConnected) return;
                const t = Math.min((now - start) / 650, 1),
                    v = value * (1 - (1 - t) ** 3);
                el.textContent = decimal ? money(v) : num(Math.round(v));
                if (t < 1) requestAnimationFrame(frame);
            }
            requestAnimationFrame(frame);
        });
        $$('.admin-metric', main).forEach((el) =>
            el.addEventListener('pointermove', (event) => {
                const r = el.getBoundingClientRect();
                el.style.setProperty('--mx', event.clientX - r.left + 'px');
                el.style.setProperty('--my', event.clientY - r.top + 'px');
            }),
        );
    }
    function bindChart() {
        const wrap = $('.admin-chart-wrap', main);
        if (!wrap) return;
        const tooltip = $('.admin-chart-tooltip', wrap);
        const show = (event) => {
            const point = event.target.closest('[data-chart-label]');
            if (!point) return;
            const r = point.getBoundingClientRect(),
                parent = wrap.getBoundingClientRect();
            tooltip.innerHTML = `${esc(point.dataset.chartLabel)}<strong>${esc(point.dataset.chartValue)} JOD</strong>`;
            tooltip.hidden = false;
            tooltip.style.left = Math.max(0, Math.min(parent.width - 120, r.left - parent.left - 45)) + 'px';
            tooltip.style.top = Math.max(0, r.top - parent.top - 58) + 'px';
        };
        wrap.addEventListener('pointerover', show);
        wrap.addEventListener('focusin', show);
        wrap.addEventListener('pointerleave', () => (tooltip.hidden = true));
        wrap.addEventListener('focusout', () => (tooltip.hidden = true));
    }
    // Password inputs cannot be accidentally submitted from browser autofill.
    document.addEventListener('toggle', (event) => {
        const panel = event.target;
        if (!(panel instanceof HTMLDetailsElement) || !panel.matches('[data-admin-password-panel]')) return;
        panel.querySelectorAll('[data-admin-password-field]').forEach((input) => {
            input.disabled = !panel.open;
            if (!panel.open) input.value = '';
        });
    }, true);

    // The admin and storefront share the same authenticated preference endpoint.
    document.querySelectorAll('[data-admin-pref]').forEach((button) => {
        button.addEventListener('click', async () => {
            const type = button.dataset.adminPref;
            const value = button.dataset.value;
            if (button.getAttribute('aria-pressed') === 'true') return;
            const previous = document.documentElement.dataset.lootTheme;
            if (type === 'theme') document.documentElement.dataset.lootTheme = value;
            button.disabled = true;
            try {
                const body = new URLSearchParams({
                    type, value, csrf_token: document.querySelector('[data-admin-logout] [name="csrf_token"]').value,
                });
                const response = await fetch(B.appUrl + '/preferences', {
                    method: 'POST', credentials: 'same-origin',
                    headers: { Accept: 'application/json' }, body,
                });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'Could not save preference.');
                if (type === 'lang') {
                    location.reload();
                    return;
                }
                document.querySelectorAll(`[data-admin-pref="${type}"]`).forEach((item) => {
                    item.setAttribute('aria-pressed', String(item.dataset.value === value));
                });
                toast('Appearance saved.');
            } catch (error) {
                if (type === 'theme') document.documentElement.dataset.lootTheme = previous;
                toast(error.message || 'Could not save preference.', true);
            } finally {
                button.disabled = false;
            }
        });
    });
    document.addEventListener('click', (event) => {
        const menu = document.querySelector('[data-admin-account-menu]');
        if (menu?.open && !menu.contains(event.target)) menu.open = false;
    });
    document.querySelector('[data-admin-logout]')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!window.confirm(window.LootI18n?.t('Are you sure you want to log out?') || 'Are you sure you want to log out?')) return;
        const form = event.currentTarget;
        const submit = form.querySelector('[type="submit"]');
        submit.disabled = true;
        try {
            const response = await fetch(form.action, {
                method: 'POST', credentials: 'same-origin',
                headers: { Accept: 'application/json' },
                body: new URLSearchParams(new FormData(form)),
            });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Could not log out.');
            location.href = result.data?.redirect || B.appUrl + '/login';
        } catch (error) {
            toast(error.message || 'Could not log out.', true);
            submit.disabled = false;
        }
    });

    drawer.addEventListener('close', () => {
        if (previewObjectUrl) {
            URL.revokeObjectURL(previewObjectUrl);
            previewObjectUrl = null;
        }
        editorProduct = null;
    });
    $('#admin-mobile-scrim').addEventListener('click', () => mobile(false));
    document.addEventListener('keydown', (event) => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            globalSearch();
        }
        if (event.key === 'Escape') {
            const accountMenu = document.querySelector('[data-admin-account-menu]');
            if (accountMenu) accountMenu.open = false;
            const open = $('.admin-combo-menu:not([hidden])');
            if (open) {
                event.preventDefault();
                open.hidden = true;
                $('.admin-combo-trigger', open.parentElement).setAttribute('aria-expanded', 'false');
            }
            mobile(false);
        }
        if (event.key === 'ArrowDown' && event.target.matches('[data-combo-search]')) {
            event.preventDefault();
            $('.admin-combo-option:not([hidden])', event.target.parentElement)?.focus();
        }
    });
    document.addEventListener(
        'error',
        (event) => {
            if (event.target instanceof HTMLImageElement) {
                const img = event.target;
                img.hidden = true;
                if (img.parentElement.classList.contains('admin-product-thumb'))
                    img.parentElement.insertAdjacentHTML('beforeend', icon('box'));
            }
        },
        true,
    );
    window.addEventListener('popstate', () => {
        const q = new URLSearchParams(location.search);
        const page = q.get('page') || 'overview';
        state.page = names[page] ? page : 'overview';
        state.query = Object.fromEntries(q);
        delete state.query.page;
        state.query.page = Number(q.get('p')) || 1;
        delete state.query.p;
        selected.clear();
        loadPage();
    });
    matchMedia('(max-width:700px)').addEventListener('change', () => mobile(false));
    async function start() {
        mobile(false);
        setAdminIdentity(B.user);
        try {
            await reloadMeta();
            const q = new URLSearchParams(location.search),
                page = q.get('page') || 'overview';
            const query = Object.fromEntries(q);
            delete query.page;
            query.page = Number(q.get('p')) || 1;
            delete query.p;
            state.page = names[page] ? page : 'overview';
            state.query = query;
            await loadPage();
        } catch (error) {
            mount(`<div class="admin-error" role="alert">${esc(error.message)}</div>`);
        }
    }
    start();
})();
