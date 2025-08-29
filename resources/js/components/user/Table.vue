<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <div class="flex flex-col">
      <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle">
          <div class="shadow-sm ring-1 ring-black ring-opacity-5">
            <table class="min-w-full border-separate" style="border-spacing: 0">
              <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                  <th
                    scope="col"
                    class="dark:bg-border-gray-700 border-b border-gray-300 bg-gray-50 bg-opacity-75 py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter dark:bg-gray-900 dark:text-gray-300 sm:pl-6 lg:pl-8"
                  >
                    Vorname
                  </th>
                  <th
                    scope="col"
                    class="dark:bg-border-gray-700 border-b border-gray-300 bg-gray-50 bg-opacity-75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter dark:bg-gray-900 dark:text-gray-300"
                  >
                    Nachname
                  </th>
                  <th
                    scope="col"
                    class="dark:bg-border-gray-700 border-b border-gray-300 bg-gray-50 bg-opacity-75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter dark:bg-gray-900 dark:text-gray-300"
                  >
                    E-Mail
                  </th>
                  <th
                    scope="col"
                    class="dark:bg-border-gray-700 border-b border-gray-300 bg-gray-50 bg-opacity-75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter dark:bg-gray-900 dark:text-gray-300"
                  >
                    Studiengang
                  </th>
                  <th
                    scope="col"
                    class="dark:bg-border-gray-700 border-b border-gray-300 bg-gray-50 bg-opacity-75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter dark:bg-gray-900 dark:text-gray-300"
                  >
                    Rollen
                  </th>
                  <th
                    scope="col"
                    class="dark:bg-border-gray-700 border-b border-gray-300 bg-gray-50 bg-opacity-75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter dark:bg-gray-900 dark:text-gray-300"
                  >
                    Hat ein Bild
                  </th>
                  <th
                    scope="col"
                    class="dark:bg-border-gray-700 border-b border-gray-300 bg-gray-50 bg-opacity-75 py-3.5 pl-3 pr-4 backdrop-blur backdrop-filter dark:bg-gray-900 dark:text-gray-300 sm:pr-6 lg:pr-8"
                  >
                    <span class="sr-only">Aktionen</span>
                  </th>
                </tr>
              </thead>
              <tbody v-if="users" class="bg-white dark:bg-gray-800">
                <template v-for="(userData, index) in users" :key="userData.id">
                  <tr
                    v-if="userData"
                    :class="{
                      'bg-yellow-100 dark:bg-yellow-900':
                        userData.roles.length &&
                        userData.roles
                          .map((role) => role.name)
                          .includes('super admin'),
                      'bg-red-100 dark:bg-red-900': userData.is_disabled,
                    }"
                  >
                    <td
                      :class="[
                        index !== users.length - 1
                          ? 'border-b border-gray-200 dark:border-gray-700'
                          : '',
                        'whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 dark:text-gray-100 sm:pl-6 lg:pl-8',
                      ]"
                    >
                      {{ userData.firstname }}
                    </td>
                    <td
                      :class="[
                        index !== users.length - 1
                          ? 'border-b border-gray-200 dark:border-gray-700'
                          : '',
                        'whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900 dark:text-gray-100',
                      ]"
                    >
                      {{ userData.lastname }}
                    </td>
                    <td
                      :class="[
                        index !== users.length - 1
                          ? 'border-b border-gray-200 dark:border-gray-700'
                          : '',
                        'whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900 dark:text-gray-100',
                      ]"
                    >
                      {{ userData.email }}
                    </td>
                    <td
                      :class="[
                        index !== users.length - 1
                          ? 'border-b border-gray-200 dark:border-gray-700'
                          : '',
                        'whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300',
                      ]"
                    >
                      <span
                        v-if="userData.course?.id"
                        :class="[
                          userData.course.classes,
                          'rounded-md p-1 text-xs text-white',
                        ]"
                      >
                        {{ userData.course.abbreviation }}
                      </span>
                    </td>
                    <td
                      :class="[
                        index !== users.length - 1
                          ? 'border-b border-gray-200 dark:border-gray-700'
                          : '',
                        'whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300',
                      ]"
                    >
                      <div
                        v-if="userData.roles.length"
                        class="flex flex-col gap-2"
                      >
                        <div v-for="role in userData.roles" :key="role.id">
                          <span
                            class="rounded-md bg-slate-900 p-1 text-xs text-white"
                          >
                            {{ role.name }}
                          </span>
                        </div>
                      </div>
                    </td>
                    <td
                      :class="[
                        index !== users.length - 1
                          ? 'border-b border-gray-200 dark:border-gray-700'
                          : '',
                        'whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900 dark:text-gray-100',
                      ]"
                    >
                      {{ userData.avatarUrl ? "Ja" : "Nein" }}
                    </td>
                    <td
                      :class="[
                        index !== users.length - 1
                          ? 'border-b border-gray-200 dark:border-gray-700'
                          : '',
                        'relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 lg:pr-8',
                      ]"
                    >
                      <div class="flex gap-4">
                        <div>
                          <AppButton
                            theme="warning"
                            @click="selectUserToEdit(userData)"
                          >
                            <span class="sr-only">
                              {{ userData.firstname }}
                              {{ userData.lastname }}
                            </span>
                            bearbeiten
                          </AppButton>
                        </div>
                        <div
                          v-if="
                            user.permissionsArray.includes('delete users') &&
                            user.id !== userData.id &&
                            !userData.roles
                              .map((role) => role.name)
                              .includes('super admin')
                          "
                        >
                          <AppButton
                            theme="danger"
                            @click="selectUserToDelete(userData)"
                          >
                            <span class="sr-only">
                              {{ userData.firstname }}
                              {{ userData.lastname }}
                            </span>
                            löschen
                          </AppButton>
                        </div>
                      </div>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

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
  </div>
</template>

<script setup lang="ts">
import { ref, PropType } from "vue";
import { TableColumn } from "../../types/table-column";
import { ButtonState, TableStateButton } from "../../types/table-state-button";
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
          '<span class ="[' +
          user.course.classes +
          ', "rounded-md p-1 text-xs text-white"]">' +
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
          .join(" | ")
          .localeCompare(user2.roles.map((role) => role.name).join(" | "));
      }
    },
  );
  columns.push(roleCol);

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
      false,
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
        false,
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
        (registration) => {},
        true,
        "gray",
      ),
      [
        new ButtonState(
          (registration) => !registration.fulfils_requirements,
          new TableButton(
            "fulfils",
            "löschen",
            (registration) => destroy(registration.id),
            false,
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
