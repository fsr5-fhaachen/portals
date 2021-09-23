<template>
    <div class="container">
        <div class="bg mt-3 ml-5 pl-5 opacity-25"></div>
        <div class="row pt-5 pt-lg-0 position-relative">
            <div class="col-lg-6 mx-auto mt-5 pt-5 pt-lg-0">
                <h1>Start <Link href="/admin/" class="btn btn-primary text-white">Zurück zur Admin-Übersicht</Link></h1>
                <div>Du bist dabei die Gruppeneinteilung zu starten.</div>
                <div class="row">
                    <div class="col-12 mx-auto mt-5">
                        <p class="h5">Statistik</p>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <tbody>
                                    <tr v-for="stat in stats" :key="stat.title">
                                        <td>{{ stat.title }}</td>
                                        <td>{{ stat.value }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-12 mx-auto mt-5">
                        <p class="h5">Gruppeneinteilung</p>
                        <form @submit.prevent="submit">
                            <p v-if="groupsCount.all">
                                Es gibt {{ groupsCount.all }} Gruppen.
                                <ul v-if="groupsCount.courses.length">
                                    <li v-for="course in groupsCount.courses">
                                        {{ course.title }}: {{ course.count }}
                                    </li>
                                </ul>
                            </p>
                            <div v-else class="form-group">
                                <label for="groupCount">Wie viele Gruppen soll es geben?</label>
                                <input type="text" class="form-control" id="groupCount" placeholder="Gruppenanzahl" v-model="form.groupCount">
                                <div class="text-danger" v-if="form.errors.groupCount"><strong>{{ form.errors.groupCount }}</strong></div>
                            </div>
                            <button type="submit" class="btn btn-danger text-white mt-3">GRUPPENEINTEILUNG STARTEN (ES GIBT KEIN ZURÜCK MEHR)</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</template>
<script>
    import { Link } from '@inertiajs/inertia-vue';

    export default {
        name: 'Start',
        components: {
            Link
        },
        data() {
            return {
                form: this.$inertia.form({
                    groupCount: null,
                }),
            }
        },
        props: {
            stats: {
                type: Array,
                required: true
            },
            groupsCount: {
                type: Object,
                required: true
            }
        },
        methods: {
            updateData() {
                this.$inertia.reload({
                    preserveState: true,
                    preserveScroll: true,
                });
            },
            submit() {
                this.form.post('/admin/start/');
            },
        },
        created() {
            this.interval = setInterval(function () {
                this.updateData();
            }.bind(this), 1000);
        },
        beforeDestroy() {
            clearInterval(this.interval);
        },
    }
</script>