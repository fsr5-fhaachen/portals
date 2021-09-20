<template>
    <div>
        <div class="container position-relative">
            <div class="bg mt-3 ml-5 pl-5 opacity-25"></div>
            <div class="row pt-5 pt-lg-0">
                <div class="col-lg-6 mx-auto mt-5 pt-5 pt-lg-0">
                    <h1>Herzlich Willkommen bei der Erstiwoche des FB5!</h1>
                    <div class="mt-3">
                        <form>
                            <div class="form-group mt-2">
                                <label for="InputEmailTutor"></label>
                                <input type="email" class="form-control" id="InputEmailTutor" aria-describedby="emailHelp"
                                       placeholder="E-Mail">
                            </div>
                            <div class="form-group">
                                <label for="inputPassword" class="col-form-label"></label>
                                <input type="password" class="form-control" id="inputPassword" placeholder="Passwort">
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
    }
</script>

<style>

</style>
