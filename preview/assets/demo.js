(() => {
    const now = new Date();
    const iso = (d) => new Date(d).toISOString().slice(0, 19).replace('T', ' ');
    const days = (n) => iso(now.getTime() - n * 86400000);
    const cat = [
        { id: 1, name: 'Anime & manga', products_count: 4 },
        { id: 2, name: 'Gaming', products_count: 3 },
        { id: 3, name: 'Comics & heroes', products_count: 3 },
        { id: 4, name: 'Originals', products_count: 2 },
    ];
    const sub = [
        { id: 1, category_id: 1, name: 'Naruto' },
        { id: 2, category_id: 1, name: 'Dragon Ball' },
        { id: 3, category_id: 2, name: 'PlayStation' },
        { id: 4, category_id: 2, name: 'God of War' },
        { id: 5, category_id: 3, name: 'Marvel' },
        { id: 6, category_id: 3, name: 'DC' },
        { id: 7, category_id: 4, name: 'LOOT HUB collection' },
    ];
    function picture(type, color) {
        const art =
            type === 'mug'
                ? '<path d="M27 29h38v31c0 13-38 13-38 0Zm38 4h7c15 0 15 20 0 20h-7"/>'
                : type === 'poster'
                  ? '<rect x="23" y="15" width="49" height="65" rx="2"/><path d="m31 66 16-34 17 34M33 52h30"/>'
                  : '<path d="m34 20-17 11 9 17 9-5v35h30V43l9 5 9-17-18-11c-8 8-23 8-31 0Z"/>';
        return (
            'data:image/svg+xml;base64,' +
            btoa(
                `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><radialGradient id="b"><stop stop-color="#352741"/><stop offset="1" stop-color="#17101f"/></radialGradient></defs><rect width="100" height="100" fill="url(#b)"/><g fill="${color}" stroke="#ffffff35" stroke-width="1.5">${art}</g><path d="m45 43 7 6-7 6 7 6" fill="none" stroke="#c8a4fc" stroke-width="2.5"/></svg>`,
            )
        );
    }
    const names = [
        'Naruto shadow hoodie',
        'Kratos collectors mug',
        'Batman midnight tee',
        'Goku energy poster',
        'PlayStation essentials tee',
        'Spider-Man classic mug',
        'LOOT HUB signature hoodie',
        'Akatsuki oversized tee',
        'God of War art print',
        'Marvel hero poster',
        'DualSense collectors mug',
        'LOOT HUB everyday tee',
    ];
    let products = names.map((name, i) => {
        const sid = [1, 4, 6, 2, 3, 5, 7, 1, 4, 5, 3, 7][i],
            s = sub.find((x) => x.id === sid);
        return {
            id: i + 1,
            name,
            description:
                'A considered addition to your collection. Premium quality, original fandom-inspired artwork and a finish made for everyday use.',
            price: [32, 12, 22, 15, 24, 14, 38, 28, 18, 16, 13, 25][i],
            discount_price: i === 0 ? 28 : null,
            stock_quantity: [18, 3, 24, 12, 0, 9, 16, 5, 10, 22, 4, 31][i],
            is_active: 1,
            created_at: days(i * 3),
            image: picture(
                name.includes('mug')
                    ? 'mug'
                    : name.includes('poster') || name.includes('print')
                      ? 'poster'
                      : 'tee',
                i % 3 === 0 ? '#423549' : i % 3 === 1 ? '#a49aaf' : '#24212d',
            ),
            category_id: s.category_id,
            subcategory_id: sid,
            subcategory_name: s.name,
            category_name: cat.find((c) => c.id === s.category_id).name,
            units_sold: [126, 89, 78, 66, 59, 48, 42, 37, 29, 23, 18, 11][i],
        };
    });
    const customerNames = [
        'Amelia Parker',
        'Omar Hassan',
        'Sofia Bennett',
        'Adam Khalil',
        'Maya Thompson',
        'Daniel Brooks',
        'Sara Ahmad',
        'Leo Mitchell',
        'Nour Saleh',
        'Oliver Hayes',
        'Layla Morgan',
        'Ethan Carter',
        'Yara Ibrahim',
        'Lucas Turner',
        'Hana Lewis',
    ];
    let customers = Array.from({ length: 34 }, (_, i) => ({
        id: i + 1,
        name: customerNames[i % 15] + (i >= 15 ? ' ' + String.fromCharCode(65 + Math.floor(i / 15)) : ''),
        email: customerNames[i % 15].toLowerCase().replaceAll(' ', '.') + (i >= 15 ? i : '') + '@example.com',
        phone: '+962 79 000 ' + String(1000 + i),
        is_active: i === 8 || i === 17 ? 0 : 1,
        created_at: days(i * 0.8),
        orders_count: i % 4 === 0 ? 0 : Math.max(1, 8 - (i % 8)),
        points: 100 + i * 5,
    }));
    const statuses = [
        'pending',
        'completed',
        'processing',
        'shipped',
        'completed',
        'completed',
        'processing',
        'cancelled',
    ];
    let orders = Array.from({ length: 46 }, (_, i) => {
        const u = customers[(i + 1) % customers.length];
        return {
            id: 1048 - i,
            user_id: u.id,
            name: u.name,
            email: u.email,
            phone: u.phone,
            shipping_address: 'Amman, Jordan · Example delivery address',
            total_amount: [54, 28, 76, 42, 32, 98, 24, 38][i % 8],
            promo_discount: i % 5 === 0 ? 5 : 0,
            status: statuses[i % 8],
            created_at: days(i * 0.6),
            items_count: (i % 3) + 1,
            items: [
                {
                    ...products[i % products.length],
                    quantity: (i % 3) + 1,
                    unit_price: products[i % products.length].price,
                },
            ],
        };
    });
    let promos = [
        {
            id: 1,
            code: 'WELCOME10',
            discount_type: 'percentage',
            discount_value: 10,
            expires_at: null,
            created_at: days(10),
        },
        {
            id: 2,
            code: 'LOOT5',
            discount_type: 'fixed',
            discount_value: 5,
            expires_at: days(-20),
            created_at: days(5),
        },
    ];
    let reviews = Array.from({ length: 13 }, (_, i) => ({
        id: i + 1,
        name: customers[i].name,
        product_name: products[i % 12].name,
        rating: i % 3 ? 5 : 4,
        comment: [
            'Great quality and the print looks even better in person.',
            'Arrived safely. A lovely addition to my collection.',
            'Exactly what I was looking for.',
        ][i % 3],
        created_at: days(i),
    }));
    let admin = {
        id: 99,
        name: 'Mohammad Altarifi',
        email: 'mohammad@example.com',
        phone: '+962 79 000 0000',
        has_password: 1,
        avatar: '',
    };
    const paginate = (items, q) => {
        const pages = Math.max(1, Math.ceil(items.length / 10)),
            page = Math.max(1, Math.min(Number(q.page) || 1, pages));
        return {
            items: items.slice((page - 1) * 10, page * 10),
            total: items.length,
            page,
            pages,
            per_page: 10,
        };
    };
    const period = (item, q) =>
        !q.period ||
        new Date(item.created_at.replace(' ', 'T')) >=
            new Date(
                q.period === 'today'
                    ? new Date().setHours(0, 0, 0, 0)
                    : now.getTime() - (q.period === 'week' ? 7 : 30) * 86400000,
            );
    function filteredProducts(q) {
        let a = products.filter(
            (p) =>
                (q.active === 'all' || Number(p.is_active) === (q.active === '0' ? 0 : 1)) &&
                (!q.q || (p.name + ' ' + p.description).toLowerCase().includes(q.q.toLowerCase())) &&
                (!q.category_id || String(p.category_id) === String(q.category_id)) &&
                (!q.subcategory_id || String(p.subcategory_id) === String(q.subcategory_id)) &&
                period(p, q) &&
                (!q.stock ||
                    (q.stock === 'attention'
                        ? p.stock_quantity <= 5
                        : q.stock === 'low'
                          ? p.stock_quantity > 0 && p.stock_quantity <= 5
                          : p.stock_quantity === 0)),
        );
        if (q.sort === 'price-desc')
            a.sort((a, b) => (b.discount_price ?? b.price) - (a.discount_price ?? a.price));
        if (q.sort === 'price-asc')
            a.sort((a, b) => (a.discount_price ?? a.price) - (b.discount_price ?? b.price));
        if (q.sort === 'sold-desc') a.sort((a, b) => b.units_sold - a.units_sold);
        if (q.sort === 'sold-asc') a.sort((a, b) => a.units_sold - b.units_sold);
        if (q.sort === 'oldest') a.reverse();
        return a;
    }
    window.AdminDemo = {
        async request(name, q = {}, method = 'GET') {
            await new Promise((resolve) => setTimeout(resolve, 100));
            if (method === 'POST') {
                if (name === 'product-save') {
                    if (!q.name || !q.description || !q.category_id || !q.subcategory_id)
                        throw new Error(
                            'Complete the product details and choose a category and subcategory.',
                        );
                    const p = {
                        ...q,
                        id: q.id ? Number(q.id) : products.length + 1,
                        price: Number(q.price),
                        discount_price: q.discount_price === '' ? null : Number(q.discount_price),
                        category_id: Number(q.category_id),
                        subcategory_id: Number(q.subcategory_id),
                        stock_quantity: Number(q.stock_quantity),
                        is_active: Number(q.is_active),
                        units_sold: 0,
                        created_at: days(0),
                    };
                    if (p.discount_price !== null && p.discount_price >= p.price)
                        throw new Error('The sale price must be lower than the regular price.');
                    const s = sub.find((s) => String(s.id) === String(q.subcategory_id));
                    p.category_name = cat.find((c) => String(c.id) === String(q.category_id)).name;
                    p.subcategory_name = s.name;
                    const old = products.findIndex((x) => x.id === p.id);
                    if (old >= 0) products[old] = { ...products[old], ...p };
                    else products.unshift(p);
                    return p;
                }
                if (name === 'product-archive') {
                    products.find((p) => p.id === Number(q.id)).is_active = 0;
                    return {};
                }
                if (name === 'user-status') {
                    customers.find((u) => u.id === Number(q.id)).is_active = Number(q.active);
                    return {};
                }
                if (name === 'category-save') {
                    const c = q.id
                        ? cat.find((c) => c.id === Number(q.id))
                        : { id: Math.max(...cat.map((x) => x.id)) + 1, products_count: 0 };
                    c.name = q.name;
                    if (!q.id) cat.push(c);
                    return c;
                }
                if (name === 'subcategory-save') {
                    const s = q.id
                        ? sub.find((s) => s.id === Number(q.id))
                        : { id: Math.max(...sub.map((s) => s.id)) + 1, category_id: Number(q.category_id) };
                    s.name = q.name;
                    if (!q.id) sub.push(s);
                    return s;
                }
                if (name === 'products-move') {
                    const s = sub.find((x) => x.id === Number(q.subcategory_id));
                    if (!s) throw new Error('Choose a subcategory.');
                    products
                        .filter((p) => q.ids.includes(String(p.id)))
                        .forEach((p) =>
                            Object.assign(p, {
                                subcategory_id: s.id,
                                subcategory_name: s.name,
                                category_id: s.category_id,
                                category_name: cat.find((c) => c.id === s.category_id).name,
                            }),
                        );
                    return {};
                }
                if (name === 'category-delete') {
                    if (q.type === 'subcategory') {
                        if (products.some((p) => p.subcategory_id === Number(q.id)))
                            throw new Error('Move all products before deleting this subcategory.');
                        sub.splice(
                            sub.findIndex((x) => x.id === Number(q.id)),
                            1,
                        );
                    } else {
                        if (sub.some((s) => s.category_id === Number(q.id)))
                            throw new Error('Remove empty subcategories first.');
                        cat.splice(
                            cat.findIndex((x) => x.id === Number(q.id)),
                            1,
                        );
                    }
                    return {};
                }
                if (name === 'promo-save') {
                    const p = { ...q, id: q.id ? Number(q.id) : promos.length + 1 };
                    const i = promos.findIndex((x) => x.id === p.id);
                    if (i >= 0) promos[i] = p;
                    else promos.unshift(p);
                    return p;
                }
                if (name === 'promo-expire') {
                    promos.find((x) => x.id === Number(q.id)).expires_at = days(1);
                    return {};
                }
                if (name === 'review-delete') {
                    reviews = reviews.filter((x) => x.id !== Number(q.id));
                    return {};
                }
                if (name === 'settings-save') {
                    admin = { ...admin, name: q.name, phone: q.phone };
                    return admin;
                }
                if (name === 'upload') {
                    const image = q.get('image');
                    return { path: 'preview-upload', url: URL.createObjectURL(image) };
                }
                if (name === 'avatar-save') {
                    admin.avatar = '';
                    return { avatar: '' };
                }
            }
            if (name === 'metadata')
                return {
                    categories: cat.map((c) => ({
                        ...c,
                        products_count: products.filter((p) => p.category_id === c.id && p.is_active).length,
                    })),
                    subcategories: sub,
                };
            if (name === 'products') return paginate(filteredProducts(q), q);
            if (name === 'product') return products.find((p) => p.id === Number(q.id));
            if (name === 'users')
                return {
                    ...paginate(
                        customers.filter(
                            (u) =>
                                (!q.q ||
                                    (u.name + ' ' + u.email + ' ' + u.phone)
                                        .toLowerCase()
                                        .includes(q.q.toLowerCase())) &&
                                period(u, q) &&
                                (!q.orders || (q.orders === 'yes' ? u.orders_count > 0 : !u.orders_count)) &&
                                (q.active === '' ||
                                    q.active === undefined ||
                                    String(u.is_active) === q.active),
                        ),
                        q,
                    ),
                    summary: {
                        total: customers.length,
                        active: customers.filter((x) => x.is_active).length,
                        today: 2,
                    },
                };
            if (name === 'user') {
                const u = customers.find((u) => u.id === Number(q.id));
                return {
                    ...u,
                    orders: orders.filter((o) => o.user_id === u.id),
                    summary: { orders_count: u.orders_count, completed_value: 168 },
                };
            }
            if (name === 'orders')
                return paginate(
                    orders.filter(
                        (o) =>
                            (!q.q ||
                                (o.name + ' ' + o.email + ' ' + o.id)
                                    .toLowerCase()
                                    .includes(q.q.toLowerCase())) &&
                            (!q.status || o.status === q.status) &&
                            (!q.user_id || o.user_id === Number(q.user_id)) &&
                            period(o, q),
                    ),
                    q,
                );
            if (name === 'order') return orders.find((o) => o.id === Number(q.id));
            if (name === 'reviews')
                return paginate(
                    reviews.filter(
                        (r) =>
                            !q.q ||
                            (r.name + ' ' + r.product_name + ' ' + r.comment)
                                .toLowerCase()
                                .includes(q.q.toLowerCase()),
                    ),
                    q,
                );
            if (name === 'promos') return { items: promos };
            if (name === 'settings') return admin;
            if (name === 'overview') {
                const n = q.range === '7d' ? 7 : q.range === '30d' ? 30 : 12;
                const series = Array.from({ length: n }, (_, i) => {
                    const d = new Date(now);
                    if (n === 12) {
                        d.setDate(1);
                        d.setMonth(now.getMonth() - 11 + i);
                    } else d.setDate(now.getDate() - n + 1 + i);
                    const x =
                        n === 12
                            ? [420, 650, 580, 1100, 900, 1800, 1600, 2240, 1970, 2690, 2860, 3240][i]
                            : 400 + Math.round((i + 1) * 15 + Math.sin(i * 1.1) * 100);
                    return {
                        date: d.toISOString().slice(0, n === 12 ? 7 : 10),
                        label: d.toLocaleDateString(
                            'en-US',
                            n === 12 ? { month: 'short' } : { month: 'short', day: 'numeric' },
                        ),
                        completed: x,
                        ordered: x * 1.23 + 130,
                        orders: n === 12 ? [1, 1, 2, 2, 2, 4, 3, 5, 4, 6, 7, 9][i] : i % 3 === 0 ? 3 : 1,
                        customers: n === 12 ? [1, 1, 2, 2, 2, 3, 2, 4, 3, 4, 5, 5][i] : i % 5 === 0 ? 1 : 0,
                    };
                });
                return {
                    stats: {
                        revenue: 20050,
                        customers: customers.length,
                        products: products.filter((p) => p.is_active).length,
                        orders: orders.length,
                        pending: 8,
                        low_stock: 4,
                    },
                    series,
                    recent_orders: orders.slice(0, 5),
                    top_products: products
                        .filter((p) => p.is_active)
                        .sort((a, b) => b.units_sold - a.units_sold)
                        .slice(0, 4),
                    status: [],
                };
            }
            throw new Error('Unknown preview action.');
        },
    };
})();
