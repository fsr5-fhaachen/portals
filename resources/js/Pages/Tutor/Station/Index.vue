<template>
    <div class="container position-relative">
        <div class="bg mt-3 ml-5 pl-5 opacity-25"></div>
        <div class="row pt-5 pt-lg-0 position-relative">
            <div class="col mx-auto mt-5 pt-5 pt-lg-0">
                <h1 class="mb-3">Stationsübersicht: {{ station.name }} <Link href="/tutor/overview/" class="btn btn-primary text-white">Zurück zur Übersicht</Link></h1>
                <div>Hier findest du Informationen über deine Station.</div>
            </div>
            <div class="row">
                <div class="col-12 mx-auto mt-5">
                    <p class="h5">Tutoren</p>
                    <p v-if="station.tutors.length">
                        {{ station.tutors.map(tutor => `${ tutor.firstname } ${ tutor.lastname }`).join(', ') }}
                    </p>
                </div>
                <div class="col-12 mx-auto mt-5">
                    <p class="h5">Stationen</p>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th scope="col">Reihenfolge</th>
                                    <th scope="col">Gruppe A</th>
                                    <th scope="col">Gruppe A Erledigt</th>
                                    <th scope="col">Gruppe B</th>
                                    <th scope="col">Gruppe B Erledigt</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="groupsByStep in groupsBySteps" :key="groupsByStep.id">
                                    <th scope="row">{{ groupsByStep.step }}</th>
                                    <td>{{ groupsByStep.groups[0].group.title }}</td>
                                    <td>
                                        <input 
                                            :checked="groupsByStep.groups[0].done"
                                            type="checkbox" 
                                            @change="stationDone($event, groupsByStep.groups[0].id)"
                                        >
                                    </td>
                                    <td>{{ groupsByStep.groups[1].group.title }}</td>
                                    <td>
                                        <input 
                                            :checked="groupsByStep.groups[1].done"
                                            type="checkbox" 
                                            @change="stationDone($event, groupsByStep.groups[1].id)"
                                        >
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
    import { Link } from '@inertiajs/inertia-vue';

    export default {
        name: 'Index',
        components: {
            Link
        },
        data () {
            return {
                csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                showEmails: false,
            }
        },
        props: {
            station: {
                type: Object,
                required: true
            },
            groupsBySteps: {
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
            studentAttended(event, id) {
                const requestOptions = {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": this.csrf,
                    },
                };
                if(event.target.checked) {
                    fetch('/tutor/group/student/' + id +'/attended', requestOptions);
                } else {
                    fetch('/tutor/group/student/' + id +'/unattended', requestOptions);
                }
            },
            stationDone(event, id) {
                const requestOptions = {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": this.csrf,
                    },
                };
                if(event.target.checked) {
                    fetch('/tutor/group/station/' + id +'/done', requestOptions);
                } else {
                    fetch('/tutor/group/station/' + id +'/undone', requestOptions);
                }
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
