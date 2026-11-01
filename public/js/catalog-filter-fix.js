(() => {
  'use strict';
  // Independent, cache-busted catalog controller. It works even if an old user.js is cached.
  const boot = () => {
    const catalog = document.querySelector('[data-user-catalog]');
    if (!catalog) return;
    const cards = Array.from(catalog.querySelectorAll('[data-user-product-card]'));
    const categoryButtons = Array.from(document.querySelectorAll('[data-user-category]'));
    const subcategoryButtons = Array.from(document.querySelectorAll('[data-user-subcategory]'));
    const typeButtons = Array.from(document.querySelectorAll('[data-user-type]'));
    const subcategoryList = document.querySelector('[data-user-subcategory-list]');
    const searchInput = document.querySelector('[data-user-catalog-search]');
    const searchForm = document.querySelector('[data-user-search-form]');
    const sortSelect = document.querySelector('[data-user-sort]');
    const resultCount = document.querySelector('[data-user-result-count]');
    const activeLabel = document.querySelector('[data-user-active-category]');
    const emptyState = document.querySelector('[data-user-no-results]');
    const initialOrder = new Map(cards.map((card, i) => [card, i]));
    let category = 'all', subcategory = 'all', type = 'all';
    let pending;

    const setPressed = (buttons, dataKey, selected) => {
      for (const button of buttons) button.setAttribute('aria-pressed', String(button.dataset[dataKey] === selected));
    };
    const refreshSubcategories = () => {
      if (subcategoryList) subcategoryList.hidden = category === 'all';
      for (const button of subcategoryButtons) {
        button.hidden = button.dataset.subcategoryId !== 'all' && button.dataset.categoryId !== category;
      }
    };
    const apply = () => {
      clearTimeout(pending);
      const query = (searchInput?.value || '').trim().toLocaleLowerCase();
      const visible = [];
      for (const card of cards) {
        const matches = (category === 'all' || card.dataset.categoryId === category)
          && (subcategory === 'all' || card.dataset.subcategoryId === subcategory)
          && (type === 'all' || card.dataset.typeId === type)
          && (!query || [card.dataset.name, card.dataset.categoryName, card.dataset.subcategoryName]
            .some((value) => (value || '').toLocaleLowerCase().includes(query)));
        card.hidden = !matches;
        if (matches) visible.push(card);
      }
      const sort = sortSelect?.value || 'newest';
      visible.sort((a, b) => {
        if (sort === 'price-asc') return Number(a.dataset.price) - Number(b.dataset.price);
        if (sort === 'price-desc') return Number(b.dataset.price) - Number(a.dataset.price);
        if (sort === 'name') return (a.dataset.name || '').localeCompare(b.dataset.name || '');
        return initialOrder.get(a) - initialOrder.get(b);
      });
      visible.forEach((card) => catalog.appendChild(card));
      if (resultCount) resultCount.textContent = String(visible.length);
      if (emptyState) emptyState.hidden = visible.length !== 0;
      if (activeLabel) {
        const c = categoryButtons.find((b) => b.dataset.categoryId === category);
        const s = subcategoryButtons.find((b) => b.dataset.subcategoryId === subcategory);
        const t = typeButtons.find((b) => b.dataset.typeId === type);
        activeLabel.textContent = [c?.dataset.categoryName || 'All products',
          ...(subcategory === 'all' ? [] : [s?.dataset.subcategoryName || '']),
          ...(type === 'all' ? [] : [t?.dataset.typeName || ''])].filter(Boolean).join(' / ');
      }
      catalog.classList.remove('user-is-updating');
    };
    const applyAfterOldScript = () => {
      clearTimeout(pending);
      pending = setTimeout(apply, 220);
    };

    // Capture these three button groups so stale catalog listeners cannot override them.
    document.addEventListener('click', (event) => {
      const button = event.target.closest('[data-user-category], [data-user-subcategory], [data-user-type]');
      if (!button) return;
      event.stopPropagation();
      if (button.hasAttribute('data-user-category')) {
        category = button.dataset.categoryId || 'all';
        subcategory = 'all';
        setPressed(categoryButtons, 'categoryId', category);
        setPressed(subcategoryButtons, 'subcategoryId', 'all');
        refreshSubcategories();
      } else if (button.hasAttribute('data-user-subcategory')) {
        subcategory = button.dataset.subcategoryId || 'all';
        setPressed(subcategoryButtons, 'subcategoryId', subcategory);
      } else {
        type = button.dataset.typeId || 'all';
        setPressed(typeButtons, 'typeId', type);
      }
      apply();
    }, true);

    searchInput?.addEventListener('input', applyAfterOldScript);
    sortSelect?.addEventListener('change', applyAfterOldScript);
    searchForm?.addEventListener('submit', (event) => { event.preventDefault(); applyAfterOldScript(); });
    const q = new URLSearchParams(location.search).get('q');
    if (q && searchInput) searchInput.value = q;
    refreshSubcategories();
    applyAfterOldScript();
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true });
  else boot();
})();
