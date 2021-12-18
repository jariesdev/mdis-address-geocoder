import http from "@/js/http";

export const customerImport = () => {

    // Methods
    const getCustomerImports = () => {
        return http.get('/api/imports').then(({data}) => data);
    }
    const createCustomerImports = (form) => {
        return http.post('/api/imports', form).then(({data}) => data);
    }
    const generateCsv = (importId) => {
        return http.post(`/api/imports/${importId}/generate`).then(({data}) => data);
    }
    const getCustomers = (importId, params) => {
        return http.get(`/api/imports/${importId}/customers`, {params: params}).then(({data}) => data);
    }

    return {
        getCustomerImports,
        createCustomerImports,
        generateCsv,
        getCustomers,
    }
}
