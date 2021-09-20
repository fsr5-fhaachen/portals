<template>
    <div class="container">
        <div class="bg mt-3 ml-5 pl-5 opacity-25"></div>
        <div class="row pt-5 pt-lg-0">
            <div class="col mx-auto mt-5 pt-5 pt-lg-0">
                <h1 class="mb-3">Übersicht für Tutoren</h1>
                <div>Hier findest du Informationen über deine Gruppen und den heutigen Zeitplan.</div>
            </div>
            <div class="row">
                <div class="col-12 mx-auto mt-5">
                    <p class="h5"> Gruppen Infos</p>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Anwesend</th>
                                <th scope="col">Vorname</th>
                                <th scope="col">Nachname</th>
                                <th scope="col">Studiengang</th>
                            </tr>
                            </thead>
                            <tbody>
                            <!-- Schleife über Teilnehmer -->
                            <tr>
                                <th scope="row">1</th>
                                <td>
                                    <div class="input-group-text">
                                        <input type="checkbox" aria-label="Checkbox for following text input">
                                    </div>
                                </td>
                                <td>Max</td>
                                <td>Mustermann</td>
                                <td>MCD</td>
                            </tr>

                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="col-12 mx-auto mt-5">

                    <p class="h5"> Stationen/Zeitplan je nach Tag</p>
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
            </div>
        </div>
    </div>
</template>
<script>
    export default {
        name: 'Index',
    }
</script>
