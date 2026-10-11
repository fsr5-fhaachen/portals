<template>
  <AppTable
    ref="table"
    :data="data"
    searchable
    :columns="getColumns()"
    :idFunction="(registration: any) => registration.id"
    :functions="getFunctions()"
    :rowClass="
      (registration: any) => {
        return {
          'bg-yellow-100 dark:bg-yellow-900':
            registration.queue_position && registration.queue_position > 0,
          'bg-red-100 dark:bg-red-900':
            registration.queue_position && registration.queue_position == -1,
          'bg-green-100 dark:bg-green-900': registration.fulfils_requirements,
        };
      }
    "
    :sortValues="getSortValues()"
  />
</template>

<script setup lang="ts">
import { ref, PropType } from "vue";
import { TableColumn } from "../../types/table-column";
import { ButtonState, TableStateButton } from "../../types/table-state-button";
import { TableFunction } from "../../types/table-function";
import { TableButton } from "../../types/table-button";
import { TableSortValue } from "../../types/table-sort-value";

const props = defineProps({
  // name of the infinite scroll prop with the registrations
  data: {
    type: String,
    default: "registrations",
  },
  courses: {
    type: Array as PropType<App.Models.Course[]>,
    required: true,
  },
  event: {
    type: Object as PropType<App.Models.Event>,
    required: true,
  },
  // whether any registration has form responses (column "Rückmeldung")
  hasFormResponses: {
    type: Boolean,
    default: false,
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

const table = ref<{
  patchRow: (id: number, fields: Record<string, any>) => void;
  removeRow: (id: number) => void;
} | null>(null);

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

    table.value?.patchRow(registrationId, { is_present: data.is_present });
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

    table.value?.patchRow(registrationId, {
      fulfils_requirements: data.fulfils_requirements,
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
    table.value?.removeRow(registrationId);
  }
};

function getColumns(): Array<TableColumn> {
  let columns = Array<TableColumn>();

  let firstNameCol = new TableColumn({
    name: "firstName",
    text: "Vorname",
    valueFunction: (registration) => registration.user.firstname,
    sortKey: "firstname",
  });
  columns.push(firstNameCol);

  let lastNameCol = new TableColumn({
    name: "lastName",
    text: "Nachname",
    valueFunction: (registration) => registration.user.lastname,
    sortKey: "lastname",
  });
  columns.push(lastNameCol);

  let courseCol = new TableColumn({
    name: "course",
    text: "Studiengang",
    valueFunction: (registration) => getCourseName(registration.user.course_id),
    sortKey: "course",
  });
  columns.push(courseCol);

  if (!props.hideSlots && props.event.type == "slot_booking") {
    let slotCol = new TableColumn({
      name: "slot",
      text: "Slot",
      valueFunction: (registration) => getSlotName(registration.slot_id),
      sortKey: "slot",
    });
    columns.push(slotCol);
  }

  if (!props.hideGroups && props.event.type == "group_phase") {
    let groupCol = new TableColumn({
      name: "group",
      text: "Gruppe",
      valueFunction: (registration) => getGroupName(registration.group_id),
      sortKey: "group",
    });
    columns.push(groupCol);
  }

  if (props.event.consider_alcohol) {
    let alcoholCol = new TableColumn({
      name: "alcohol",
      text: "Trinkt Alkohol",
      valueFunction: (registration) =>
        registration.drinks_alcohol ? "Ja" : "Nein",
      sortKey: "drinks_alcohol",
    });
    columns.push(alcoholCol);
  }

  if (props.event.type == "slot_booking") {
    let queueCol = new TableColumn({
      name: "queue",
      text: "Warteschlangenposition",
      valueFunction: (registration) => {
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
      sortKey: "queue_position",
    });
    columns.push(queueCol);
  }

  if (props.hasFormResponses) {
    let formCol = new TableColumn({
      name: "form",
      text: "Rückmeldung",
      valueFunction: (registration) => {
        if (registration.form_responses) {
          return (
            "<code>" +
            safeArrayToString(registration.form_responses) +
            "</code>"
          );
        }
        return "";
      },
      sortKey: "form_responses",
    });
    columns.push(formCol);
  }

  return columns;
}

function getFunctions(): Array<TableFunction> {
  let functions = new Array<TableFunction>();

  let presentButton = new TableStateButton({
    name: "isPresent",
    defaultState: new TableButton({
      name: "notPresent",
      text: "ist nicht anwesend",
      buttonFunction: (registration) => toggleIsPresent(registration.id),
      disabledFunction: () => false,
      theme: "gray",
    }),
    states: [
      new ButtonState({
        condition: (registration) => registration.is_present,
        button: new TableButton({
          name: "present",
          text: "ist anwesend",
          buttonFunction: (registration) => toggleIsPresent(registration.id),
        }),
      }),
    ],
  });
  functions.push(presentButton);

  if (
    props.user &&
    props.user.permissionsArray.includes("view hidden event details")
  ) {
    let requirementsButton = new TableStateButton({
      name: "fulfilsRequirements",
      defaultState: new TableButton({
        name: "doesNotFulFill",
        text: "erfüllt nicht die Anforderungen",
        buttonFunction: (registration) =>
          toggleFulfilsRequirements(registration.id),
        disabledFunction: () => false,
        theme: "gray",
      }),
      states: [
        new ButtonState({
          condition: (registration) => registration.fulfils_requirements,
          button: new TableButton({
            name: "fulfils",
            text: "erfüllt die Anforderungen",
            buttonFunction: (registration) =>
              toggleFulfilsRequirements(registration.id),
          }),
        }),
      ],
    });
    functions.push(requirementsButton);

    let deleteButton = new TableStateButton({
      name: "delete",
      defaultState: new TableButton({
        name: "doesNotFulFill",
        text: "löschen",
        buttonFunction: () => {},
        disabledFunction: () => true,
        theme: "gray",
      }),
      states: [
        new ButtonState({
          condition: (registration) => !registration.fulfils_requirements,
          button: new TableButton({
            name: "fulfils",
            text: "löschen",
            buttonFunction: (registration) => destroy(registration.id),
            disabledFunction: () => false,
            theme: "danger",
          }),
        }),
      ],
    });
    functions.push(deleteButton);
  }

  return functions;
}

function getSortValues() {
  let sortValues = new Array<TableSortValue>();

  let sortByPresent = new TableSortValue({
    name: "presence",
    text: "Anwesenheit",
    sortKey: "is_present",
  });

  sortValues.push(sortByPresent);

  if (
    props.user &&
    props.user.permissionsArray.includes("view hidden event details")
  ) {
    let sortByRequirements = new TableSortValue({
      name: "requirements",
      text: "Anforderungen",
      sortKey: "fulfils_requirements",
    });

    sortValues.push(sortByRequirements);
  }

  return sortValues;
}

function safeArrayToString(arr: any[]) {
  if (Array.isArray(arr)) {
    return arr
      .map((item) => {
        try {
          if (typeof item === "string") {
            return item;
          }
          if (item == null) {
            return "";
          }
          return JSON.stringify(item);
        } catch {
          return String(item);
        }
      })
      .join(", ");
  } else {
    try {
      if (typeof arr === "string") {
        return arr;
      }
      if (arr == null) {
        return "";
      }
      return JSON.stringify(arr);
    } catch {
      return String(arr);
    }
  }
}
</script>
