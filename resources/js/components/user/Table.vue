<template>
  <AppTable
    :columns="getColumns()"
    :elements="usersData"
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
  />

  <UserDeleteModal
    v-if="userToDelete"
    :user="userToDelete"
    @close="clearUserToDelete"
    @submit="submitUserDelete"
  />
</template>

<script setup lang="ts">
import { ref, PropType, watch } from "vue";
import { TableColumn } from "../../types/table-column";
import { TableFunction } from "../../types/table-function";
import { TableButton } from "../../types/table-button";

const props = defineProps({
  user: {
    type: Object as PropType<Models.User>,
    default: null,
  },
  users: {
    type: Array as PropType<Models.User[]>,
    required: true,
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

const usersData = ref(props.users);
watch(props, (props) => {
  usersData.value = props.users;
});

const userToEdit = ref<Models.User | null>(null);
const userToDelete = ref<Models.User | null>(null);

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

function getColumns(): Array<TableColumn> {
  let columns = Array<TableColumn>();

  let firstNameCol = new TableColumn(
    "firstName",
    "Vorname",
    (user) => user.firstname,
    (user1, user2) => user1.firstname.localeCompare(user2.firstname),
  );
  columns.push(firstNameCol);

  let lastNameCol = new TableColumn(
    "lastName",
    "Nachname",
    (user) => user.lastname,
    (user1, user2) => user1.lastname.localeCompare(user2.lastname),
  );
  columns.push(lastNameCol);

  let emailCol = new TableColumn(
    "email",
    "E-Mail",
    (user) => user.email,
    (user1, user2) => user1.email.localeCompare(user2.email),
  );
  columns.push(emailCol);

  let courseCol = new TableColumn(
    "course",
    "Studiengang",
    (user) => {
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
    (user1, user2) => {
      if (!user1.course?.id) {
        return -1;
      } else if (!user2.course?.id) {
        return 1;
      } else {
        return user1.course.abbreviation.localeCompare(
          user2.course.abbreviation,
        );
      }
    },
  );
  columns.push(courseCol);

  let roleCol = new TableColumn(
    "role",
    "Rollen",
    (user) => {
      let ret = "";
      if (user.roles.length) {
        ret += '<div class="flex flex-col gap-2">';
        for (const role of user.roles) {
          ret +=
            '<span class="rounded-md bg-slate-900 p-1 text-xs text-white">' +
            role.name +
            "</span>";
        }
      }

      return ret;
    },
    (user1, user2) => {
      if (!user1.roles.length) {
        return -1;
      } else if (!user2.roles.length) {
        return 1;
      } else {
        return user1.roles
          .map((role) => role.name)
          .join("")
          .localeCompare(user2.roles.map((role) => role.name).join(""));
      }
    },
  );
  columns.push(roleCol);

  let avatarCol = new TableColumn(
    "avatar",
    "Hat ein Bild",
    (user) => (user.avatarUrl ? "Ja" : "Nein"),
    (user1, user2) =>
      user1.avatarUrl && user2.avatarUrl ? 0 : user1.avatarUrl ? 1 : -1,
  );
  columns.push(avatarCol);

  return columns;
}

function getFunctions(): Array<TableFunction> {
  let functions = new Array<TableFunction>();

  const editButton = new TableButton(
    "edit",
    "bearbeiten",
    (user) => selectUserToEdit(user),
    () => false,
    "warning",
  );
  functions.push(editButton);

  if (props.user.permissionsArray.includes("delete users")) {
    const deleteButton = new TableButton(
      "delete",
      "löschen",
      (user) => selectUserToDelete(user),
      (userData) =>
        props.user.id === userData.id ||
        userData.roles.map((role) => role.name).includes("super admin"),
      "danger",
    );
    functions.push(deleteButton);
  }

  return functions;
}
</script>
