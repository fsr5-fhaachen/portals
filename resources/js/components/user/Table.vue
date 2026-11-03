<template>
  <AppTable
    ref="table"
    data="users"
    searchable
    :columns="getColumns()"
    :idFunction="(user) => user.id"
    :functions="getFunctions()"
    :rowClass="
      (user) => {
        return {
          'bg-yellow-100 dark:bg-yellow-900':
            user.roles.length &&
            user.roles.map((role) => role.name).includes('super admin'),
          'bg-red-100 dark:bg-red-900': user.is_disabled,
        };
      }
    "
  />

  <UserEditModal
    v-if="userToEdit"
    :user="userToEdit"
    :courses="courses"
    :roles="roles"
    @close="clearUserToEdit"
    @submit="submitUserEdit"
    @saved="refreshTable"
  />

  <UserDeleteModal
    v-if="userToDelete"
    :user="userToDelete"
    @close="clearUserToDelete"
    @submit="submitUserDelete"
    @saved="refreshTable"
  />

  <UserInfoModal
    v-if="userToView"
    :user="userToView"
    :courses="courses"
    :roles="roles"
    @close="clearUserToView"
  />
</template>

<script setup lang="ts">
import { ref, PropType } from "vue";
import { TableColumn } from "../../types/table-column";
import { TableFunction } from "../../types/table-function";
import { TableButton } from "../../types/table-button";
import { roleColors } from "../../composables/useRoleColors";

const props = defineProps({
  user: {
    type: Object as PropType<Models.User>,
    default: null,
  },
  courses: {
    type: Array as PropType<Models.Course[]>,
    required: true,
  },
  roles: {
    type: Array as PropType<Models.Role[]>,
    required: true,
  },
});

const table = ref<{ refresh: () => void } | null>(null);

const refreshTable = () => {
  table.value?.refresh();
};

const userToEdit = ref<Models.User | null>(null);
const userToDelete = ref<Models.User | null>(null);
const userToView = ref<Models.User | null>(null);

const clearUserToEdit = () => {
  userToEdit.value = null;
};
const selectUserToEdit = async (user: Models.User) => {
  userToEdit.value = user;
};
const submitUserEdit = async () => {
  clearUserToEdit();
};

const clearUserToDelete = () => {
  userToDelete.value = null;
};
const selectUserToDelete = async (user: Models.User) => {
  userToDelete.value = user;
};
const submitUserDelete = async () => {
  clearUserToDelete();
};

const clearUserToView = () => {
  userToView.value = null;
};
const selectUserToView = async (user: Models.User) => {
  userToView.value = user;
};

function getColumns(): Array<TableColumn> {
  let columns: Array<TableColumn> = [];

  let firstNameCol = new TableColumn({
    name: "firstName",
    text: "Vorname",
    valueFunction: (user) => user.firstname,
    sortKey: "firstname",
  });
  columns.push(firstNameCol);

  let lastNameCol = new TableColumn({
    name: "lastName",
    text: "Nachname",
    valueFunction: (user) => user.lastname,
    sortKey: "lastname",
  });
  columns.push(lastNameCol);

  let emailCol = new TableColumn({
    name: "email",
    text: "E-Mail",
    valueFunction: (user) => user.email,
    sortKey: "email",
  });
  columns.push(emailCol);

  let courseCol = new TableColumn({
    name: "course",
    text: "Studiengang",
    valueFunction: (user) => {
      if (user.course?.id) {
        return (
          '<span class="' +
          user.course.classes +
          ', rounded-md p-1 text-xs text-white">' +
          user.course.abbreviation +
          "</span>"
        );
      }
      return "";
    },
    sortKey: "course",
  });
  columns.push(courseCol);

  let roleCol = new TableColumn({
    name: "role",
    text: "Rollen",
    valueFunction: (user) => {
      let ret = "";
      if (user.roles.length) {
        ret += '<div class="flex flex-col gap-2">';
        for (const role of [...user.roles].sort((a, b) =>
          a.name.localeCompare(b.name),
        )) {
          const color = roleColors[role.name] ?? "bg-slate-500";

          ret +=
            '<span class="rounded-md ' +
            color +
            ' text-xs text-white text-center">' +
            role.name +
            "</span>";
        }
      }
      return ret;
    },
    sortKey: "roles",
  });
  columns.push(roleCol);

  let avatarCol = new TableColumn({
    name: "avatar",
    text: "Hat ein Bild",
    valueFunction: (user) => (user.avatarUrl ? "Ja" : "Nein"),
    sortKey: "avatar",
  });
  columns.push(avatarCol);

  return columns;
}

function getFunctions(): Array<TableFunction> {
  let functions: Array<TableFunction> = [];

  const viewInfoButton = new TableButton({
    name: "view",
    text: "Info",
    buttonFunction: (user) => selectUserToView(user),
    disabledFunction: () => false,
    theme: "default",
  });
  functions.push(viewInfoButton);

  const editButton = new TableButton({
    name: "edit",
    text: "bearbeiten",
    buttonFunction: (user) => selectUserToEdit(user),
    disabledFunction: () => false,
    theme: "warning",
  });
  functions.push(editButton);

  if (props.user.permissionsArray.includes("delete users")) {
    const deleteButton = new TableButton({
      name: "delete",
      text: "löschen",
      buttonFunction: (user) => selectUserToDelete(user),
      disabledFunction: (userData) =>
        props.user.id === userData.id ||
        userData.roles.map((role) => role.name).includes("super admin"),
      theme: "danger",
    });
    functions.push(deleteButton);
  }

  return functions;
}
</script>
