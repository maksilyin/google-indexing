<template>
    <div class="main">
        <div class="indexing indexing__container">
            <h2>Google Indexing</h2>
            <form>
                <input-file
                    :title="'Список ключей'"
                    :multiple="true"
                    :error="errors.keys"
                    :disabled="bLoading"
                    v-model="oFormData.keysFile"
                    @change="onChangeKeys()"
                    :accept="'.json'"
                />
                <div class="indexing__flex">
                    <label class="indexing__label--text">Список страниц</label>
                    <div class="indexing__tabs">
                        <span
                            v-for="(tab, i) in tabs"
                            :key="i"
                            @click="switchTab(i)"
                            :class="{active: selectedTab === i}"
                        >
                            {{tab}}
                        </span>
                    </div>
                </div>
                <input-file
                    v-if="selectedTab === 0"
                    :error="errors.urls"
                    :disabled="bLoading"
                    v-model="oFormData.urlsFile"
                    @change="onChangeUrls()"
                    :accept="'text/*'"
                />
                <div
                    v-if="selectedTab === 1"
                    class="indexing__textarea"
                >
                    <textarea
                        :disabled="bLoading"
                        v-model="oFormData.urlsText"
                        @input="onChangeUrlsText($event)"
                    />
                </div>
                <div class="indexing__submit">
                    <button
                    @click.prevent="send"
                    :disabled="bLoading"
                    >
                        Отправить
                    </button>
                </div>
            </form>
            <loader
                v-if="bLoading"
                object="#4c328b"
                color1="#ffffff"
                color2="#17fd3d"
                size="6"
                speed="2"
                bg="#000000"
                objectbg="#999793"
                opacity="20"
                disableScrolling="false"
                name="spinning"
            >
            </loader>
        </div>
        <div
            class="container"
        >
            <slide-up-down :active="bLogActive" :duration="300">
                <logs
                    :arLogs="arLogMessages"
                    @clear="arLogMessages = []"
                />
            </slide-up-down>
        </div>
    </div>
</template>

