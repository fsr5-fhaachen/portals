<template>
    <div class="container">
        <div class="bg mt-3 ml-5 pl-5 opacity-25"></div>
        <div class="row pt-5 pt-lg-0">
            <div class="col-lg-6 mx-auto mt-5 pt-5 pt-lg-0">
                <h1 class="mb-3">Gruppen Fertigstellung</h1>
                <div>Hier die Gruppenübersicht: </div>
                <div>
                    Es haben sich ... Erstis angemeldet.

                    Daraus entstehen ... Gruppen.
                </div>
                <div class="mt-5">
                    Die Verteilung sieht wie folgt aus:

                    <table class="table table-striped mb-5 mt-3">
                        <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Anzahl Teilnehmer</th>
                        </tr>
                        </thead>
                        <tbody>
                        <!-- Schleife über Teilnehmer -->
                        <tr>
                            <th scope="row">1</th>
                            <td>7</td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div>
                    <p>Möchtest du an der Verteilung noch etwas ändern?</p>
                    <form>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="ChangeGroup" id="Change" value="1"
                                   checked>
                            <label class="form-check-label" for="Change">
                                Ja
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="Studiengang" id="NoChange" value="0">
                            <label class="form-check-label" for="NoChange">
                                Nein
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary text-white mt-4">Absenden</button>
                        <!-- wenn ja, dann href zu Create Group, wenn nein dann absenden und info an alle welche Gruppe -->
                    </form>
                </div>

            </div>
        </div>
    </div>

</template>
<script>
    export default {
        name: 'Finish',
    }
</script>
