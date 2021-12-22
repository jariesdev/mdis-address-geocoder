<template>
    <div class="import-customers">
        <div class="table-responsive">
            <table class="table">
                <thead>
                <tr>
                    <th>RefId</th>
                    <th>Street</th>
                    <th>Barangay</th>
                    <th>Municipality</th>
                    <th>Province</th>
                    <th>Region</th>
                    <th>Source</th>
                    <th>Coordinate</th>
                </tr>
                </thead>
                <tbody>
                <template v-if="customers.length > 0">
                    <tr v-for="row in customers" :key="row.id">
                        <td>{{ row.refid }}</td>
                        <td>{{ row.street }}</td>
                        <td>{{ row.barangay_name }}</td>
                        <td>{{ row.municipality_name }}</td>
                        <td>{{ row.region }}</td>
                        <td>{{ row.island }}</td>
                        <td>{{ row.source_table }}</td>
                        <td>
                            <template v-if="row.latitude && row.longitude">
                                <a :href="`http://lookup.holos.ph/nominatim/reverse.php?lat=${row.latitude}&lon=${row.longitude}&zoom=16&format=html`"
                                   target="_blank">
                                    {{ row.latitude }}, {{ row.longitude }}
                                </a>
                            </template>
                            <template v-else>
                                <a :href="`http://lookup.holos.ph/nominatim/search.php?street=${row.street}&city=${row.municipality_name}&country=ph`" class="text-warning"
                                   target="_blank">
                                    <i class="fas fa-exclamation-circle"></i>
                                </a>
                            </template>
                        </td>
                    </tr>
                </template>
                <template v-else>
                    <tr>
                        <td colspan="99">
                            <div class="alert alert-info text-center">
                                No customer imported yet.
                            </div>
                        </td>
                    </tr>
                </template>
                </tbody>
            </table>
        </div>
        <nav aria-label="Page navigation" class="d-flex align-items-center">
            <div class="d-flex align-items-center">
                <select v-model="perPage" class="form-control ms-2">
                    <option value="20">20 per page</option>
                    <option value="50">50 per page</option>
                    <option value="100">100 per page</option>
                    <option value="1000">1000 per page</option>
                </select>
            </div>
            <div class="ms-auto me-3 d-flex align-items-center">
                <span>Page:</span> <input type="number" min="1" class="form-control ms-2" :style="{width: '80px'}"
                                          v-model="page"/>
            </div>
            <ul class="pagination mb-0">
                <li class="page-item">
                    <button :disabled="!hasPrevPage" class="page-link" :class="{'text-muted': !hasPrevPage}"
                            @click="prevPage()">
                        Previous
                    </button>
                </li>
                <li class="page-item">
                    <button :disabled="!hasNextPage" class="page-link" :class="{'text-muted': !hasNextPage}"
                            @click="nextPage()">
                        Next
                    </button>
                </li>
            </ul>
        </nav>
    </div>
</template>

<script>
export default {
    name: "ImportCustomers",
}
</script>

<script setup>
import {customerImport} from "../composables/customers.js";
import {onMounted, ref, watch} from "vue";
import debounce from "lodash/debounce";

const props = defineProps({
    importId: {
        type: [String, Number],
        required: true
    }
})
const {getCustomers} = customerImport()

const customers = ref([])
const isLoading = ref(false)
const total = ref(0)
const page = ref(1)
const perPage = ref(20)
const hasPrevPage = ref(false)
const hasNextPage = ref(false)

// Methods
const loadCustomers = debounce(() => {
    isLoading.value = true
    getCustomers(props.importId, {
        page: page.value > 1 ? page.value : 1,
        perPage: perPage.value
    })
        .then(({data, meta, links}) => {
            customers.value = data
            hasPrevPage.value = links.prev !== null
            hasNextPage.value = links.next !== null
            total.value = meta.total
        })
        .finally(() => {
            isLoading.value = false
        })
}, 250, {maxWait: 1000})
const prevPage = () => {
    if (hasPrevPage.value && page.value > 1) {
        page.value = page.value - 1
    }
}
const nextPage = () => {
    if (hasNextPage.value) {
        page.value = page.value + 1
    }
}

onMounted(() => {
    loadCustomers()
})

watch([page, perPage], () => {
    loadCustomers()
})
</script>
