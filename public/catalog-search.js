
(function () {
    'use strict';

    const form = document.getElementById('catalogSearchForm');
    const input = document.getElementById('catalogSearchInput');
    const panel = document.getElementById('catalogSearchSuggestions');
    const clearButton = document.getElementById('clearCatalogSearch');
    const productsElement = document.getElementById('catalogSearchProducts');

    if (!form || !input || !panel || !clearButton || !productsElement) {
        return;
    }

    const HISTORY_KEY = 'novacart_recent_searches';
    const MAX_HISTORY = 6;
    const MAX_SUGGESTIONS = 8;

    let activeIndex = -1;
    let visibleItems = [];

    let products = [];

    try {
        products = JSON.parse(productsElement.textContent || '[]');
    } catch (error) {
        console.error('Unable to load product search suggestions.', error);
    }

    products = [...new Set(
        products.filter(item => typeof item === 'string' && item.trim())
    )];

    function getHistory() {
        try {
            const value = JSON.parse(localStorage.getItem(HISTORY_KEY) || '[]');
            return Array.isArray(value)
                ? value.filter(item => typeof item === 'string').slice(0, MAX_HISTORY)
                : [];
        } catch (error) {
            return [];
        }
    }

    function saveSearch(query) {
        query = query.trim();

        if (!query) return;

        try {
            const history = getHistory().filter(
                item => item.toLowerCase() !== query.toLowerCase()
            );

            history.unshift(query);

            localStorage.setItem(
                HISTORY_KEY,
                JSON.stringify(history.slice(0, MAX_HISTORY))
            );
        } catch (error) {
            // Search remains functional if browser storage is unavailable.
        }
    }

    function makeHeading(text) {
        const heading = document.createElement('div');
        heading.className = 'catalog-search-heading';
        heading.textContent = text;
        return heading;
    }

    function makeOption(text, type, icon) {
        const button = document.createElement('button');

        button.type = 'button';
        button.className = 'catalog-search-option';
        button.setAttribute('role', 'option');
        button.dataset.query = text;

        const symbol = document.createElement('span');
        symbol.className = 'catalog-search-option-icon';
        symbol.setAttribute('aria-hidden', 'true');
        symbol.textContent = icon;

        const label = document.createElement('span');
        label.className = 'catalog-search-option-label';
        label.textContent = text;

        button.append(symbol, label);

        button.addEventListener('mouseenter', function () {
            setActive(visibleItems.indexOf(button));
        });

        button.addEventListener('click', function () {
            input.value = text;
            saveSearch(text);
            form.requestSubmit();
        });

        return button;
    }

    function setActive(index) {
        activeIndex = index;

        visibleItems.forEach(function (item, i) {
            const active = i === activeIndex;

            item.classList.toggle('is-active', active);
            item.setAttribute('aria-selected', String(active));
        });

        const activeItem = visibleItems[activeIndex];

        if (activeItem) {
            input.setAttribute('aria-activedescendant', activeItem.id);
            activeItem.scrollIntoView({ block: 'nearest' });
        } else {
            input.removeAttribute('aria-activedescendant');
        }
    }

    function renderOptionList(title, items, type, icon) {
        if (!items.length) return;

        panel.appendChild(makeHeading(title));

        items.forEach(function (item) {
            const button = makeOption(item, type, icon);

            button.id = 'catalog-search-option-' + visibleItems.length;

            visibleItems.push(button);
            panel.appendChild(button);
        });
    }

    function render() {
        const query = input.value.trim();

        panel.replaceChildren();
        visibleItems = [];
        activeIndex = -1;

        input.removeAttribute('aria-activedescendant');

        if (!query) {
            const history = getHistory();

            if (!history.length) {
                closePanel();
                return;
            }

            renderOptionList(
                'Recent searches',
                history,
                'recent',
                '◷'
            );

            const clearHistory = document.createElement('button');

            clearHistory.type = 'button';
            clearHistory.className = 'catalog-search-option';
            clearHistory.textContent = 'Clear search history';

            clearHistory.addEventListener('click', function () {
                try {
                    localStorage.removeItem(HISTORY_KEY);
                } catch (error) {
                    // Ignore unavailable browser storage.
                }

                render();
                input.focus();
            });

            panel.appendChild(clearHistory);
        } else {
            const normalized = query.toLocaleLowerCase();

            const matches = products.filter(function (product) {
                return product.toLocaleLowerCase().includes(normalized);
            }).slice(0, MAX_SUGGESTIONS);

            if (!matches.length) {
                const empty = document.createElement('div');
                empty.className = 'catalog-search-empty';
                empty.textContent = 'No matching product suggestions. Press Enter to search all results.';
                panel.appendChild(empty);
            } else {
                renderOptionList(
                    'Suggested products',
                    matches,
                    'product',
                    '⌕'
                );
            }

            const recentMatches = getHistory().filter(function (item) {
                return item.toLocaleLowerCase().includes(normalized);
            }).slice(0, 3);

            if (recentMatches.length) {
                renderOptionList(
                    'Recent searches',
                    recentMatches,
                    'recent',
                    '◷'
                );
            }
        }

        openPanel();
    }

    function openPanel() {
        panel.hidden = false;
    }

    function closePanel() {
        panel.hidden = true;
        activeIndex = -1;
        visibleItems = [];
        input.removeAttribute('aria-activedescendant');
    }

    function updateClearButton() {
        clearButton.hidden = input.value.length === 0;
    }

    input.addEventListener('focus', render);

    input.addEventListener('input', function () {
        updateClearButton();
        render();
    });

    clearButton.addEventListener('click', function () {
        input.value = '';
        updateClearButton();
        render();
        input.focus();
    });

    input.addEventListener('keydown', function (event) {
        if (panel.hidden) {
            if (event.key === 'ArrowDown') {
                render();
                if (visibleItems.length) event.preventDefault();
            }
            return;
        }

        if (event.key === 'ArrowDown') {
            if (visibleItems.length) {
                event.preventDefault();
                setActive((activeIndex + 1) % visibleItems.length);
            }
        } else if (event.key === 'ArrowUp') {
            if (visibleItems.length) {
                event.preventDefault();
                setActive(
                    activeIndex <= 0
                        ? visibleItems.length - 1
                        : activeIndex - 1
                );
            }
        } else if (event.key === 'Escape') {
            closePanel();
        } else if (
            event.key === 'Enter' &&
            activeIndex >= 0 &&
            visibleItems[activeIndex]
        ) {
            event.preventDefault();
            visibleItems[activeIndex].click();
        }
    });

    form.addEventListener('submit', function () {
        saveSearch(input.value);
    });

    document.addEventListener('click', function (event) {
        if (!form.contains(event.target)) {
            closePanel();
        }
    });

    updateClearButton();
})();
