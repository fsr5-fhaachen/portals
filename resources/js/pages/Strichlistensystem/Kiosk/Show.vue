<template>
  <LayoutDashboardContent>
    <template #title>Hallo {{ person.firstname }},</template>
    <!-- the kiosk has no navbar, so the color mode is switched here -->
    <template #actions>
      <ColorModeButton class="p-2 text-2xl" />
    </template>
    <template #subtitle>
      du wirst in {{ redirectCountdown }}
      {{ redirectCountdown === 1 ? "Sekunde" : "Sekunden" }} automatisch
      abgemeldet.
    </template>

    <CardContainer>
      <AppMessage :message="message" class="!mb-0" />
      <UiMessage v-if="errors.amount" type="error" :message="errors.amount" />

      <div class="flex flex-wrap gap-3">
        <AppButton theme="gray" @click="logout">Fertig & abmelden</AppButton>
      </div>

      <StrichlistensystemBalanceCard
        :balance="balance"
        :loginTotal="loginTotal"
      />

      <div class="grid grid-cols-1 gap-6 md:grid-cols-4">
        <StrichlistensystemPersonCard :person="person" class="md:col-span-1" />

        <div
          class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:col-span-3 lg:grid-cols-3"
        >
          <StrichlistensystemArticleCard
            v-for="article in articles"
            :key="article.id"
            :article="article"
            limitLabel="pro Anmeldung"
            @buy="buy"
          />
        </div>
      </div>

      <StrichlistensystemActionLogList
        :articleActionLogs="articleActionLogs"
        @cancel="cancel"
      />
    </CardContainer>
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { onBeforeUnmount, PropType, ref } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
  message: {
    type: Object,
    default: () => ({}),
  },
  errors: {
    type: Object as PropType<Record<string, string>>,
    default: () => ({}),
  },
  person: {
    type: Object as PropType<Strichlistensystem.Person>,
    required: true,
  },
  articles: {
    type: Array as PropType<App.Models.Article[]>,
    required: true,
  },
  articleActionLogs: {
    type: Array as PropType<App.Models.ArticleActionLog[]>,
    required: true,
  },
  balance: {
    type: Object as PropType<Strichlistensystem.Balance>,
    required: true,
  },
  loginTotal: {
    type: Number,
    required: true,
  },
  sessionTimeout: {
    type: Number,
    required: true,
  },
});

// seconds until the person gets logged out automatically (after an action the countdown is reset)
const countdownSeconds = Math.min(40, props.sessionTimeout);
const countdownSecondsAfterAction = Math.min(30, props.sessionTimeout);

const redirectCountdown = ref(countdownSeconds);

const logout = () => {
  router.post("/strichlistensystem/kiosk/logout");
};

const buy = (article: App.Models.Article, amount: number) => {
  redirectCountdown.value = countdownSecondsAfterAction;
  router.post(
    `/strichlistensystem/kiosk/user/${props.person.id}/buy/${article.id}`,
    { amount, price: article.price },
    { preserveScroll: true, preserveState: true },
  );
};

const cancel = (articleActionLog: App.Models.ArticleActionLog) => {
  redirectCountdown.value = countdownSecondsAfterAction;
  router.post(
    `/strichlistensystem/kiosk/user/${props.person.id}/cancel/${articleActionLog.id}`,
    {},
    { preserveScroll: true, preserveState: true },
  );
};

const countdownInterval = setInterval(() => {
  if (redirectCountdown.value > 1) {
    redirectCountdown.value--;
    return;
  }

  clearInterval(countdownInterval);
  logout();
}, 1000);

onBeforeUnmount(() => {
  clearInterval(countdownInterval);
});
</script>
