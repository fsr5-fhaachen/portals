<template>
    <div>
        <div class="container position-relative">
            <div class="bg mt-3 ml-5 pl-5 opacity-25"></div>
            <div class="row pt-5 pt-lg-0 position-relative">
                <div class="col-lg-6 mx-auto mt-5 pt-5 pt-lg-0">
                    <h1>Herzlich Willkommen bei der Erstiwoche des FB5!</h1>
                    <div>Logge dich bitte hier ein um einer Gruppe zugeordnet zu werden.</div>
                    <div class="mt-3">
                        <form @submit.prevent="submit">
                            <div class="form-group mt-2">
                                <label for="email"></label>
                                <input type="email" class="form-control" id="email" aria-describedby="emailHelp"
                                       placeholder="E-Mail" v-model="form.email">
                                <div class="text-danger" v-if="form.errors.email"><strong>{{ form.errors.email
                                    }}</strong></div>
                            </div>
                            <button type="submit" class="btn btn-primary text-white mt-3">Einloggen</button>
                        </form>
                    </div>
                    <div class="mt-3">
                        <Link href="/login/">Du hast dich noch nicht für diese Gruppenphase angemeldet? Dann melde dich hier an.</Link>
                    </div>
                </div>
            </div>
        </div>
    </div>

</template>

<script>
    import { Link } from '@inertiajs/inertia-vue';

    export default {
        name: 'Login',
        components: {
            Link
        },
        data() {
            return {
                form: this.$inertia.form({
                    email: null,
                }),
            }
        },
        methods: {
            submit() {
                this.form.post('/login/');
            },
        },
    }
</script>