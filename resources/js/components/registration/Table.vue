<template>
  <AppTable
    :columns="getColumns()"
    :elements="registrationsData"
    :idFunction="(registration) => registration.id"
    :functions="getFunctions()"
    :rowClass="
      (registration) => {
        return {
          'bg-yellow-100 dark:bg-yellow-900':
            registration.queue_position && registration.queue_position > 0,
          'bg-red-100 dark:bg-red-900':
            registration.queue_position && registration.queue_position == -1,
          'bg-green-100 dark:bg-green-900': registration.fulfils_requirements,
        };
      }
    "
  />
</template>

<script setup lang="ts">
import { computed, ref, PropType, watch } from "vue";
import { TableColumn } from "../../types/table-column";
import { ButtonState, TableStateButton } from "../../types/table-state-button";
import { TableFunction } from "../../types/table-function";
import { TableButton } from "../../types/table-button";

const props = defineProps({
  courses: {
    type: Array as PropType<App.Models.Course[]>,
    required: true,
  },
  event: {
    type: Object as PropType<App.Models.Event>,
    required: true,
  },
  registrations: {
    type: Array as PropType<App.Models.Registration[]>,
    required: true,
  },
  hideGroups: {
    type: Boolean,
    default: false,
  },
  hideSlots: {
    type: Boolean,
    default: false,
  },
  user: {
    type: Object as PropType<Models.User>,
    default: null,
  },
});

const getCourseById = (id: number) => {
  return props.courses.find((course) => course.id === id);
};

const getCourseName = (id: number) => {
  const course = getCourseById(id);
  return course !== undefined && course !== null ? course.name : "";
};

const getSlotById = (id: number) => {
  if (!props.event.slots) return null;
  return props.event.slots.find((slot) => slot.id === id);
};

const getSlotName = (id: number) => {
  const slot = getSlotById(id);
  return slot !== undefined && slot !== null ? slot.name : "";
};

const getGroupById = (id: number) => {
  if (!props.event.groups) return null;
  return props.event.groups.find((group) => group.id === id);
};

const getGroupName = (id: number) => {
  const group = getGroupById(id);
  return group !== undefined && group !== null ? group.name : "";
};

const registrationsData = ref(props.registrations);
watch(props, (props) => {
  registrationsData.value = props.registrations;
});

const showFormColomn = computed(() => {
  // check if any registrationsData has the attribute "form_responses"
  return registrationsData.value.some((registration) => {
    return registration.form_responses;
  });
});

const toggleIsPresent = async (registrationId: number) => {
  const response = await fetch(
    "/api/registrations/" + registrationId + "/toggle-is-present",
    {
      method: "GET",
      credentials: "include",
      headers: {
        "Content-Type": "application/json",
      },
    },
  );

  if (response.ok) {
    const data = await response.json();

    registrationsData.value = registrationsData.value.map((registration) => {
      if (registration.id === registrationId) {
        registration.is_present = data.is_present;
      }
      return registration;
    });
  }
};
const toggleFulfilsRequirements = async (registrationId: number) => {
  const response = await fetch(
    "/api/registrations/" + registrationId + "/toggle-fulfils-requirements",
    {
      method: "GET",
      credentials: "include",
      headers: {
        "Content-Type": "application/json",
      },
    },
  );

  if (response.ok) {
    const data = await response.json();

    registrationsData.value = registrationsData.value.map((registration) => {
      if (registration.id === registrationId) {
        registration.fulfils_requirements = data.fulfils_requirements;
      }
      return registration;
    });
  }
};

const destroy = async (registrationId: number) => {
  const response = await fetch("/api/registrations/" + registrationId, {
    method: "DELETE",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
      "X-CSRF-TOKEN":
        document
          .querySelector("meta[name='csrf-token']")
          ?.getAttribute("content") || "",
    },
  });

  if (response.ok) {
    registrationsData.value = registrationsData.value.filter(
      (registration) => registration.id !== registrationId,
    );
  }
};

