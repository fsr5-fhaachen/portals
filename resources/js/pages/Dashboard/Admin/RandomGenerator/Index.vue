<template>
  <LayoutDashboardContent>
    <template #title>Zufallsgenerator</template>

    <CardContainer>
      <CardBase>
        <FormContainer>
          <div class="flex flex-wrap items-center justify-between gap-3">
            <UiH2>Aktionen</UiH2>
            <div class="flex items-center gap-2 text-sm font-medium">
              <span
                :class="stateBadges[currentState].classes"
                class="rounded-md px-2 py-1 text-white"
              >
                {{ stateBadges[currentState].label }}
              </span>
              <span
                title="Personen im Lostopf / alle Personen"
                class="flex items-center gap-2 rounded-md bg-gray-100 px-2 py-1 text-gray-700 dark:bg-gray-800 dark:text-gray-300"
              >
                <FontAwesomeIcon :icon="['fas', 'user']" class="h-3 w-3" />
                {{ poolCount }} / {{ users.length }}
              </span>
            </div>
          </div>

          <UiMessage v-if="preselectedUser" type="warning">
            <template #message>
              Vorausgewählt:
              <strong>
                {{ preselectedUser.firstname }} {{ preselectedUser.lastname }}
              </strong>
              . Die Person wird beim Stoppen unabhängig von allen Einstellungen
              gezogen.
            </template>
          </UiMessage>
          <UiMessage
            v-else-if="!poolCount"
            type="warning"
            message="Es ist niemand im Lostopf. Wähle Fachbereiche aus, setze Ziehungen zurück oder wähle eine Person vor."
          />
          <UiMessage v-if="drawError" type="error" :message="drawError" />
          <UiMessage v-if="lastDrawnUser" type="success">
            <template #message>
              Gezogen:
              <strong>
                {{ lastDrawnUser.firstname }} {{ lastDrawnUser.lastname }}
              </strong>
            </template>
          </UiMessage>

          <FormRow>
            <AppButton
              @click="updateStateAndSubmit('running')"
              class="flex-1 text-center"
            >
              Zufallsgenerator starten
            </AppButton>
            <AppButton
              @click="updateStateAndSubmit('stopped')"
              theme="danger"
              class="flex-1 text-center"
            >
              Zufallsgenerator stoppen
            </AppButton>
          </FormRow>
          <FormRow>
            <AppButton
              @click="updateStateAndSubmit('idle')"
              theme="gray"
              class="flex-1 text-center"
            >
              Zufallsgenerator idle
            </AppButton>
            <AppButton
              v-if="preselectedUser"
              @click="preselectedUserId = null"
              theme="gray"
              class="flex-1 text-center"
            >
              Vorauswahl aufheben
            </AppButton>
          </FormRow>
        </FormContainer>
      </CardBase>

      <CardBase>
        <FormContainer>
          <UiH2>Wer wird gezogen?</UiH2>

          <div class="flex flex-wrap gap-4">
            <RandomGeneratorGroupToggleBox
              v-for="option in participantOptions"
              :key="option.value"
              :label="option.label"
              :icon="option.icon"
              :iconClasses="option.iconClasses"
              :selected="settings.participants === option.value"
              class="min-w-[12rem] flex-1"
              @toggle="settings.participants = option.value"
            />
          </div>
        </FormContainer>
      </CardBase>

      <CardBase>
        <FormContainer>
          <UiH2>Fachbereiche</UiH2>

          <p class="text-gray-500 dark:text-gray-400">
            Nur Personen der ausgewählten Fachbereiche werden gezogen.
          </p>
          <!-- at most 5 courses per row, the courses of a row fill the whole width -->
          <div class="flex flex-wrap gap-4">
            <CourseToggleBox
              v-for="course in courses"
              :key="course.id"
              :course="course"
              :selected="settings.course_ids.includes(course.id)"
              class="min-w-[12rem] flex-1 basis-[calc((100%_-_4rem)/5)]"
              @toggle="toggleCourse(course.id)"
            />
          </div>
          <FormRow>
            <AppButton
              @click="settings.course_ids = courses.map((course) => course.id)"
              theme="gray"
              class="flex-1 text-center"
            >
              Alle auswählen
            </AppButton>
            <AppButton
              @click="settings.course_ids = []"
              theme="gray"
              class="flex-1 text-center"
            >
              Keine auswählen
            </AppButton>
          </FormRow>
        </FormContainer>
      </CardBase>

      <CardBase>
        <FormContainer>
          <UiH2>Personen</UiH2>

          <FormKit type="text" label="Suche" v-model="query" />
          <FormKit
            type="checkbox"
            label="Personen mit Opt-out ausblenden"
            v-model="settings.hide_opted_out"
          />
          <FormKit
            type="checkbox"
            label="Personen anderer Fachbereiche ausblenden"
            v-model="settings.hide_other_courses"
          />
          <FormKit
            type="checkbox"
            label="Bereits gezogene Personen ausblenden"
            v-model="settings.hide_drawn"
          />
          <FormRow>
            <AppButton
              @click="showResetDrawn = true"
              :disabled="!drawnCount"
              theme="danger"
              class="flex-1 text-center"
            >
              Alle Ziehungen zurücksetzen
            </AppButton>
          </FormRow>
        </FormContainer>
      </CardBase>

      <GridContainer :cols="3">
        <RandomGeneratorUserCandidateCard
          v-for="filteredUser in filteredUsers"
          :key="filteredUser.id"
          :user="filteredUser"
          :isSelected="filteredUser.id === preselectedUserId"
          :isInSelectedCourses="isInSelectedCourses(filteredUser)"
          @select="togglePreselectedUser(filteredUser.id)"
          @resetDrawn="resetDrawnUser(filteredUser)"
        />
      </GridContainer>

      <CardBase>
        <FormContainer>
          <UiH2>Einstellungen</UiH2>

          <FormKit
            type="checkbox"
            label="Opt-out für Erstis freischalten"
            help="Erstis können sich dann auf einer eigenen Seite vom Zufallsgenerator abmelden."
            v-model="settings.opt_out_enabled"
          />
          <FormKit
            type="checkbox"
            label="Opt-out für Tutoren freischalten"
            help="Tutoren können sich dann im Tutor-Bereich vom Zufallsgenerator abmelden."
            v-model="settings.tutor_opt_out_enabled"
          />
        </FormContainer>
      </CardBase>
    </CardContainer>

    <UiConfirmModal
      v-if="showResetDrawn"
      title="Ziehungen zurücksetzen"
      message="Sollen alle Ziehungen zurückgesetzt werden? Danach können alle Personen wieder gezogen werden."
      confirmLabel="Zurücksetzen"
      :processing="processing"
      @close="showResetDrawn = false"
      @confirm="resetDrawn"
    />
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { computed, ref, PropType, watch } from "vue";
import { router } from "@inertiajs/vue3";

