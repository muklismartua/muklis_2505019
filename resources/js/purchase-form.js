export default (products, initialItems, initialDiscount, initialUpdatePrice) => {
    const items = Array.isArray(initialItems) && initialItems.length
        ? initialItems
        : [{ product_id: '', qty: 1, harga_beli: 0 }];

    return {
        products,
        items: items.map((item, index) => {
            const selected = products.find((product) => product.id === Number(item.product_id));
            const quantity = Number(item.qty ?? 1);
            const price = Number(item.harga_beli ?? selected?.harga_beli ?? 0);

            return {
                key: index,
                product_id: item.product_id ? Number(item.product_id) : '',
                qty: Number.isSafeInteger(quantity) && quantity > 0 ? quantity : 1,
                harga_beli: Number.isSafeInteger(price) && price >= 0 ? price : selected?.harga_beli ?? 0,
            };
        }),
        discount: Number.isSafeInteger(Number(initialDiscount)) && Number(initialDiscount) >= 0
            ? Number(initialDiscount)
            : 0,
        updatePurchasePrice: initialUpdatePrice === true || initialUpdatePrice === 1 || initialUpdatePrice === '1',
        nextKey: items.length,

        get subtotal() {
            return this.items.reduce((sum, item) => sum + this.lineSubtotal(item), 0);
        },

        get total() {
            return Math.max(this.subtotal - Math.max(Number(this.discount || 0), 0), 0);
        },

        lineSubtotal(item) {
            const quantity = Number(item.qty);
            const price = Number(item.harga_beli);

            return Number.isSafeInteger(quantity) && quantity > 0 && Number.isSafeInteger(price) && price >= 0
                ? quantity * price
                : 0;
        },

        formatRupiah(value) {
            return `Rp ${new Intl.NumberFormat('id-ID').format(value)}`;
        },

        setProductPrice(item) {
            const product = this.products.find((candidate) => candidate.id === Number(item.product_id));
            item.harga_beli = product ? product.harga_beli : 0;
        },

        addItem() {
            if (this.items.length < 50) {
                this.items.push({ key: this.nextKey++, product_id: '', qty: 1, harga_beli: 0 });
            }
        },

        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
            }
        },
    };
};
