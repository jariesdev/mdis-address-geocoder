<template>
    <div class="customer-grid-imports">
        <div class="table-container mb-3">
            <div ref="tableRef" />
        </div>
        <nav aria-label="Page navigation" class="d-flex align-items-center">
            <button :disabled="isLoading" class="btn btn-light" @click="loadCustomers()">
                <i class="fas fa-refresh" :class="{'fa-spin': isLoading}" /> Reload
            </button>
            <div class="d-flex align-items-center">
                <select v-model="perPage" class="form-control ms-2">
                    <option value="20">20 per page</option>
                    <option value="50">50 per page</option>
                    <option value="100">100 per page</option>
                    <option value="500">500 per page</option>
                    <option value="1000">1000 per page</option>
                    <option value="10000">10000 per page</option>
                </select>
            </div>
            <label class="ms-2 checkbox">
                <input v-model="onlyEmptyCoordinates" type="checkbox" @change="loadCustomers()"> Only empty coordinates
            </label>
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
    name: "CustomerGridImports",
}
</script>

<script setup>
import {customerImport} from "@/composables/customers.js";
import {onMounted, ref, watch} from "vue";
import debounce from "lodash/debounce.js";
import Handsontable from "handsontable";
import 'handsontable/dist/handsontable.full.css';
import {forEach} from "lodash";

const props = defineProps({
    importId: {
        type: [String, Number],
        required: true
    },
    nominatimUrl: {
        type: String,
        required: true
    }
})
const {getCustomers, updateCustomers} = customerImport()

const hot = ref()
const tableRef = ref()
const customers = ref([])
const isLoading = ref(false)
const total = ref(0)
const page = ref(1)
const perPage = ref(20)
const hasPrevPage = ref(false)
const hasNextPage = ref(false)
const onlyEmptyCoordinates = ref(false)

// Methods
const initHandsOnTable = () => {
    const columns = [
        {
            data: 'refid',
            readOnly: true,
            type: 'numeric',
        },
        {
            data: 'street'
        },
        {
            data: 'barangay_name'
        },
        {
            data: 'municipality_name'
        },
        {
            data: 'province_name'
        },
        {
            data: 'region'
        },
        {
            data: 'island'
        },
        {
            data: 'source_table'
        },
        {
            data: 'latitude',
            readOnly: true,
            type: 'numeric',
        },
        {
            data: 'longitude',
            readOnly: true,
            type: 'numeric',
        },
    ]

    const afterChange = function (changes, source) {
        if (source === 'loadData') {
            return; //don't save this change
        }

        const foundCustomers = [];
        forEach(changes, c => {
            const customer = customers.value[c[0]]
            customer[c[1]] = c[3]
            foundCustomers.push(customer)
        })

        updateCustomers(foundCustomers)
    }

    hot.value = new Handsontable(tableRef.value, {
        data: customers.value,
        rowHeaders: true,
        colHeaders: true,
        height: 'auto',
        filters: false,
        dropdownMenu: true,
        stretchH: 'all', // 'none' is default
        contextMenu: false,
        columns,
        licenseKey: 'non-commercial-and-evaluation', // for non-commercial use only
        afterChange
    });
}
const loadCustomers = debounce(() => {
    isLoading.value = true
    getCustomers(props.importId, {
        page: page.value > 1 ? page.value : 1,
        perPage: perPage.value,
        onlyEmptyCoordinates: onlyEmptyCoordinates.value,
    })
        .then(({data, meta, links}) => {
            customers.value = data
            hasPrevPage.value = links.prev !== null
            hasNextPage.value = links.next !== null
            total.value = meta.total
            hot.value.loadData(customers.value)
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
    initHandsOnTable()
    loadCustomers()
})

watch([page, perPage], () => {
    loadCustomers()
})
</script>
