<template>
    <div class="container position-relative">
        <div class="bg mt-3 ml-5 pl-5 opacity-25"></div>
        <div class="row pt-5 pt-lg-0 position-relative">
            <div class="col mx-auto mt-5 pt-5 pt-lg-0">
                <h1 class="mb-3">Gruppenübersicht: {{ group.title }}</h1>
                <div>Hier findest du Informationen über deine Gruppe.</div>
            </div>
            <div class="row">
                <div class="col-12 mx-auto mt-5">
                    <p class="h5">Gruppenmitglieder</p>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Vorname</th>
                                    <th scope="col">Nachname</th>
                                    <th scope="col">Studiengang</th>
                                    <th scope="col">Anwesend</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(student, index) in students" :key="student.id">
                                    <th scope="row">{{ index + 1 }}</th>
                                    <td>{{ student.firstname }}</td>
                                    <td>{{ student.lastname }}</td>
                                    <td>{{ student.course }}</td>
                                    <td>
                                        <input 
                                            :checked="student.attended"
                                            type="checkbox" 
                                            @change="studentAttended($event, student.id)"
                                            aria-label="Checkbox for following text input"
                                        >
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="col-12 mx-auto mt-5">

                    <p class="h5">Stationen/Zeitplan je nach Tag</p>
                    <form>
                        <div class="form-group col-md-6">
                            <label class="my-1 mr-2" for="currentStation">Aktuelle Station</label>
                            <select class="custom-select form-select my-1 mr-sm-2" id="currentStation">
                                <option selected>Wähle deine aktuelle Station</option>
                                <!-- Schleife über Dtaen aus DB -->
                                <option value="1">Elisenbrunnen</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="my-1 mr-2" for="nextStation">Nächste Station</label>
                            <select class="custom-select form-select my-1 mr-sm-2" id="nextStation">
                                <option selected>Wähle deine nächste Station</option>
                                <!-- Schleife über Dtaen aus DB -->
                                <option value="1">Ponttor</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary text-white mt-3 mb-3">Absenden</button>
                    </form>

                </div>
                
                <div class="col-12 mx-auto mt-5 mb-5" v-if="isAdmin">
                    <p class="h5">E-Mail Adressen der Gruppenmitglieder <span class="badge bg-danger">ADMIN</span></p>
                    <button class="btn btn-primary text-white" @click="showEmails = !showEmails">Toogle E-Mails</button>

                    <textarea v-if="showEmails" disabled class="mt-3 w-100" :rows="students.length">{{ students.map(student => `${student.email}`).join('\n') }}</textarea>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    export default {
        name: 'Index',
        data () {
            return {
                csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                showEmails: false,
            }
        },
        props: {
            isAdmin: {
                type: Boolean,
                default: false
            },
            group: {
                type: Object,
                required: true
            },
            students: {
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
