import { createApp } from 'vue'
import CustomerImports from "../components/CustomerImports.vue";
import CustomerImportCreate from "../components/CustomerImportCreate.vue";
import ImportCustomers from "../components/ImportCustomers.vue";
import CustomerGridImports from "../components/CustomerGridImports.vue";

const app = createApp({});

// Components
app.component('customer-imports', CustomerImports);
app.component('customer-import-create', CustomerImportCreate);
app.component('import-customers', ImportCustomers);
app.component('import-grid-customers', CustomerGridImports);

app.mount('#app');

require('./bootstrap');
