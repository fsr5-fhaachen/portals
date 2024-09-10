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
              <template
                v-for="(course_collection, index) in course_collections"
                :key="index"
              >
                <FormRow>
                  <UiH2>
                    <template v-for="course in course_collection.join(' | ')">
                      {{ course }}
                    </template>
                  </UiH2>
                </FormRow>
                <FormRow>
                  <FormKit
                    type="number"
                    :name="'max_groups_' + index"
                    label="Maximale Gruppenanzahl"
                    placeholder="Leer lassen für die voreingestellte Anzahl"
                  />
                  <FormKit
                    type="number"
                    :name="'max_participants_' + index"
                    label="Maximale Teilnehmeranzahl pro Gruppe"
                    placeholder="Leer lassen für keine Begrenzung"
                  />
                </FormRow>

                <FormDivider v-if="index < course_collections.length - 1" />
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
  course_collections: {
    type: Object,
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
