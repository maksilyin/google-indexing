<template>
    <div class="indexing__input--file">
        <label class="indexing__label">
            <span
                v-if="title"
                class="indexing__label--text"
            >
                {{title}}
            </span>
            <input @change="onFileSelected($event)" ref="upload_file" type="file" :accept="accept" :multiple="multiple" :disabled="disabled">
            <div class="indexing__input--file-block">
                <span ref="upload_file_path" class="indexing__input--file-text">{{sFileCount}}</span>
                <span class="indexing__input--file-btn">Выбрать</span>
            </div>
            <div
                v-if="error != ''"
                class="indexing__error"
            >
                <span class="indexing__error--text">{{error}}</span>
            </div>
        </label>
    </div>
</template>

<script>

export default {
    props: {
        title: {
            type: String,
            default() {
                return '';
            },
        },

        multiple: {
            type: Boolean,
            default() {
                return false;
            },
        },

        accept: {
            type: String,
            default() {
                return '';
            },
        },

        error: {
            type: String,
            default() {
                return '';
            },
        },

        disabled: {
            type: Boolean,
            default() {
                return false;
            },
        }
    },
    data (){
        return {
            arFiles: [],
        }
    },
    computed: {
        sFileCount() {
            return this.arFiles.length ? 'Выбрано файлов ' + this.arFiles.length : 'Файл не выбран';
        }
    },
    methods: {
        onFileSelected (e) {
            this.arFiles = e.target.files;
            this.$emit('input', this.arFiles)
            this.$emit('change', this.arFiles)
        },
        selectFile() {
            this.$refs.upload_file.click();
        }
    },
}
</script>
