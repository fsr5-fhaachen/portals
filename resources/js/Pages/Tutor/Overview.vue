<template>
    <div>
        <div class="container position-relative">
            <div class="bg mt-3 ml-5 pl-5 opacity-25"></div>
            <div class="row pt-5 pt-lg-0 pos position-relative">
                <div class="col-lg-6 mx-auto mt-5 pt-5 pt-lg-0">
                    <h1>Gruppen Übersicht <Link v-if="isAdmin" href="/admin/" class="btn btn-danger text-white">Admin-Login</Link></h1>
                    <div class="table-responsive">
                        <table class="table">
                            <thead class="thead-dark">
                                <tr>
                                    <th scope="col">Name</th>
                                    <th scope="col">Tutoren</th>
                                    <th v-if="showTimeslots" scope="col">Zeitslot</th>
                                    <th scope="col">Aktion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="group in groups" :key="group.id"
                                :class="{'bg-warning': group.id == tutor.group_id}">
                                    <td>{{ group.title }}</td>
                                    <td>
                                        <div v-if="group.tutors.length">
                                            <p v-for="tutor in group.tutors" :key="tutor.id">
                                                {{ tutor.firstname }} {{ tutor.lastname }}
                                            </p>
                                        </div>
                                        <p v-else>Keine Tutoren</p>
                                    </td>
                                    <td v-if="showTimeslots">
                                        <p v-if="group.timeslot && group.timeslot.name">
                                            {{ group.timeslot.name }}
                                        </p>
                                        <p v-else>Keine Zeitslot</p>
                                    </td>
                                    <td>
                                        <Link :href="'/tutor/group/' + group.id + '/join'" method="post" class="btn btn-danger text-white">Gruppe beitreten</Link>
                                        <Link :href="'/tutor/group/' + group.id" class="btn btn-primary text-white">Details</Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <h1 class="mt-4">Stations Übersicht</h1>
                    <div class="table-responsive">
                        <table class="table">
                            <thead class="thead-dark">
                                <tr>
                                    <th scope="col">Name</th>
                                    <th scope="col">Tutoren</th>
                                    <th scope="col">Aktion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="station in stations" :key="station.id"
                                :class="{'bg-warning': station.id == tutor.station_id}">
                                    <td>{{ station.name }}</td>
                                    <td>
                                        <div v-if="station.tutors.length">
                                            <p v-for="tutor in station.tutors" :key="tutor.id">
                                                {{ tutor.firstname }} {{ tutor.lastname }}
                                            </p>
                                        </div>
                                        <p v-else>Keine Tutoren</p>
                                    </td>
                                    <td>
                                        <Link :href="'/tutor/station/' + station.id + '/join'" method="post" class="btn btn-danger text-white">Station beitreten</Link>
                                        <Link :href="'/tutor/station/' + station.id" class="btn btn-primary text-white">Details</Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</template>

<script>
    import { Link } from '@inertiajs/inertia-vue'
    export default {
        name: 'Overview',
        components: {
            Link
        },
        props: {
            isAdmin: {
                type: Boolean,
                default: false
            },
            tutors: {
                type: Array,
                required: true
            },
            stations: {
                type: Array,
                required: true
            },
            groups: {
                type: Array,
                required: true
            },
            tutor: {
                type: Object,
                required: true
            },
            showTimeslots: {
                type: Boolean,
                default: false
            },
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
            }.bind(this), 5000);
        },
        beforeDestroy() {
            clearInterval(this.interval);
        },
    }
</script>

<style>

</style>
