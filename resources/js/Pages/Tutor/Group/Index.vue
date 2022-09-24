<template>
  <div class="container position-relative">
    <div class="bg mt-3 ml-5 pl-5 opacity-25"></div>
    <div class="row pt-5 pt-lg-0 position-relative">
      <div class="col mx-auto mt-5 pt-5 pt-lg-0">
        <h1 class="mb-3">
          Gruppenübersicht: {{ group.title }}
          <Link href="/tutor/overview/" class="btn btn-primary text-white"
            >Zurück zur Übersicht</Link
          >
        </h1>
        <div>Hier findest du Informationen über deine Gruppe.</div>
      </div>
      <div class="row">
        <div class="col-12 mx-auto mt-5">
          <p class="h5">Tutoren</p>
          <p v-if="group.tutors.length">
            {{
              group.tutors
                .map((tutor) => `${tutor.firstname} ${tutor.lastname}`)
                .join(", ")
            }}
          </p>
        </div>
        <div class="col-12 mx-auto mt-5">
          <p class="h5">Gruppenmitglieder</p>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th scope="col">#</th>
                  <th scope="col">Vorname</th>
                  <th scope="col">Nachname</th>
                  <th scope="col">Studiengang</th>
                  <th scope="col">Anwesend</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(student, index) in students" :key="student.id">
                  <th scope="row">{{ index + 1 }}</th>
                  <td>{{ student.firstname }}</td>
                  <td>{{ student.lastname }}</td>
                  <td>{{ student.course }}</td>
                  <td>
                    <input
                      :checked="student.attended"
                      type="checkbox"
                      @change="studentAttended($event, student.id)"
                    />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="col-12 mx-auto mt-5">
          <p class="h5">Stationen</p>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th scope="col">Reihenfolge</th>
                  <th scope="col">Name</th>
                  <th scope="col">Erledigt</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="station in stations" :key="station.id">
                  <th scope="row">{{ station.pivot.step }}</th>
                  <td>{{ station.name }}</td>
                  <td>
                    <input
                      :checked="station.pivot.done"
                      type="checkbox"
                      @change="stationDone($event, station.pivot.id)"
                    />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="col-12 mx-auto mt-5 mb-5" v-if="isAdmin">
          <p class="h5">
            E-Mail Adressen der Gruppenmitglieder
            <span class="badge bg-danger">ADMIN</span>
          </p>
          <button
            class="btn btn-primary text-white"
            @click="showEmails = !showEmails"
          >
            Toogle E-Mails
          </button>

          <textarea
            v-if="showEmails"
            disabled
            class="mt-3 w-100"
            :rows="students.length"
            >{{
              students.map((student) => `${student.email}`).join("\n")
            }}</textarea
          >
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import { Link } from "@inertiajs/inertia-vue3";

export default {
  name: "Index",
  components: {
    Link,
  },
  data() {
    return {
      csrf: document
        .querySelector('meta[name="csrf-token"]')
        .getAttribute("content"),
      showEmails: false,
    };
  },
  props: {
    isAdmin: {
      type: Boolean,
      default: false,
    },
    group: {
      type: Object,
      required: true,
    },
    students: {
      type: Array,
      required: true,
    },
    stations: {
      type: Array,
    },
  },
  methods: {
    updateData() {
      this.$inertia.reload({
        preserveState: true,
        preserveScroll: true,
      });
    },
    studentAttended(event, id) {
      const requestOptions = {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": this.csrf,
        },
      };
      if (event.target.checked) {
        fetch("/tutor/group/student/" + id + "/attended", requestOptions);
      } else {
        fetch("/tutor/group/student/" + id + "/unattended", requestOptions);
      }
    },
    stationDone(event, id) {
      const requestOptions = {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": this.csrf,
        },
      };
      if (event.target.checked) {
        fetch("/tutor/group/station/" + id + "/done", requestOptions);
      } else {
        fetch("/tutor/group/station/" + id + "/undone", requestOptions);
      }
    },
  },
  created() {
    this.interval = setInterval(
      function () {
        this.updateData();
      }.bind(this),
      1000
    );
  },
  beforeDestroy() {
    clearInterval(this.interval);
  },
};
</script>
