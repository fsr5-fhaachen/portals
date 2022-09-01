<template>
  <CardBase>
    <UiH2>{{ event.name }}</UiH2>
    <dl class="grid grid-cols-1 sm:grid-cols-2 mt-2">
      <template v-if="event.registration_from">
        <dt class="text-gray-800">Anmeldung ab:</dt>
        <dd class="text-gray-900">
          <DateTimeString
            :value="event.registration_from"
            :withClockSuffix="true"
          />
        </dd>
      </template>

      <template v-if="event.registration_until">
        <dt class="text-gray-800">Anmeldung bis:</dt>
        <dd class="text-gray-900">
          <DateTimeString
            :value="event.registration_until"
            :withClockSuffix="true"
          />
        </dd>
      </template>
    </dl>

    <template #footer>
      <div class="-mt-px flex divide-x divide-gray-200">
        <template v-if="isRegistered">
          <div class="flex w-0 flex-1">
            <a
              :class="{
                'opacity-70 hover:cursor-not-allowed': isExpired,
                'hover:cursor-pointer': !isExpired,
              }"
              class="gap-2 group relative -mr-px inline-flex w-0 flex-1 items-center justify-center rounded-bl-lg border border-transparent py-4 text-sm font-medium text-gray-700"
            >
              <FontAwesomeIcon
                :class="{
                  'group-hover:text-red-700': !isExpired,
                }"
                class="h-5 w-5 text-gray-400"
                :icon="['fas', 'door-open']"
              />
              <span>Abmelden</span>
            </a>
          </div>
          <div class="-ml-px flex w-0 flex-1">
            <a
              class="hover:cursor-pointer gap-2 group relative inline-flex w-0 flex-1 items-center justify-center rounded-br-lg border border-transparent py-4 text-sm font-medium text-gray-700"
            >
              <FontAwesomeIcon
                class="h-5 w-5 text-gray-400 group-hover:text-blue-500"
                :icon="['fas', 'circle-info']"
              />
              <span>Info</span>
            </a>
          </div>
        </template>
        <template v-else>
          <div class="flex w-0 flex-1">
            <a
              :class="{
                'opacity-70 hover:cursor-not-allowed': !canRegister,
                'hover:cursor-pointer': canRegister,
              }"
              class="gap-2 group relative -mr-px inline-flex w-0 flex-1 items-center justify-center rounded-bl-lg border border-transparent py-4 text-sm font-medium text-gray-700"
            >
              <FontAwesomeIcon
                :class="{
                  'group-hover:text-green-700': canRegister,
                }"
                class="h-5 w-5 text-gray-400"
                :icon="['fas', 'play']"
              />
              <span>Anmelden</span>
            </a>
          </div>
        </template>
      </div>
    </template>
  </CardBase>
</template>

<script setup lang="ts">
import { computed, PropType } from "vue";
import DateTimeString from "../ui/DateTimeString.vue";

const { event } = defineProps({
  event: {
    type: Object as PropType<App.Models.Event>,
    required: true,
  },
});

const isExpired = computed(() => {
  return new Date(event.registration_until) < new Date();
});

const canRegister = computed(() => {
  const now = new Date();
  const registrationFrom = new Date(event.registration_from);
  const registrationUntil = new Date(event.registration_until);

  return now >= registrationFrom && now <= registrationUntil;
});

const isRegistered = computed(() => {
  return Math.random() > 0.5;
});
</script>
