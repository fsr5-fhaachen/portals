<template>
    <div>
        <div class="container position-relative">
            <div class="bg mt-3 ml-5 pl-5 opacity-25"></div>
            <div class="row pt-5 pt-lg-0">
                <div class="col-lg-6 mx-auto mt-5 pt-5 pt-lg-0">
                    <h1>Herzlich Willkommen bei der Erstiwoche des FB5!</h1>
                    <div>Melde dich bitte hier an um einer Gruppe zugeordnet zu werden.</div>
                    <div class="mt-3">
                        <form>
                            <div class="form-group">
                                <label for="FirstName"></label>
                                <input type="text" class="form-control" id="FirstName"
                                       placeholder="Vorname">
                            </div>
                            <div class="form-group mt-2">
                                <label for="LastName"></label>
                                <input type="text" class="form-control" id="LastName"
                                       placeholder="Nachname">
                            </div>
                            <div class="form-group mt-2">
                                <label for="InputEmail"></label>
                                <input type="email" class="form-control" id="InputEmail" aria-describedby="emailHelp"
                                       placeholder="E-Mail">
                            </div>
                            <div class="input-group mt-4 pt-1">
                                <div class="input-group-prepend">
                                    <label class="input-group-text" for="Studiengang">Studiengang</label>
                                </div>
                                <select class="custom-select form-control" id="Studiengang">
                                    <option selected>Wähle deinen Studiengang aus...</option>
                                    <option value="MCD">MCD</option>
                                    <option value="WI">WI</option>
                                    <option value="INF">INF</option>
                                    <option value="ET">ET</option>
                                </select>
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
    }
</script>

<style>

</style>
