<template>
    <div>
        <div class="container position-relative">
            <div class="bg mt-3 ml-5 pl-5 opacity-25"></div>
            <div class="row pt-5 pt-lg-0 position-relative">
                <div class="col-lg-6 mx-auto mt-5 pt-5 pt-lg-0">
                    <h1>Neue Daten anlegen</h1>
                    <div>
                        <p class="h5 mt-4">Eingabe einer neuen Gruppe</p>
                        <form class="row row-cols-lg-auto g-3 align-items-center">
                            <div class="col-12">
                                <label class="visually-hidden" for="Gruppenname">Gruppe</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="Gruppenname" placeholder="Gruppenname">
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Absenden</button>
                            </div>
                        </form>
                        <p class="h5 mt-4">Eingabe eines neuen Tutoren</p>
                        <form class="row row-cols-lg-auto g-3 align-items-center">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="FirstName"></label>
                                    <input type="text" class="form-control" id="FirstName"
                                           placeholder="Vorname">
                                </div>
                                <div class="form-group">
                                    <label for="LastName"></label>
                                    <input type="text" class="form-control" id="LastName"
                                           placeholder="Nachname">
                                </div>
                                <div class="form-group">
                                    <label for="InputEmail"></label>
                                    <input type="email" class="form-control" id="InputEmail"
                                           aria-describedby="emailHelp"
                                           placeholder="E-Mail">
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary mt-2">Absenden</button>
                                </div>
                            </div>
                        </form>
                        <p class="h5 mt-4">Eingabe einer neuen Station</p>
                        <form class="row row-cols-lg-auto g-3 align-items-center">
                            <div class="col-12">
                                <label class="visually-hidden" for="Station">Station</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="Station"
                                           placeholder="Neuer Stationsname">
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Absenden</button>
                            </div>
                        </form>
                        <p class="h5 mt-4">Eingabe eines neuen Zeitslots</p>
                        <form class="row row-cols-lg-auto g-3 align-items-center">
                            <div class="col-12">
                                <label class="visually-hidden" for="ZeitslotName">Zeitslots</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="ZeitslotName"
                                           placeholder="Zeitslot Name">
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="visually-hidden" for="ZeitslotTime">Zeitslots</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="ZeitslotTime"
                                           placeholder="Zeitslot Uhrzeit">
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary mb-3">Absenden</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: 'NewData',
    }
</script>

<style>

</style>
