<template>
  <LayoutDashboardContent>
    <template #title>Event "{{ event.name }}" einteilen</template>

    <CardBase>
      <FormKit
        type="form"
        id="event-submit"
        @submit="submitHandler"
        :actions="false"
        v-model="form"
      >
        <FormContainer>
          <template v-if="event.type == 'group_phase'">
            <template v-if="hasCourse">
              <template v-for="(course, index) in courses" :key="course.id">
                <FormRow>
                  <UiH2>{{ course.name }}</UiH2>
                </FormRow>
                <FormRow>
                  <FormKit
                    type="number"
                    :name="'max_groups_' + course.id"
                    label="Maximale Gruppenanzahl"
                    placeholder="Leer lassen für die voreingestellte Anzahl"
                  />
                  <FormKit
                    type="number"
                    :name="'max_participants_' + course.id"
                    label="Maximale Teilnehmeranzahl pro Gruppe"
                    placeholder="Leer lassen für keine Begrenzung"
                  />
                </FormRow>

                <FormDivider v-if="index < courses.length - 1" />
              </template>
            </template>

            <FormRow v-else>
              <FormKit
                type="number"
                name="max_groups"
                label="Maximale Gruppenanzahl"
                placeholder="Leer lassen für die voreingestellte Anzahl"
              />
              <FormKit
                type="number"
                name="max_participants"
                label="Maximale Teilnehmeranzahl pro Gruppe"
                placeholder="Leer lassen für keine Begrenzung"
              />
            </FormRow>
          </template>

          <FormRow>
            <FormKit type="submit" label="Ich bin mir sicher" />
          </FormRow>
        </FormContainer>
      </FormKit>
    </CardBase>
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { ref, PropType } from "vue";
import { Inertia } from "@inertiajs/inertia";

const { event } = defineProps({
  courses: {
    type: Object as PropType<App.Models.Course[]>,
    required: true,
  },
  event: {
    type: Object as PropType<App.Models.Event>,
    required: true,
  },
  hasCourse: {
    type: Boolean,
    default: false,
  },
});

const form = ref({});

const submitHandler = async () => {
  Inertia.post("/dashboard/admin/event/" + event.id + "/submit", form.value);
};
</script>
