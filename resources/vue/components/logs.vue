<template>
    <div>
        <div class="indexing__logs">
            <ul>
                <li
                    v-for="(log, i) in arLogs" :key="i"
                    :class="log.type"
                >
                    <span
                        v-if="log.dropdown.length"
                        class="indexing__logs--drop-btn"
                        :class="{open: arDropdown.includes(i)}"
                        @click="dropdown(i)"
                    >
                    </span>
                        {{log.message}}
                    <ul
                        v-if="log.dropdown.length && arDropdown.includes(i)"
                        class="indexing__logs--submessages"
                    >
                        <li
                            v-for="(submessage, j) in log.dropdown" :key="j"
                            :class="log.type"
                        >
                            {{submessage}}
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
        <span
            class="indexing__logs--clear"
            @click="clear()"
        >
            Очистить
        </span>
    </div>
</template>

<script>
export default {
    props: {
        arLogs: {
            type: Array,
            default() {
                return []
            }
        }
    },
    data () {
        return {
            dropActive: false,
            arDropdown: [],
        }
    },
    methods: {
        dropdown(i) {
            const index = this.arDropdown.indexOf(i);

            if (index === -1) {
                this.arDropdown.push(i);
            }
            else {
                this.arDropdown.splice(index, 1);
            }
        },

        clear() {
            this.$emit('clear')
        }
    }
}
</script>
