<template>
  <LayoutDashboardContent>
    <template #title>{{ event.name }}</template>

    <BoxContainer>
      <CourseBox v-for="course in courses" :course="course">
        <p class="text-2xl font-semibold text-gray-900">
          {{ getRegistrationsAmountByCourse(course) }}
        </p>
      </CourseBox>
    </BoxContainer>

    <div class="my-16">
      <AppButton
        theme="danger"
        @click="submit()"
        class="text-center text-2xl uppercase"
      >
        Event einteilen
      </AppButton>
    </div>

    <GroupTable
      v-if="event.type == 'group_phase' && event.groups"
      :courses="courses"
      :event="event"
      :groups="event.groups"
    />
    <SlotTable
      v-else-if="event.type == 'slot_booking' && event.slots"
      :slots="event.slots"
    />
    <RegistrationTable
      v-else-if="event.type == 'event_registration' && registrations"
      :courses="courses"
      :event="event"
      :registrations="registrations"
      :user="user"
    />
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { ref, PropType, onBeforeUnmount } from "vue";
import { Inertia } from "@inertiajs/inertia";

const { event } = defineProps({
  courses: {
    type: Array as PropType<App.Models.Course[]>,
    required: true,
  },
  event: {
    type: Object as PropType<App.Models.Event>,
    required: true,
  },
  user: {
    type: Object as PropType<App.Models.User>,
    required: true,
  },
});
const registrations = ref(event.registrations);
const fetchRegistrations = async () => {
  const response = await fetch("/api/events/" + event.id + "/registrations", {
    method: "GET",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
    },
  });

  if (response.ok) {
    const data = await response.json();
    registrations.value = data.registrations;
  }
};
const registrationsInterval = setInterval(fetchRegistrations, 1000);
onBeforeUnmount(() => {
  clearInterval(registrationsInterval);
});

const getRegistrationsAmountByCourse = (course: App.Models.Course) => {
  if (!registrations.value) return 0;

  return registrations.value.filter(
    (registration) => registration.user?.course_id == course.id
  ).length;
};

const submit = () => {
  Inertia.visit("/dashboard/admin/event/" + event.id + "/submit");
};
</script>
