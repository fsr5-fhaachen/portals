<template>
    <div class="container">
        <div class="bg mt-3 ml-5 pl-5 opacity-25"></div>
        <div class="row pt-5 pt-lg-0">
            <div class="col mx-auto mt-5 pt-5 pt-lg-0">
                <h1 class="mb-3">Übersicht für Tutoren</h1>
                <div>Hier findest du Informationen über deine Gruppen und den heutigen Zeitplan.</div>
            </div>
            <div class="row">
                <div class="col-lg-4 mx-auto mt-5">
                    Gruppen Infos
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Vorname</th>
                            <th scope="col">Nachname</th>
                            <th scope="col">Studiengang</th>
                        </tr>
                        </thead>
                        <tbody>
                        <!-- Schleife über Teilnehmer -->
                        <tr>
                            <th scope="row">1</th>
                            <td>Max</td>
                            <td>Mustermann</td>
                            <td>MCD</td>
                        </tr>

                        </tbody>
                    </table>
                </div>
                <div class="col-lg-4 mx-auto mt-5">
                    Stationen/Zeitplan je nach Tag
                </div>
            </div>
        </div>
    </div>

</template>
<script>
    export default {
        name: 'TutorView',
    }
</script>
