<template>
    <div>
        <div class="container position-relative">
            <div class="bg mt-3 ml-5 pl-5 opacity-25"></div>
            <div class="row pt-5 pt-lg-0">
                <div class="col-lg-6 mx-auto mt-5 pt-5 pt-lg-0">
                    <h1>Herzlich Willkommen bei der Erstiwoche des FB5!</h1>
                    <div>Melde dich bitte hier an um einer Gruppe zugeordnet zu werden.</div>
                    <div class="mt-3">
                        <form @submit.prevent="submit">
                            <div class="form-group">
                                <label for="student_firstname"></label>
                                <input type="text" class="form-control" id="student_firstname"
                                       placeholder="Vorname" v-model="form.student_firstname">
                                <div class="text-danger" v-if="form.errors.student_firstname"><strong>{{ form.errors.student_firstname }}</strong></div>
                            </div>
                            <div class="form-group mt-2">
                                <label for="student_lastname"></label>
                                <input type="text" class="form-control" id="student_lastname"
                                       placeholder="Nachname" v-model="form.student_lastname">
                                <div class="text-danger" v-if="form.errors.student_lastname"><strong>{{ form.errors.student_lastname }}</strong></div>
                            </div>
                            <div class="form-group mt-2">
                                <label for="student_email"></label>
                                <input type="email" class="form-control" id="student_email" aria-describedby="emailHelp"
                                       placeholder="E-Mail" v-model="form.student_email">
                                <div class="text-danger" v-if="form.errors.student_email"><strong>{{ form.errors.student_email }}</strong></div>
                            </div>
                            <div class="form-group mt-4">
                                <div class="input-group pt-1">
                                    <div class="input-group-prepend">
                                        <label class="input-group-text" for="student_course">Studiengang</label>
                                    </div>
                                    <select class="custom-select form-control" id="student_course" v-model="form.student_course">
                                        <option selected>Wähle deinen Studiengang aus...</option>
                                        <option value="MCD">MCD</option>
                                        <option value="WI">WI</option>
                                        <option value="INF">INF</option>
                                        <option value="ET">ET</option>
                                    </select>
                                </div>
                                <div class="text-danger" v-if="form.errors.student_course"><strong>{{ form.errors.student_course }}</strong></div>
                            </div>
                            <button type="submit" class="btn btn-primary text-white mt-3">Login</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</template>

<script>
    export default {
        name: 'Home',
        data() {
            return {
                form: this.$inertia.form({
                    student_firstname: null,
                    student_lastname: null,
                    student_email: null,
                    student_course: null,
                }),
            }
        },
        methods: {
            submit() {
                this.form.post('/');
            },
        },
    }
</script>
<style>

</style>
