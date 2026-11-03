<template>
  <LayoutDashboardContent>
    <template #title>Strichliste</template>
    <template v-if="!isDisabled && !hasPin" #subtitle>
      Lege zuerst eine PIN fest.
    </template>

    <CardContainer>
      <UiMessage
        v-for="error in Object.values(errors)"
        :key="error"
        type="error"
        :message="error"
      />

      <UiMessage v-if="isDisabled" type="error">
        <template #message>
          Dein Account ist für die Strichliste gesperrt. Bitte wende dich an die
          Orga.
        </template>
      </UiMessage>

      <template v-else-if="!hasPin">
        <CardBase>
          <p class="mb-6 text-gray-500 dark:text-gray-400">
            Mit deiner PIN schützt du dein Konto am Kiosk (Tablet). Dort musst
            du sie jedes Mal eingeben, bevor du Striche machen kannst.
          </p>
          <StrichlistensystemPinForm submitLabel="PIN speichern" />
        </CardBase>
      </template>

      <template v-else>
        <StrichlistensystemBalanceCard :balance="balance" />

        <GridContainer>
          <StrichlistensystemArticleCard
            v-for="article in articles"
            :key="article.id"
            :article="article"
            :limitLabel="`pro ${limitWindow} Sekunden`"
            @buy="buy"
            @limitReset="reloadArticles"
          />
        </GridContainer>

        <StrichlistensystemActionLogList
          :articleActionLogs="articleActionLogs"
          @cancel="cancel"
        />

        <CardBase>
          <UiH2 class="mb-6">PIN ändern</UiH2>
          <StrichlistensystemPinForm submitLabel="PIN ändern" />
        </CardBase>
      </template>
    </CardContainer>
  </LayoutDashboardContent>
</template>

<script lang="ts">
import DashboardLayout from "@/layouts/DashboardLayout.vue";

export default {
  layout: DashboardLayout,
};
</script>

<script setup lang="ts">
import { PropType } from "vue";
import { router } from "@inertiajs/vue3";

defineProps({
  errors: {
    type: Object as PropType<Record<string, string>>,
    default: () => ({}),
  },
  hasPin: {
    type: Boolean,
    required: true,
  },
  isDisabled: {
    type: Boolean,
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
  limitWindow: {
    type: Number,
    required: true,
  },
});

// the article limit applies per time window, reload the remaining amounts after the window passed
const reloadArticles = () => {
  router.reload({ only: ["articles"] });
};

const buy = (article: App.Models.Article, amount: number) => {
  router.post(
    `/strichlistensystem/buy/${article.id}`,
    { amount, price: article.price },
    { preserveScroll: true, preserveState: true },
  );
};

const cancel = (articleActionLog: App.Models.ArticleActionLog) => {
  router.post(
    `/strichlistensystem/cancel/${articleActionLog.id}`,
    {},
    { preserveScroll: true, preserveState: true },
  );
};
</script>
