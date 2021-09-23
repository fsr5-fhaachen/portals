<template>
    <div>
        <div class="container position-relative">
            <div class="bg mt-3 ml-5 pl-5 opacity-25"></div>
            <div class="row pt-5 pt-lg-0 position-relative">
                <div class="col-lg-6 mx-auto mt-5 pt-5 pt-lg-0">
                    <h1>Tutoren-Login</h1>
                    <div class="mt-3">
                        <form @submit.prevent="submit">
                            <div class="form-group mt-2">
                                <label for="email"></label>
                                <input type="email" class="form-control" id="email" aria-describedby="emailHelp"
                                       placeholder="E-Mail" v-model="form.email">
                                    <div class="text-danger" v-if="form.errors.email"><strong>{{ form.errors.email
                                    }}</strong></div>
                            </div>
                            <div class="form-group">
                                <label for="password" class="col-form-label"></label>
                                <input type="password" class="form-control" id="password" placeholder="Passwort" v-model="form.password">
                            <div class="text-danger" v-if="form.errors.password"><strong>{{ form.errors.password
                                    }}</strong></div>
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
        name: 'Index',
        data() {
            return {
                form: this.$inertia.form({
                    email: null,
                    password: null,
                }),
            }
        },
        methods: {
            submit() {
                this.form.post('/tutor/');
            },
        },
    }
</script>

<style>

</style>
