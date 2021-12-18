import { createApp } from 'vue'
import CustomerImports from "@/components/CustomerImports.vue";
import CustomerImportCreate from "@/components/CustomerImportCreate.vue";

const app = createApp({});

// Components
app.component('customer-imports', CustomerImports);
app.component('customer-import-create', CustomerImportCreate);

app.mount('#app');

require('./bootstrap');