<script>
import inputFile from './components/inputFile.vue'
import logs from './components/logs.vue'
import axios from 'axios'
export default {

    data () {
        return {
            tabs: [
                'Файл',
                'Текст'
            ],
            oFormData: {
                keysFile: null,
                urlsText: null,
                urlsFile: null
            },
            errors: {
                keys: '',
                urls: '',
            },
            bLogActive: true,
            bLoading: false,

            arKeys: [],
            arUrls: [],
            arLogMessages: [],
            arIndexKeySkip: [],
            arNotIndexed: [],

            iMaxUrlRequest: 200,
            iIndexed: 0,
            selectedTab: 0,
        }
    },
    components: {
        logs,
        inputFile,
    },
    methods: {
        prepareFormData() {
            const formData = {}

            if (this.oFormData.urls && this.oFormData.urls !== '') {
                formData.url_list = this.oFormData.urls.split(/\r\n|\r|\n/g);
            }

            formData.keysFile = this.oFormData.keys;
            formData.urlsFile = this.oFormData.urlsFile;

            return formData;
        },

        async send() {

            if (!this.validate() || this.bLoading) {
                return;
            }

            this.bLoading = true;

            this.addLog('message', 'Индексация...');

            await this.sendRequest();

            this.addLog('success', 'Индексация завершена');
            this.addLog('message', 'Проиндексировано страниц: ' + this.iIndexed);
            this.addLog('message', 'Не проиндексировано страниц: ' + this.arNotIndexed.length, this.arNotIndexed);

            this.bLoading = false;
        },

        async setKeysFromFile() {

            for (let i = 0; i < this.oFormData.keysFile.length; i++) {
                const file = this.oFormData.keysFile[i];

                try {
                    const fileData = await this.getFileData(file);
                    const json = JSON.parse(fileData);

                    if (Array.isArray(json)) {

                        json.forEach((item, i) => {

                            if (this.validateJsonKeys(item)) {
                                this.arKeys.push(item);
                            }
                            else {
                                this.addLog('error', 'Файл: ' + file.name + ' неверный формат ключа ' + (i + 1));
                            }
                        });
                    }
                    else if (this.validateJsonKeys(json)){
                        this.arKeys.push(json);
                    }
                    else {
                        this.addLog('error', 'Файл: ' + file.name + ' неверный формат ключа');
                    }
                }
                catch(e) {
                    console.log(e)
                }
            }
        },

        async sendRequest() {
            let iNumUrlRequest = Math.min(this.arUrls.length, this.iMaxUrlRequest);
            let iUrlStart = 0;
            let iUrlEnd = iNumUrlRequest;
            this.iIndexed = 0;
            this.arNotIndexed = [];

            for (let i = 0; i < this.arKeys.length; i++) {

                if (this.arUrls.length <= iUrlStart) {
                    return;
                }

                if (this.arIndexKeySkip.includes(i)) {
                    continue;
                }

                this.addLog('message', 'Аккаунт: ' + this.arKeys[i].client_email + '...');

                const urls = this.arUrls.slice(iUrlStart, iUrlEnd);

                const key = this.arKeys[i];
                const requestData = {
                    key: key,
                    urls: urls,
                }

                let { data } = await axios ({
                    method: 'post',
                    headers: { 'Content-Type': 'application/json' },
                    url: '/ajax/start_indexing.php',
                    data: requestData,
                });

                this.processingResponse(data)

                iUrlStart += iNumUrlRequest;
                iUrlEnd += iNumUrlRequest;

                if (data.error !== undefined) {
                    iUrlStart -= data.error.urls.length;
                    iUrlEnd -= data.error.urls.length;
                    this.arIndexKeySkip.push(i);
                }
            }

            if (this.arUrls.length > iUrlStart) {
                this.arNotIndexed = this.arNotIndexed.concat(this.arUrls.slice(iUrlStart));
            }
        },

        async setUrlsFromFile() {

            for (let i = 0; i < this.oFormData.urlsFile.length; i++) {
                const file = this.oFormData.urlsFile[i];
                try {
                    const fileData = await this.getFileData(file);
                    const arUrls = fileData.split(/\r\n|\r|\n/g);

                    arUrls.forEach(url => {
                        if (this.checkUrl(url)) {
                            this.arUrls.push(url)
                        }
                    })
                }
                catch(e) {
                      console.log(e)
                }
            }
        },

        getFileData(file) {
            return new Promise(function (resolve, reject) {
                let reader = new FileReader();
                reader.readAsText(file);
                reader.onload = function() {
                    resolve(reader.result)
                };
            })
        },

        validate() {
            let isError = true;

            if (!this.oFormData.keysFile) {
                this.errors.keys = 'Необходимо загрузить файлы с ключами';
                isError = false;
            }

            if (this.selectedTab === 0 && !this.oFormData.urlsFile) {
                this.errors.urls = 'Необходимо загрузить файл со списком страниц';
                isError = false;
            }

            return isError;
        },

        validateJsonKeys(json) {
            const keys = [
                'type',
                'project_id',
                'private_key_id',
                'private_key',
                'client_email',
                'client_id',
                'auth_uri',
                'token_uri',
                'auth_provider_x509_cert_url',
                'client_x509_cert_url',
            ];

            for (let i = 0; i < keys.length; i++) {

                if (!(keys[i] in json)) {
                    return false
                }
            }

            return true;
        },

        checkUrl(url) {
            let reg = /^(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;
            return reg.test(url);
        },

        addLog(type, message, dropdown = []) {
            this.arLogMessages.push(
                {type, message, dropdown}
            )
        },

        processingResponse(data) {
            if (data.content !== undefined) {
                data.content.forEach(item => {
                    let type = 'error'

                    if (item.code === 200) {
                        type = 'success';
                        this.iIndexed++;
                    }
                    else {
                        this.arNotIndexed.push(item.url);
                    }
                    this.addLog(type, item.code + ' ' + item.status + ': ' + item.url);
                })
            }

            if (data.error !== undefined) {

                if (data.error.code === 429) {
                    this.addLog('error', 'Аккаунт: ' + data.error.client_email + ' исчерпал свою квоту Indexing API');
                }
                else {
                    this.addLog('error', 'Аккаунт: ' + data.error.client_email + ' ошибка ' + data.error.code, [data.error.body]);
                }
            }
        },

        switchTab(index) {
            if (index !== this.selectedTab) {
                this.selectedTab = index;
                this.arUrls = [];
                this.oFormData.urlsText = null;
                this.oFormData.urlsFile = null;
            }
        },

        async onChangeKeys() {
            this.errors.keys = '';
            this.arKeys = [];
            this.arIndexKeySkip = [];
            await this.setKeysFromFile();
            this.addLog('message', 'Загружено ключей: ' + this.arKeys.length);
        },

        async onChangeUrls() {
            this.errors.urls = '';
            this.arUrls = [];
            await this.setUrlsFromFile();
            this.addLog('message', 'Загружено страниц: ' + this.arUrls.length);
        },

        onChangeUrlsText(event) {
            this.arUrls = [];
            const arUrls = event.target.value.split(/\r\n|\r|\n/g);

            arUrls.forEach(url => {
                if (this.checkUrl(url)) {
                    this.arUrls.push(url)
                }
            })
        }
    },
}
</script>
