<template>
  <LayoutDashboardContent>
    <template #title>Statistik</template>

    <BoxContainer>
      <CourseBox v-for="course in coursesData" :course="course">
        <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
          {{
            typeof course.users == "number"
              ? course.users
              : course.users?.length
          }}
        </p>
      </CourseBox>
    </BoxContainer>
    <br />
    <div
      class="flex bg-white dark:bg-gray-800 mb-16 pb-5 justify-center rounded-lg flex-row flex-wrap flex-grow"
    >
      <ChartContainer :stats="totalUser" chartType="total" />
    </div>
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { ref, PropType, onBeforeUnmount } from "vue";

const { courses, totalUser } = defineProps({
  courses: {
    type: Array as PropType<App.Models.Course[]>,
    required: true,
  },
  totalUser: {
    type: Number,
    required: false,
    default: 0,
  },
});

const coursesData = ref(courses);
const isFetchingCourses = ref(false);
const fetchCourses = async () => {
  if (isFetchingCourses.value) {
    return;
  }

  isFetchingCourses.value = true;

  const response = await fetch("/api/courses/user-amount", {
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

  isFetchingCourses.value = false;
};
const coursesInterval = setInterval(fetchCourses, 2500);
onBeforeUnmount(() => {
  clearInterval(coursesInterval);
});
</script>