type RandomGeneratorState = "setup" | "idle" | "running" | "stopped";

type RandomGeneratorParticipants = "first_years" | "tutors";

type RandomGeneratorSettings = {
  participants: RandomGeneratorParticipants;
  course_ids: number[];
  opt_out_enabled: boolean;
  tutor_opt_out_enabled: boolean;
  hide_opted_out: boolean;
  hide_other_courses: boolean;
  hide_drawn: boolean;
};

const baseUrl = "/dashboard/admin/random-generator";

const participantOptions: {
  value: RandomGeneratorParticipants;
  label: string;
  icon: string;
  iconClasses: string;
}[] = [
  {
    value: "first_years",
    label: "Erstis",
    icon: "fa-user-graduate",
    iconClasses: "bg-fhac-mint-dark",
  },
  {
    value: "tutors",
    label: "Tutoren",
    icon: "fa-chalkboard-user",
    iconClasses: "bg-gray-600 dark:bg-gray-700",
  },
];

const stateBadges: Record<
  RandomGeneratorState,
  { label: string; classes: string }
> = {
  setup: { label: "Setup", classes: "bg-gray-500" },
  idle: { label: "Idle", classes: "bg-gray-500" },
  running: { label: "Läuft", classes: "bg-fhac-mint-dark" },
  stopped: { label: "Gestoppt", classes: "bg-red-700" },
};

const props = defineProps({
  users: {
    type: Array as PropType<Models.User[]>,
    required: true,
  },
  courses: {
    type: Array as PropType<App.Models.Course[]>,
    required: true,
  },
  settings: {
    type: Object as PropType<RandomGeneratorSettings>,
    required: true,
  },
  poolCount: {
    type: Number,
    required: true,
  },
  state: {
    type: Object as PropType<{ state: RandomGeneratorState }>,
    required: true,
  },
});

