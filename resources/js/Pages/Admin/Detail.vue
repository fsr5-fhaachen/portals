<template>
    <div>
        <div class="container position-relative">
            <div class="bg mt-3 ml-5 pl-5 opacity-25"></div>
            <div class="row pt-5 pt-lg-0">
                <div class="col-lg-6 mx-auto mt-5 pt-5 pt-lg-0">
                    <h1>Gruppe 1</h1>
                    <div>
                        Tutoren
                        <ul> <!-- Schleife -->
                            <li>Jan Phil</li>
                            <li>Daniel Bortmann</li>
                        </ul>
                    </div>
                    <div>
                        Gruppen Infos
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
                        <div>
                        Geplante Tour:
                            <table class="table table-striped">
                                <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Staion</th>
                                    <th scope="col">Zeit</th>
                                </tr>
                                </thead>
                                <tbody>
                                <!-- Schleife über Teilnehmer -->
                                <tr>
                                    <th scope="row">1</th>
                                    <td>Station 1</td>
                                    <td>10:15-12:15 Uhr</td>
                                </tr>
                                </tbody>
                            </table>
                            <form>
                                <div class="form-group">
                                    <label for="exampleFormControlSelect1">Tour-/Routenauswahl</label>
                                    <select class="form-control custom-select form-select" id="exampleFormControlSelect1">
                                        <!-- daten aus DB -->
                                        <option value="1"> Route 1</option>
                                        <option value="2">Route 2</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary text-white mt-3">Absenden</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</template>

<script>
    export default {
        name: 'Detail',
    }
</script>

<style>

</style>
