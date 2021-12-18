<template>
    <div class="customer-imports">
        <table class="table">
            <thead>
            <tr>
                <th>File</th>
                <th>Status</th>
                <th>Total</th>
                <th>Success</th>
            </tr>
            </thead>
            <tbody>
            <template v-if="customerImports.length > 0">
                <tr v-for="(row,index) in customerImports" :key="index">
                    <td>{{ row.file }}</td>
                    <td>{{ row.status }}</td>
                    <td>{{ row.total }}</td>
                    <td>{{ row.success_count }}</td>
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

const {getCustomerImports} = customerImport()

const customerImports = ref([]);

const loadCustomerImports = () => {
    getCustomerImports().then(({data}) => {
        customerImports.value = data
    })
}

onMounted(() => {
    loadCustomerImports()
})
</script>