const query = ref("");
const preselectedUserId = ref<number | null>(null);
const currentState = ref<RandomGeneratorState>(props.state.state);
const drawError = ref<string | null>(null);
const lastDrawnUser = ref<{ firstname: string; lastname: string } | null>(null);
const showResetDrawn = ref(false);
const processing = ref(false);

// local copy of the settings, every change is saved directly
const settings = ref<RandomGeneratorSettings>({ ...props.settings });

const toggleCourse = (courseId: number) => {
  settings.value.course_ids = settings.value.course_ids.includes(courseId)
    ? settings.value.course_ids.filter((id) => id !== courseId)
    : [...settings.value.course_ids, courseId];
};

const preselectedUser = computed(
  () => props.users.find((user) => user.id === preselectedUserId.value) ?? null,
);

const drawnCount = computed(
  () => props.users.filter((user) => user.random_generator_drawn_at).length,
);

const isInSelectedCourses = (user: Models.User) =>
  settings.value.course_ids.includes(user.course_id);

const filteredUsers = computed(() => {
  const search = query.value.toLowerCase();

  return props.users.filter((user) => {
    // the preselected user is always visible
    if (user.id === preselectedUserId.value) {
      return true;
    }

    if (settings.value.hide_opted_out && user.random_generator_opt_out) {
      return false;
    }
    if (settings.value.hide_other_courses && !isInSelectedCourses(user)) {
      return false;
    }
    if (settings.value.hide_drawn && user.random_generator_drawn_at) {
      return false;
    }

    return (
      !search ||
      user.firstname.toLowerCase().includes(search) ||
      user.lastname.toLowerCase().includes(search) ||
      user.email.toLowerCase().includes(search)
    );
  });
});

// save the settings shortly after the last change, so that fast clicks do not create many requests
// the users are only reloaded if the group (first-year students or tutors) changed
let saveSettingsTimeout: ReturnType<typeof setTimeout> | undefined;
let loadedParticipants = props.settings.participants;
watch(
  settings,
  () => {
    clearTimeout(saveSettingsTimeout);
    saveSettingsTimeout = setTimeout(() => {
      const participantsChanged =
        settings.value.participants !== loadedParticipants;
      if (participantsChanged) {
        preselectedUserId.value = null;
        loadedParticipants = settings.value.participants;
      }

      router.post(`${baseUrl}/settings`, settings.value, {
        preserveScroll: true,
        preserveState: true,
        only: participantsChanged
          ? ["settings", "poolCount", "users"]
          : ["settings", "poolCount"],
      });
    }, 300);
  },
  { deep: true },
);

const togglePreselectedUser = (userId: number) => {
  preselectedUserId.value = preselectedUserId.value === userId ? null : userId;
};

const updateStateAndSubmit = async (newState: RandomGeneratorState) => {
  drawError.value = null;

  const response = await fetch(baseUrl, {
    method: "POST",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
      "X-CSRF-TOKEN":
        document
          .querySelector("meta[name='csrf-token']")
          ?.getAttribute("content") || "",
    },
    body: JSON.stringify({
      state: newState,
      // the server draws the user, a preselected user overrides the pool
      user_id: newState === "stopped" ? preselectedUserId.value : null,
    }),
  });

  const data = await response.json().catch(() => ({}));

  if (!response.ok || !data.success) {
    drawError.value =
      data.message ?? "Der Zufallsgenerator konnte nicht geändert werden.";
    return;
  }

  currentState.value = newState;

  if (newState === "stopped") {
    lastDrawnUser.value = data.user;
    preselectedUserId.value = null;

    router.reload({ only: ["users", "poolCount"] });
  }
};

const resetDrawnUser = (user: Models.User) => {
  router.post(
    `${baseUrl}/user/${user.id}/reset-drawn`,
    {},
    { preserveScroll: true, preserveState: true },
  );
};

const resetDrawn = () => {
  processing.value = true;
  router.post(
    `${baseUrl}/reset-drawn`,
    {},
    {
      preserveScroll: true,
      preserveState: true,
      onFinish: () => {
        processing.value = false;
        showResetDrawn.value = false;
      },
    },
  );
};
</script>
