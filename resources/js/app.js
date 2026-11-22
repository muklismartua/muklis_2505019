import './bootstrap';

import Alpine from 'alpinejs';
import './transaction-form';
import purchaseForm from './purchase-form';

window.Alpine = Alpine;

Alpine.data('purchaseForm', purchaseForm);
Alpine.start();
