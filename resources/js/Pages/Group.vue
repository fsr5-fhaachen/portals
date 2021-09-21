<template>
    <div class="container position-relative">
        <div class="bg mt-3 ml-5 pl-5 opacity-25"></div>
        <div class="row pt-5 pt-lg-0">
            <div class="col-lg-6 mx-auto mt-5 pt-5 pt-lg-0 text-center">
                <h3 class="mt-5">Hallo {{ student.firstname }} {{ student.lastname }},</h3>
                
                <div v-if="group">
                    <p>Du bist in der Gruppe:</p>
                    <h1 class="text-primary mt-3">{{ group.title }}</h1>
                </div>
                <p v-else>Die Einteilung hat noch nicht begonnen, bitte warte auf weitere Anweisungen durch die Tutoren.</p>
            </div>
        </div>
    </div>

</template>
<script>
    export default {
        name: 'Group',
        props: {
            student: {
                type: Object,
                required: true
            },
            group: {
                type: Object
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
            if (!this.group) {
                this.interval = setInterval(function () {
                    this.updateData();
                }.bind(this), 10000);
            }
        },
        beforeDestroy() {
            clearInterval(this.interval);
        },
        watch: {
            group: function (group) {
                if (group.id) {
                    clearInterval(this.interval);
                }
            },
        }
    }
</script>
