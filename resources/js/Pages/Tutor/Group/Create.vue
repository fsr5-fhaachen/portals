<template>
    <div class="container">
        <div class="bg mt-3 ml-5 pl-5 opacity-25"></div>
        <div class="row pt-5 pt-lg-0">
            <div class="col-lg-6 mx-auto mt-5 pt-5 pt-lg-0">
                <h1>Erstelle hier deine Gruppe!</h1>
                <div>Bitte fülle alle Eingabefelder aus.</div>
                <div class="mt-3">
                    <form>
                        <div class="form-group">
                            <label for="GroupCount">Wie viele Gruppen soll es geben?</label>
                            <input type="text" class="form-control" id="GroupCount"
                                   placeholder="Gruppenanzahl">
                        </div>
                        <p class="mt-3">Sollen die Gruppen nach Studiengang getrennt sein?</p>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="Studiengang" id="Seperated" value="1" checked>
                            <label class="form-check-label" for="Seperated">
                                Ja
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="Studiengang" id="NotSeperated" value="0">
                            <label class="form-check-label" for="NotSeperated">
                                Nein
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary text-white mt-3">Erstellen</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</template>
<script>
    export default {
        name: 'Create',
    }
</script>
