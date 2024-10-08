<template>
  <LayoutDashboardContent>
    <template #title>{{ event.name }}</template>

    <BoxContainer class="mb-16">
      <CourseBox v-for="course in coursesData" :course="course">
        <p
          v-if="
            typeof course.users == 'number' ||
            (Array.isArray(course.users) && course.users.length)
          "
          class="text-2xl font-semibold text-gray-900 dark:text-gray-100"
        >
          {{
            typeof course.users == "number"
              ? course.users
              : course.users?.length
          }}
        </p>
        <div
          v-else
          class="h-8 w-10 animate-pulse rounded-lg bg-gray-200 dark:bg-gray-900"
        ></div>
      </CourseBox>
    </BoxContainer>

    <div
      v-if="['group_phase', 'slot_booking'].includes(event.type)"
      class="mb-16"
    >
      <AppButton
        theme="danger"
        @click="submit()"
        class="text-center text-2xl uppercase"
      >
        Event einteilen
      </AppButton>
    </div>

    <div class="mb-16">
      <AppButton @click="view()" class="text-center text-2xl uppercase">
        Registrierungen anzeigen
      </AppButton>
    </div>

    <GroupTable
      v-if="event.type == 'group_phase' && event.groups"
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

const { courses, event } = defineProps({
  courses: {
    type: Array as PropType<App.Models.Course[]>,
    required: true,
  },
  event: {
    type: Object as PropType<App.Models.Event>,
    required: true,
  },
  user: {
    type: Object as PropType<Models.User>,
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
// TODO: Optimize this
//const registrationsInterval = setInterval(fetchRegistrations, 2500);
// onBeforeUnmount(() => {
//   clearInterval(registrationsInterval);
// });

const coursesData = ref(courses);
const isCoursesFetching = ref(false);
const fetchCourses = async () => {
  if (isCoursesFetching.value) {
    return;
  }

  isCoursesFetching.value = true;

  const response = await fetch("/api/events/" + event.id + "/user-amount", {
    method: "GET",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
    },
  });

  if (response.ok) {
    const data = await response.json();

    // map the data to the courses
    coursesData.value = courses.map((course) => {
      const courseData = data.find(
        (courseData: any) => courseData.id == course.id,
      );

      return {
        ...course,
        users: courseData.amount,
      };
    });
  }

  isCoursesFetching.value = false;
};
const coursesInterval = setInterval(fetchCourses, 2500);
onBeforeUnmount(() => {
  clearInterval(coursesInterval);
});

const submit = () => {
  Inertia.visit("/dashboard/admin/event/" + event.id + "/submit");
};
const view = () => {
  Inertia.visit("/dashboard/admin/event/" + event.id + "/registrations");
};
</script>
