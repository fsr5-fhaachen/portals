<template>
  <LayoutDashboardContent>
    <template #title>{{ workshopSession.workshop?.name }}</template>
    <template #subtitle>
      <AppLink :href="`/dashboard/tutor/event/${event.id}`" theme="gray">
        &larr; Alle Workshops
      </AppLink>
    </template>

    <CardContainer>
      <CardBase>
        <UiDl>
          <UiDt>Wann</UiDt>
          <UiDd>
            {{ workshopSession.block?.name }}
            <template
              v-if="
                workshopSession.block?.starts_at &&
                workshopSession.block?.ends_at
              "
            >
              ,
              <UiTimeString
                :value="workshopSession.block.starts_at"
              />–<UiTimeString
                :value="workshopSession.block.ends_at"
                :withClockSuffix="true"
              />
            </template>
          </UiDd>

          <template v-if="workshopSession.location">
            <UiDt>Wo</UiDt>
            <UiDd>{{ workshopSession.location }}</UiDd>
          </template>

          <UiDt>Teilnehmende</UiDt>
          <UiDd>
            {{ workshopSession.minimum_participants }}–{{
              workshopSession.maximum_participants ?? "∞"
            }}
            Plätze
          </UiDd>
        </UiDl>
      </CardBase>

      <UiMessage
        v-if="workshopSession.is_cancelled"
        type="warning"
        message="Dieser Workshop fällt in diesem Block aus."
      />
      <UiMessage
        v-if="!event.workshops_published_at"
        type="info"
        message="Die Einteilung ist noch nicht veröffentlicht. Die Erstis sehen ihre Workshops noch nicht, die Liste kann sich noch ändern."
      />

      <RegistrationTable
        :courses="courses"
        :event="event"
        :hasFormResponses="hasFormResponses"
        :hideSlots="true"
        :hideGroups="true"
        :user="user"
      />
    </CardContainer>
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { PropType } from "vue";

defineProps({
  workshopSession: {
    type: Object as PropType<App.Models.WorkshopSession>,
    required: true,
  },
  event: {
    type: Object as PropType<App.Models.Event>,
    required: true,
  },
  courses: {
    type: Array as PropType<App.Models.Course[]>,
    required: true,
  },
  hasFormResponses: {
    type: Boolean,
    default: false,
  },
  user: {
    type: Object as PropType<Models.User>,
    required: true,
  },
});
</script>
