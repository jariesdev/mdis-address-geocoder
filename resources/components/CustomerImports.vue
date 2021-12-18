<template>
    <div class="customer-imports">
        <table class="table">
            <thead>
            <tr>
                <th>File</th>
                <th>Status</th>
                <th>Total</th>
                <th>Located</th>
                <th>Download CSV</th>
            </tr>
            </thead>
            <tbody>
            <template v-if="customerImports.length > 0">
                <tr v-for="(row,index) in customerImports" :key="index">
                    <td>
                        <a :href="`/imports/${row.id}/customers`">
                            {{ row.file }}
                        </a>
                    </td>
                    <td>{{ row.status }}</td>
                    <td>{{ row.total }}</td>
                    <td>
                        <template v-if="row.status === 'coordinate-searching'">
                            {{ row.success_count === null ? '-' : row.success_count }} / {{ row.search_elapse_count }}
                        </template>
                        <template v-else-if="row.success_count > 0">
                            {{ row.success_count === null ? '-' : row.success_count }}
                            <button class="btn btn-outline-info btn-sm" @click="locateImportCustomers(row)">
                                <i class="fa-solid fa-arrows-rotate"></i>
                            </button>
                        </template>
                        <template v-else>
                            <button class="btn btn-outline-info btn-sm" @click="locateImportCustomers(row)">
                                Locate <i class="fa-solid fa-magnifying-glass-location ms-1"></i>
                            </button>
                        </template>
                    </td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-info" :disabled="row.status !== 'completed'" @click="generateCsvTrigger(row)">Generate</button>
                            <a v-if="!!row.csv_path" :href="`/api/imports/${row.id}/download-csv`" :class="{'disabled' : row.status !== 'completed'}" class="btn btn-sm btn-primary">Download</a>
                        </div>
                    </td>
                </tr>
            </template>
            <template v-else>
                <tr>
                    <td colspan="99">
                        <div class="alert alert-info text-center">
                            No import yet.
                        </div>
                    </td>
                </tr>
            </template>
            </tbody>
        </table>
    </div>
</template>

<script>
export default {
    name: "CustomerImports",
}
</script>

<script setup>
import {customerImport} from "@/composables/customers.js";
import {onMounted, ref} from "vue";

const {getCustomerImports, generateCsv, locateCustomers} = customerImport()

const customerImports = ref([]);

const loadCustomerImports = () => {
    getCustomerImports().then(({data}) => {
        customerImports.value = data
    })
}
const generateCsvTrigger = (row) => {
    row.status = 'generating-csv'
    generateCsv(row.id)
}
const locateImportCustomers = (row) => {
    row.status = 'coordinate-searching'
    locateCustomers(row.id)
}

onMounted(() => {
    setInterval(loadCustomerImports, 5000)
    loadCustomerImports()
})
</script>
