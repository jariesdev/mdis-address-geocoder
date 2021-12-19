<template>
    <div class="customer-imports">
        <div class="table-responsive">
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
                        <td>
                            <animated-number :value="row.total" :tween-duration="1500" />
                        </td>
                        <td>
                            <template v-if="row.status === 'coordinate-searching'">
                                <animated-number :value="row.success_count || 0" :tween-duration="1500" /> / <animated-number :value="row.search_elapse_count" :tween-duration="1500" />
                            </template>
                            <template v-else-if="row.success_count > 0">
                                <animated-number :value="row.success_count || 0" :tween-duration="1500" />
                                <button class="btn btn-outline-info btn-sm ms-1" @click="locateImportCustomers(row)">
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
                                <button class="btn btn-sm btn-info" :disabled="!['coordinate-located','completed'].includes(row.status)" @click="generateCsvTrigger(row)">Generate</button>
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
import AnimatedNumber from "./AnimatedNumber.vue";

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
