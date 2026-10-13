
document.addEventListener('DOMContentLoaded', () => {
    const source = document.getElementById('outward-data');
    if (!source) return;

    const data = JSON.parse(source.textContent);
    const type = document.getElementById('movement-type');
    const order = document.getElementById('order-select');
    const body = document.getElementById('product-rows');
    const addButton = document.getElementById('add-product');
    let counter = 0;

    const money = n => new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR'
    }).format((Number(n) || 0) / 100);

    const cell = (row, text) => {
        const td = document.createElement('td');
        td.textContent = text;
        row.appendChild(td);
        return td;
    };

    const categories = Array.isArray(data.categories) ? data.categories : [];
    const products = Array.isArray(data.products) ? data.products : [];
    const orders = Array.isArray(data.orders) ? data.orders : [];

    const mainCategories = categories.filter(c =>
        c.parent_id === null ||
        c.parent_id === undefined ||
        c.parent_id === ''
    );

    const getSubcategories = parentId => categories.filter(c =>
        String(c.parent_id) === String(parentId)
    );

    const getProducts = categoryId => products.filter(p =>
        String(p.category_id) === String(categoryId)
    );

    function fillSelect(select, items, placeholder, labelFn) {
        select.replaceChildren(new Option(placeholder, ''));
        items.forEach(item => {
            select.add(new Option(labelFn(item), String(item.id)));
        });
    }

    function summary() {
        let qty = 0;
        let value = 0;

        body.querySelectorAll('tr').forEach(row => {
            const input = row.querySelector('input[type="number"]');
            const quantity = Number(
                input ? input.value : row.dataset.quantity || 0
            );
            const price = Number(row.dataset.price || 0);

            qty += quantity;
            value += quantity * price;

            const line = row.querySelector('.line-value');
            if (line) line.textContent = money(quantity * price);
        });

        document.getElementById('quantity-summary').textContent = qty + ' units';
        document.getElementById('value-summary').textContent = money(value);
    }

    function manual(item = {}) {
        const row = document.createElement('tr');

        // Category dropdown
        const categorySelect = document.createElement('select');
        categorySelect.setAttribute('aria-label', 'Category');
        categorySelect.name = `items[${counter}][category_id]`;

        fillSelect(categorySelect, mainCategories, 'Select category', c => c.name);
        cell(row, '').appendChild(categorySelect);

        // Subcategory dropdown
        const subcategorySelect = document.createElement('select');
        subcategorySelect.setAttribute('aria-label', 'Subcategory');
        subcategorySelect.disabled = true;
        fillSelect(subcategorySelect, [], 'Select subcategory', c => c.name);
        cell(row, '').appendChild(subcategorySelect);

        // Product dropdown
        const productSelect = document.createElement('select');
        productSelect.setAttribute('aria-label', 'Product');
        productSelect.name = `items[${counter}][product_id]`;
        productSelect.required = true;
        productSelect.disabled = true;
        fillSelect(productSelect, [], 'Select product', p => p.name);
        cell(row, '').appendChild(productSelect);

        // Available stock
        const stockCell = cell(row, '—');

        // Quantity
        const quantityInput = document.createElement('input');
        quantityInput.name = `items[${counter}][quantity]`;
        quantityInput.type = 'number';
        quantityInput.setAttribute('aria-label', 'Quantity');
        quantityInput.min = '1';
        quantityInput.max = '1000000';
        quantityInput.required = true;
        quantityInput.value = item.quantity || 1;
        cell(row, '').appendChild(quantityInput);

        // Unit and line values
        const priceCell = cell(row, '—');
        cell(row, '₹0.00').className = 'line-value';

        // Remove row
        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'ow-btn ow-danger';
        removeButton.textContent = 'Remove';
        cell(row, '').appendChild(removeButton);

        counter++;

        function updateProduct() {
            const product = products.find(p =>
                String(p.id) === String(productSelect.value)
            );

            stockCell.textContent = product ? product.stock : '—';
            priceCell.textContent = product ? money(product.price) : '—';
            row.dataset.price = product ? Number(product.price) || 0 : 0;
            summary();
        }

        function resetProduct() {
            fillSelect(productSelect, [], 'Select product', p => p.name);
            productSelect.value = '';
            productSelect.disabled = true;
            stockCell.textContent = '—';
            priceCell.textContent = '—';
            row.dataset.price = 0;
            summary();
        }

        categorySelect.addEventListener('change', () => {
            const subs = categorySelect.value
                ? getSubcategories(categorySelect.value)
                : [];

            fillSelect(
                subcategorySelect,
                subs,
                subs.length ? 'Select subcategory' : 'No subcategories available',
                c => c.name
            );

            subcategorySelect.disabled = subs.length === 0;
            subcategorySelect.value = '';
            resetProduct();
        });

        subcategorySelect.addEventListener('change', () => {
            const matching = subcategorySelect.value
                ? getProducts(subcategorySelect.value)
                : [];

            fillSelect(
                productSelect,
                matching,
                matching.length ? 'Select product' : 'No products available',
                p => p.name + ' · ' + (p.sku || '')
            );

            productSelect.disabled = matching.length === 0;
            productSelect.value = '';
            updateProduct();
        });

        productSelect.addEventListener('change', updateProduct);
        quantityInput.addEventListener('input', summary);

        removeButton.addEventListener('click', () => {
            row.remove();
            summary();
        });

        body.appendChild(row);

        // Restore existing product when editing an outward entry
        if (item.product_id) {
            const savedProduct = products.find(p =>
                String(p.id) === String(item.product_id)
            );

            if (savedProduct) {
                const sub = categories.find(c =>
                    String(c.id) === String(savedProduct.category_id)
                );

                if (sub) {
                    const parent = categories.find(c =>
                        String(c.id) === String(sub.parent_id)
                    );

                    if (parent) {
                        categorySelect.value = String(parent.id);
                        categorySelect.dispatchEvent(new Event('change'));

                        subcategorySelect.value = String(sub.id);
                        subcategorySelect.dispatchEvent(new Event('change'));

                        productSelect.value = String(savedProduct.id);
                        updateProduct();
                    }
                }
            }
        }

        summary();
    }

    function orderRows() {
        body.replaceChildren();

        const selectedOrder = orders.find(o =>
            String(o.id) === String(order.value)
        );

        document.getElementById('order-address').textContent = selectedOrder
            ? Object.values(selectedOrder.address || {})
                .filter(v => typeof v === 'string')
                .join(', ')
            : 'Select an order to load its products and delivery address.';

        if (selectedOrder) {
            (selectedOrder.items || []).forEach(item => {
                const row = document.createElement('tr');
                row.dataset.price = item.price;
                row.dataset.quantity = item.quantity;

                cell(row, [
                    item.name,
                    item.sku,
                    item.variant
                ].filter(Boolean).join(' · '));

                cell(row, 'Reserved at checkout');
                cell(row, item.quantity);
                cell(row, money(item.price));
                cell(row, money(item.price * item.quantity)).className = 'line-value';
                cell(row, '');

                body.appendChild(row);
            });
        }

        summary();
    }

    function mode() {
        const isOrder = type.value === 'Order Dispatch';
        const isTransfer = type.value === 'Internal Transfer';

        document.getElementById('order-section').hidden = !isOrder;
        order.disabled = !isOrder;
        order.required = isOrder;

        document.getElementById('reason-field').hidden = isOrder;
        document.getElementById('reason').required = !isOrder;

        document.getElementById('destination-field').hidden = !isTransfer;
        document.getElementById('destination').required = isTransfer;
        document.getElementById('transfer-note').hidden = !isTransfer;

        addButton.hidden = isOrder;
        body.replaceChildren();

        if (isOrder) {
            orderRows();
        } else if (Array.isArray(data.items) && data.items.length) {
            data.items.forEach(manual);
            data.items = [];
        } else {
            manual();
        }

        summary();
    }

    addButton.addEventListener('click', () => manual());
    type.addEventListener('change', mode);
    order.addEventListener('change', orderRows);

    mode();
});
