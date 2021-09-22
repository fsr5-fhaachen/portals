<template>
    <div class="container">
        <div class="bg mt-3 ml-5 pl-5 opacity-25"></div>
        <div class="row pt-5 pt-lg-0 position-relative">
            <div class="col-lg-6 mx-auto mt-5 pt-5 pt-lg-0">
                <h1>Admin-Übersicht <Link href="/tutor/overview/" class="btn btn-primary text-white">Zurück zur Übersicht</Link></h1>
                <div>Willkommen bei der Admin-Übersicht.</div>
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
                        <Link href="/admin/start/" class="btn btn-danger text-white">GRUPPENEINTEILUNG STARTEN</Link>
                    </div>
                </div>
            </div>
        </div>
    </div>

</template>
<script>
    import { Link } from '@inertiajs/inertia-vue';

    export default {
        name: 'Index',
        components: {
            Link
        },
        props: {
            stats: {
                type: Array,
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
