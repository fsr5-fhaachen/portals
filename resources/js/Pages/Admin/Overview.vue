<template>
    <div>
        <div class="container position-relative">
            <div class="bg mt-3 ml-5 pl-5 opacity-25"></div>
            <div class="row pt-5 pt-lg-0">
                <div class="col-lg-6 mx-auto mt-5 pt-5 pt-lg-0">
                    <h1>Admin Übersicht</h1>
                    <div class="table-responsive">
                        <table class="table">
                            <thead class="thead-dark">
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">GruppenNr.</th>
                                <th scope="col"> Tutoren</th>
                                <th scope="col">Zeitslot</th>
                                <th scope="col">Details</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <th scope="row">1</th>
                                <td>Gruppe 1</td>
                                <td>
                                    <div class="form-group">
                                    <div class="input-group">
                                        <select class="custom-select my-1 form-select mr-sm-2" id="Tutor1">
                                            <!-- Schleife über Dtaen aus DB -->
                                            <option value="1">Jan Pihl</option>
                                            <option value="3">Pablo</option>
                                        </select>
                                    </div>
                                    <div class="input-group">
                                        <select class="custom-select form-select my-1 mr-sm-2" id="Tutor2">
                                            <!-- Schleife über Dtaen aus DB -->
                                            <option value="2">Daniel Bortmann</option>
                                        </select>
                                    </div>
                                    </div>
                                </td>

                                <td>
                                    <select class="custom-select form-select my-1 mr-sm-2" id="Zeitslot">
                                        <!-- Schleife über Dtaen aus DB -->
                                        <option value="1">10:15 - 12:15 Uhr</option>
                                    </select>
                                </td>
                                <td>
                                    <a href="#" class="btn btn-primary text-white">Details</a>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary text-white mt-3">Absenden</button>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-danger text-white mt-3">Gruppeneinteilung resetten</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</template>

<script>
    export default {
        name: 'Overview',
    }
</script>

<style>

</style>
