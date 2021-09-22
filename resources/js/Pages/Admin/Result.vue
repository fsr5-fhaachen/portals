<template>
    <div class="container">
        <div class="bg mt-3 ml-5 pl-5 opacity-25"></div>
        <div class="row pt-5 pt-lg-0 position-relative">
            <div class="col-lg-6 mx-auto mt-5 pt-5 pt-lg-0">
                <h1>Ergebnis <Link href="/admin/" class="btn btn-primary text-white">Zurück zur Admin-Übersicht</Link></h1>
                <div>Die Gruppeneinteilung war erfolgreich.</div>
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
    }
</script>
