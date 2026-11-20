const form = document.querySelector('#transaction-form');

if (form) {
    const typeInput = form.querySelector('#type');
    const rows = form.querySelector('#item-rows');
    const template = document.querySelector('#item-row-template');
    const formatter = new Intl.NumberFormat('id-ID');

    const rupiah = (amount) => `Rp ${formatter.format(amount)}`;

    const updateTotals = () => {
        let total = 0;

        rows.querySelectorAll('.item-row').forEach((row) => {
            const select = row.querySelector('.product-select');
            const quantityInput = row.querySelector('.quantity-input');
            const option = select.selectedOptions[0];
            const priceKey = typeInput.value === 'penjualan' ? 'salePrice' : 'purchasePrice';
            const price = Number(option?.dataset[priceKey] ?? 0);
            const quantity = Number(quantityInput.value);
            const subtotal = Number.isSafeInteger(quantity) && quantity > 0 ? price * quantity : 0;

            row.querySelector('.unit-price').textContent = option?.value ? `Harga satuan: ${rupiah(price)}` : '';
            row.querySelector('.row-subtotal').textContent = rupiah(subtotal);
            total += subtotal;
        });

        document.querySelector('#transaction-total').textContent = rupiah(total);
    };

    const reindexRows = () => {
        rows.querySelectorAll('.item-row').forEach((row, index) => {
            row.querySelector('.product-select').name = `items[${index}][product_id]`;
            row.querySelector('.quantity-input').name = `items[${index}][quantity]`;
        });
    };

    form.addEventListener('input', updateTotals);
    form.addEventListener('change', updateTotals);
    typeInput.addEventListener('change', updateTotals);

    document.querySelector('#add-item').addEventListener('click', () => {
        rows.append(template.content.cloneNode(true));
        reindexRows();
        updateTotals();
    });

    rows.addEventListener('click', (event) => {
        if (!event.target.closest('.remove-item')) {
            return;
        }

        const row = event.target.closest('.item-row');
        if (rows.querySelectorAll('.item-row').length > 1) {
            row.remove();
        } else {
            row.querySelector('.product-select').value = '';
            row.querySelector('.quantity-input').value = 1;
        }

        reindexRows();
        updateTotals();
    });

    updateTotals();
}