function getColumns(): Array<TableColumn> {
  let columns = Array<TableColumn>();

  let firstNameCol = new TableColumn(
    "firstName",
    "Vorname",
    (registration) => registration.user.firstname,
    (registration1, registration2) =>
      registration1.user.firstname.localeCompare(registration2.user.firstname),
  );
  columns.push(firstNameCol);

  let lastNameCol = new TableColumn(
    "lastName",
    "Nachname",
    (registration) => registration.user.lastname,
    (registration1, registration2) =>
      registration1.user.lastname.localeCompare(registration2.user.lastname),
  );
  columns.push(lastNameCol);

  let courseCol = new TableColumn(
    "course",
    "Studiengang",
    (registration) => getCourseName(registration.user.course_id),
    (registration1, registration2) =>
      getCourseName(registration1.user.course_id).localeCompare(
        getCourseName(registration2.user.course_id),
      ),
  );
  columns.push(courseCol);

  if (!props.hideSlots && props.event.type == "slot_booking") {
    let slotCol = new TableColumn(
      "slot",
      "Slot",
      (registration) => getSlotName(registration.user.course_id),
      (registration1, registration2) =>
        getSlotName(registration1.user.course_id).localeCompare(
          getSlotName(registration2.user.course_id),
        ),
    );
    columns.push(slotCol);
  }

  if (!props.hideGroups && props.event.type == "group_phase") {
    let groupCol = new TableColumn(
      "group",
      "Gruppe",
      (registration) => getGroupName(registration.user.course_id),
      (registration1, registration2) =>
        getGroupName(registration1.user.course_id).localeCompare(
          getGroupName(registration2.user.course_id),
        ),
    );
    columns.push(groupCol);
  }

  if (props.event.consider_alcohol) {
    let alcoholCol = new TableColumn(
      "alcohol",
      "Trinkt Alkohol",
      (registration) => (registration.drinks_alcohol ? "Ja" : "Nein"),
      (registration1, registration2) =>
        registration1.drinks_alcohol === registration2.drinks_alcohol
          ? 0
          : registration1.drinks_alcohol
            ? 1
            : -1,
    );
    columns.push(alcoholCol);
  }

  if (props.event.type == "slot_booking") {
    let queueCol = new TableColumn(
      "queue",
      "Warteschlangenpositiion",
      (registration) => {
        if (registration.queue_position && registration.queue_position > 0) {
          return registration.queue_position;
        } else if (
          registration.queue_position &&
          registration.queue_position == -1
        ) {
          return "Wartet auf Zuteilung";
        } else {
          return "Angemeldet";
        }
      },
      (registration1, registration2) =>
        registration1.queue_position === null ||
        registration1.queue_position === undefined
          ? -1
          : registration1.queue_position - registration2.queue_position,
    );
    columns.push(queueCol);
  }

  if (showFormColomn) {
    let formCol = new TableColumn(
      "form",
      "Rückmeldung",
      (registration) => {
        if (registration.form_responses) {
          return "<code>" + registration.form_responses + "</code>";
        }

        return "";
      },
      (registration1, registration2) =>
        registration1.form_responses === null ||
        registration1.form_responses === undefined
          ? -1
          : registration1.form_responses.localeCompare(
              registration2.form_responses,
            ),
    );
    columns.push(formCol);
  }

  return columns;
}

function getFunctions(): Array<TableFunction> {
  let functions = new Array<TableFunction>();

  let presentButton = new TableStateButton(
    "isPresent",
    new TableButton(
      "notPresent",
      "ist nicht anwesend",
      (registration) => toggleIsPresent(registration.id),
      () => false,
      "gray",
    ),
    [
      new ButtonState(
        (registration) => registration.is_present,
        new TableButton("present", "ist anwesend", (registration) =>
          toggleIsPresent(registration.id),
        ),
      ),
    ],
  );
  functions.push(presentButton);

  if (
    props.user &&
    props.user.permissionsArray.includes("view hidden event details")
  ) {
    let requirementsButton = new TableStateButton(
      "fulfilsRequirements",
      new TableButton(
        "doesNotFulFill",
        "erfüllt nicht die Anforderungen",
        (registration) => toggleFulfilsRequirements(registration.id),
        () => false,
        "gray",
      ),
      [
        new ButtonState(
          (registration) => registration.fulfils_requirements,
          new TableButton(
            "fulfils",
            "erfüllt die Anforderungen",
            (registration) => toggleFulfilsRequirements(registration.id),
          ),
        ),
      ],
    );
    functions.push(requirementsButton);

    let deleteButton = new TableStateButton(
      "delete",
      new TableButton(
        "doesNotFulFill",
        "löschen",
        () => {},
        () => true,
        "gray",
      ),
      [
        new ButtonState(
          (registration) => !registration.fulfils_requirements,
          new TableButton(
            "fulfils",
            "löschen",
            (registration) => destroy(registration.id),
            () => false,
            "danger",
          ),
        ),
      ],
    );
    functions.push(deleteButton);
  }

  return functions;
}
</script>
