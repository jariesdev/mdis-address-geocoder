<template>
    <div class="customer-imports-create">
        <div v-if="formSuccessMessage" class="alert alert-success">
            {{ formSuccessMessage }}
        </div>
        <div v-if="formErrorMessage" class="alert alert-danger">
            {{ formErrorMessage }}
        </div>
        <form @submit.prevent="uploader.start()">
            <div class="mb-3">
                <div v-if="uploadFile !== null">
                    {{ uploadFile.name }}
                </div>
                <div class="progress mb-3">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" :style="{width: `${uploadProgress}%`}"></div>
                </div>
                <button ref="uploadButtonRef" :disabled="isUploading" type="button" class="btn btn-info">
                    Select File
                    <span v-show="uploadProgress > 0">({{ uploadProgress }}%)</span>
                </button>
            </div>
            <div class="mb-3">
                <label for="table_name" class="form-label">Table Name</label>
                <input v-model="form.table_name" class="form-control" name="table_name" id="table_name" :disabled="isUploading">
                <div class="form-text">
                    Enter the name of tables to import. Leave blank to get first table name.
                </div>
            </div>
            <div class="card-actions">
                <button type="submit" :disabled="isUploading" class="btn btn-primary me-2">Import</button>
            </div>
        </form>
    </div>
</template>

<script>
export default {
    name: "CustomerImportCreate"
}
</script>

<script setup>
import {onMounted, reactive, ref} from "vue";
import {customerImport} from "@/composables/customers.js";
import plupload from 'plupload'

const emit = defineEmits(['success']);

const {createCustomerImports} = customerImport()

const uploader = ref()
const fileRef = ref()
const uploadButtonRef = ref()
const uploadProgress = ref(0)
const uploadFile = ref(null)
const isUploading = ref(false)
const formSuccessMessage = ref('')
const formErrorMessage = ref('')
const form = reactive({
    file: null,
    table_name: ''
})

// Methods
const clearForm = () => {
    form.file = null
    form.table_name = ''
};
const submit = () => {
    const formData = new FormData();
    formData.append("file", form.file);
    formData.append("table_name", form.table_name);
    isUploading.value = true
    createCustomerImports(formData)
        .then(() => {
            clearForm()
            if(uploadFile.value){
                uploader.value.removeFile(uploadFile.value)
                uploader.value.refresh()
            }
            setTimeout(() => {
                window.location.replace('/')
            }, 3000)
            formSuccessMessage.value = 'Import successfully uploaded.'
        })
        .catch(() => {
            formErrorMessage.value = 'Error occurred while processing your requests.'
        })
        .finally(() => {
            isUploading.value = false
        })
}
const uploaderInit = () => {
    uploader.value = new plupload.Uploader({
        browse_button: uploadButtonRef.value, // this can be an id of a DOM element or the DOM element itself
        url: '/api/imports/chunk-upload',
        chunk_size: '1.5mb',
        max_retries: 3,
        multi_selection: false,
        unique_names: false,
        filters: {
            prevent_duplicates: false,
            mime_types: [
                {
                    title: 'MDB Database File',
                    extensions: 'mdb'
                }
            ]
        }
    });
    uploader.value.init();
    uploader.value.bind('BeforeUpload', function(up, file) {
        isUploading.value = true
    });
    uploader.value.bind('FilesAdded', function(up, file) {
        uploadFile.value = file[0]
        // setTimeout(function () { up.start(); }, 100);
    });
    uploader.value.bind('UploadProgress', function(up, file) {
        uploadProgress.value = file.percent
    });
    // uploader.value.bind('ChunkUploaded', function(up, file) {
    //
    // });
    uploader.value.bind('FileUploaded', function(up, file, info) {
        const tableNames = JSON.parse(info.response).tables || []
        form.file = file.name
        if(!form.table_name && tableNames.length > 0) {
            form.table_name = tableNames[0];
        }

        if(form.file && form.table_name) {
            submit()
        }
    });
}


onMounted(() => {
    uploaderInit()
})
</script>
