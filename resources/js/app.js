import './bootstrap';

import Alpine from 'alpinejs';
import './transaction-form';
import purchaseForm from './purchase-form';
import saleForm from './sale-form';

window.Alpine = Alpine;

Alpine.data('purchaseForm', purchaseForm);
Alpine.data('saleForm', saleForm);
Alpine.start();

if (document.querySelector('[data-sales-chart]')) {
    import('./dashboard-chart').then(({ default: initializeDashboardChart }) => initializeDashboardChart());
}
