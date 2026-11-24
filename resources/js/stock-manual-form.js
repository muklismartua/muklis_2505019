export default (products, initialItems = [], selectedProductId = null, initialType = 'tambah') => {
    const availableProducts = products.map((product) => ({
        ...product,
        id: Number(product.id),
        stok: Number(product.stok),
    }));

    const createItem = (product, quantity, key) => ({
        key,
        product_id: product.id,
        nama: product.nama,
        sku: product.sku,
        stok: product.stok,
        qty: Number.isInteger(Number(quantity)) && Number(quantity) > 0 ? Number(quantity) : 1,
    });

    let nextKey = 0;
    const items = (Array.isArray(initialItems) ? initialItems : [])
        .map((item) => {
            const product = availableProducts.find((candidate) => candidate.id === Number(item.product_id));

            return product ? createItem(product, item.qty, nextKey++) : null;
        })
        .filter(Boolean);

    if (items.length === 0 && selectedProductId) {
        const selectedProduct = availableProducts.find((product) => product.id === Number(selectedProductId));
        if (selectedProduct) {
            items.push(createItem(selectedProduct, 1, nextKey++));
        }
    }

    return {
        products: availableProducts,
        items,
        nextKey,
        query: '',
        tipe: ['tambah', 'kurang'].includes(initialType) ? initialType : 'tambah',

        get hasilPencarian() {
            const term = this.query.trim().toLocaleLowerCase('id-ID');
            if (!term) {
                return [];
            }

            const selectedIds = this.items.map((item) => item.product_id);

            return this.products
                .filter((product) => !selectedIds.includes(product.id))
                .filter((product) => `${product.nama} ${product.sku}`.toLocaleLowerCase('id-ID').includes(term))
                .slice(0, 10);
        },

        get adaQtyTidakValid() {
            return this.items.some((item) => {
                const qty = Number(item.qty);

                return !Number.isInteger(qty)
                    || qty < 1
                    || (this.tipe === 'kurang' && qty > item.stok);
            });
        },

        stokSetelah(item) {
            const qty = Number(item.qty) || 0;

            return this.tipe === 'tambah' ? item.stok + qty : item.stok - qty;
        },

        formatJumlah(value) {
            return new Intl.NumberFormat('id-ID').format(Number(value) || 0);
        },

        tambahProduk(product) {
            if (this.items.some((item) => item.product_id === product.id)) {
                return;
            }

            this.items.push(createItem(product, 1, this.nextKey++));
            this.query = '';
        },

        hapusItem(index) {
            this.items.splice(index, 1);
        },
    };
};
