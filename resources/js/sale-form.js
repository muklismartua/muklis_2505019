export default (
    products,
    initialItems,
    initialValues = {},
    customerSearchUrl,
    quickCustomerUrl,
    initialCustomer = null,
    csrfToken = '',
) => {
    const availableProducts = products.map((product) => ({
        ...product,
        id: Number(product.id),
        harga_jual: Number(product.harga_jual),
        stok: Number(product.stok),
    }));

    return {
        products: availableProducts,
        customerSearchUrl,
        quickCustomerUrl,
        csrfToken,
        selectedCustomer: initialCustomer,
        customerId: initialCustomer ? String(initialCustomer.id) : '',
        customerQuery: '',
        customerResults: [],
        customerOpen: false,
        customerLoading: false,
        customerSearchError: '',
        customerSearchSequence: 0,
        customerModalOpen: false,
        quickCustomer: { nama: '', telepon: '', alamat: '' },
        quickCustomerErrors: {},
        quickCustomerMessage: '',
        duplicateCustomer: null,
        quickCustomerSubmitting: false,
        items: (Array.isArray(initialItems) ? initialItems : []).map((item, index) => {
            const product = availableProducts.find((candidate) => candidate.id === Number(item.product_id));
            const requestedQuantity = Number(item.qty ?? 1);
            const quantity = Number.isInteger(requestedQuantity) && requestedQuantity > 0
                ? requestedQuantity
                : 1;

            return {
                key: index,
                product_id: item.product_id ? Number(item.product_id) : '',
                nama: product?.nama ?? 'Produk tidak tersedia',
                sku: product?.sku ?? '',
                harga_jual: product?.harga_jual ?? 0,
                stok: product?.stok ?? 0,
                qty: product?.stok > 0 ? Math.min(quantity, product.stok) : 0,
                diskon: Number(item.diskon ?? 0),
            };
        }),
        nextKey: Array.isArray(initialItems) ? initialItems.length : 0,
        query: '',
        error: '',
        diskon: Number(initialValues.diskon ?? 0),
        pajakPersen: Number(initialValues.pajak_persen ?? 0),
        bayar: Number(initialValues.bayar ?? 0),
        metodePembayaran: initialValues.metode_pembayaran ?? 'tunai',

        get hasilPencarian() {
            const term = this.query.trim().toLocaleLowerCase('id-ID');

            if (!term) {
                return [];
            }

            return this.products
                .filter((product) => `${product.nama} ${product.sku}`.toLocaleLowerCase('id-ID').includes(term))
                .slice(0, 10);
        },

        async cariPelanggan() {
            const term = this.customerQuery.trim();
            const sequence = ++this.customerSearchSequence;
            this.customerSearchError = '';
            this.customerOpen = true;

            if (!term) {
                this.customerResults = [];
                this.customerLoading = false;
                return;
            }

            this.customerLoading = true;

            try {
                const response = await fetch(`${this.customerSearchUrl}?q=${encodeURIComponent(term)}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) {
                    throw new Error('Pencarian pelanggan gagal. Silakan coba lagi.');
                }

                const result = await response.json();
                if (sequence === this.customerSearchSequence) {
                    this.customerResults = result.data;
                }
            } catch (error) {
                if (sequence === this.customerSearchSequence) {
                    this.customerSearchError = error.message || 'Pencarian pelanggan gagal. Silakan coba lagi.';
                    this.customerResults = [];
                }
            } finally {
                if (sequence === this.customerSearchSequence) {
                    this.customerLoading = false;
                }
            }
        },

        pilihPelanggan(customer) {
            this.selectedCustomer = customer;
            this.customerId = customer ? String(customer.id) : '';
            this.customerQuery = '';
            this.customerResults = [];
            this.customerOpen = false;
            this.customerSearchError = '';
        },

        bukaModalPelanggan() {
            this.quickCustomer = { nama: '', telepon: '', alamat: '' };
            this.quickCustomerErrors = {};
            this.quickCustomerMessage = '';
            this.duplicateCustomer = null;
            this.customerModalOpen = true;
        },

        async buatPelangganCepat() {
            if (this.quickCustomerSubmitting) {
                return;
            }

            this.quickCustomerSubmitting = true;
            this.quickCustomerErrors = {};
            this.quickCustomerMessage = '';
            this.duplicateCustomer = null;

            try {
                const response = await fetch(this.quickCustomerUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(this.quickCustomer),
                });
                const result = await response.json();

                if (response.status === 409 && result.duplicate) {
                    this.duplicateCustomer = result.customer;
                    this.quickCustomerMessage = result.message;
                    return;
                }

                if (response.status === 422) {
                    this.quickCustomerErrors = result.errors || {};
                    return;
                }

                if (!response.ok) {
                    throw new Error(result.message || 'Pelanggan gagal disimpan. Silakan coba lagi.');
                }

                this.pilihPelanggan(result.customer);
                this.customerModalOpen = false;
                this.quickCustomer = { nama: '', telepon: '', alamat: '' };
            } catch (error) {
                this.quickCustomerMessage = error.message || 'Pelanggan gagal disimpan. Silakan coba lagi.';
            } finally {
                this.quickCustomerSubmitting = false;
            }
        },

        get subtotal() {
            return this.items.reduce((sum, item) => sum + this.subtotalItem(item), 0);
        },

        get pajak() {
            const taxableAmount = Math.max(this.subtotal - Math.max(Number(this.diskon || 0), 0), 0);

            return Math.round((taxableAmount * Math.max(Number(this.pajakPersen || 0), 0)) / 100);
        },

        get total() {
            return Math.max(this.subtotal - Math.max(Number(this.diskon || 0), 0) + this.pajak, 0);
        },

        get kembalian() {
            return Math.max(Number(this.bayar || 0) - this.total, 0);
        },

        subtotalItem(item) {
            const qty = Number(item.qty);
            const harga = Number(item.harga_jual);
            const diskonItem = Number(item.diskon || 0);

            if (!Number.isSafeInteger(qty) || qty < 1 || !Number.isSafeInteger(harga) || harga < 0) {
                return 0;
            }

            return Math.max(qty * harga - Math.max(diskonItem, 0), 0);
        },

        formatRupiah(value) {
            return `Rp ${new Intl.NumberFormat('id-ID').format(Number.isFinite(value) ? value : 0)}`;
        },

        tambahProduk(product) {
            if (product.stok < 1) {
                this.error = `Stok ${product.nama} sedang habis.`;
                return;
            }

            const existing = this.items.find((item) => item.product_id === product.id);
            if (existing) {
                if (existing.qty >= product.stok) {
                    this.error = `Jumlah ${product.nama} sudah mencapai stok tersedia (${product.stok}).`;
                    return;
                }

                existing.qty += 1;
            } else {
                this.items.push({
                    key: this.nextKey++,
                    product_id: product.id,
                    nama: product.nama,
                    sku: product.sku,
                    harga_jual: product.harga_jual,
                    stok: product.stok,
                    qty: 1,
                    diskon: 0,
                });
            }

            this.query = '';
            this.error = '';
        },

        ubahQty(item) {
            if (item.stok < 1) {
                item.qty = 0;
                this.error = `Stok ${item.nama} sedang habis. Hapus item ini dari keranjang.`;
                return;
            }

            const quantity = Number(item.qty);
            if (!Number.isInteger(quantity) || quantity < 1) {
                item.qty = 1;
                return;
            }

            if (quantity > item.stok) {
                item.qty = item.stok;
                this.error = `Jumlah ${item.nama} melebihi stok tersedia (${item.stok}).`;
                return;
            }

            this.error = '';
        },

        hapusItem(index) {
            this.items.splice(index, 1);
        },
    };
};
