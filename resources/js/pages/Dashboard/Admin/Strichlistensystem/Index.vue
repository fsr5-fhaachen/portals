<template>
  <LayoutDashboardContent>
    <template #title>Strichliste</template>

    <CardContainer>
      <UiMessage
        v-for="error in Object.values(errors)"
        :key="error"
        type="error"
        :message="error"
      />

      <CardBase>
        <div
          class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
          <div>
            <UiH2>Export</UiH2>
            <p class="text-gray-500 dark:text-gray-400">
              Anzahl der gebuchten Artikel (ohne Stornierungen) pro Person als
              CSV.
            </p>
          </div>
          <AppButton @click="downloadExport">Export herunterladen</AppButton>
        </div>
      </CardBase>

      <CardBase>
        <FormKit
          type="form"
          id="article"
          @submit="submitArticleHandler"
          :actions="false"
          v-model="articleForm"
        >
          <FormContainer>
            <FormRow>
              <UiH2>
                {{
                  articleToEdit
                    ? `Artikel „${articleToEdit.name}“ bearbeiten`
                    : "Artikel erstellen"
                }}
              </UiH2>
            </FormRow>
            <FormRow>
              <FormKit
                type="text"
                name="name"
                label="Name"
                placeholder="Bier"
                validation="required|length:1,50"
              />
              <FormKit
                type="select"
                name="icon"
                label="Icon"
                placeholder="Wähle ein Icon aus"
                validation="required"
                :options="icons"
              />
              <div
                class="flex h-16 w-16 flex-shrink-0 items-center justify-center self-end rounded-md bg-fhac-mint-dark text-2xl text-white"
              >
                <FontAwesomeIcon
                  v-if="articleForm.icon"
                  :icon="['fas', articleForm.icon]"
                />
              </div>
            </FormRow>
            <FormRow>
              <FormKit
                type="number"
                name="show_order"
                label="Reihenfolge"
                help="Artikel werden aufsteigend sortiert angezeigt."
                number="integer"
                validation="required|min:0|max:10000"
              />
              <FormKit
                type="number"
                name="max_order_amount"
                label="Maximale Anzahl"
                :help="`Am Kiosk pro Anmeldung, im Selbstzugriff pro ${limitWindow} Sekunden. Stornierte Buchungen zählen nicht.`"
                number="integer"
                validation="required|min:1|max:100"
              />
            </FormRow>
            <FormRow>
              <FormKit
                type="checkbox"
                name="is_active"
                label="Aktiv"
                help="Deaktivierte Artikel werden nicht mehr angezeigt und können nicht mehr gebucht werden. Bisherige Buchungen bleiben erhalten."
              />
            </FormRow>
            <FormRow>
              <FormKit
                type="submit"
                :label="
                  articleToEdit ? 'Artikel speichern' : 'Artikel erstellen'
                "
              />
              <FormKit
                v-if="articleToEdit"
                type="button"
                label="Abbrechen"
                @click="resetArticleForm"
              />
            </FormRow>
          </FormContainer>
        </FormKit>
      </CardBase>

      <AppTable
        data="articles"
        :columns="getColumns()"
        :idFunction="(article) => article.id"
        :functions="getFunctions()"
        :rowClass="
          (article) => ({
            'bg-gray-100 text-gray-500 dark:bg-gray-900': !article.is_active,
          })
        "
      />
    </CardContainer>
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { PropType, ref } from "vue";
import { router } from "@inertiajs/vue3";
import { TableColumn } from "../../../../types/table-column";
import { TableFunction } from "../../../../types/table-function";
import { TableButton } from "../../../../types/table-button";
import {
  ButtonState,
  TableStateButton,
} from "../../../../types/table-state-button";

defineProps({
  errors: {
    type: Object as PropType<Record<string, string>>,
    default: () => ({}),
  },
  icons: {
    type: Array as PropType<string[]>,
    required: true,
  },
  limitWindow: {
    type: Number,
    required: true,
  },
});

type ArticleForm = {
  name: string;
  icon: string;
  show_order: number;
  max_order_amount: number;
  is_active: boolean;
};

const emptyArticleForm = (): ArticleForm => ({
  name: "",
  icon: "",
  show_order: 0,
  max_order_amount: 1,
  is_active: true,
});

const articleForm = ref<ArticleForm>(emptyArticleForm());
const articleToEdit = ref<App.Models.Article | null>(null);

const resetArticleForm = () => {
  articleToEdit.value = null;
  articleForm.value = emptyArticleForm();
};

const editArticle = (article: App.Models.Article) => {
  articleToEdit.value = article;
  articleForm.value = {
    name: article.name,
    icon: article.icon,
    show_order: article.show_order,
    max_order_amount: article.max_order_amount,
    is_active: article.is_active,
  };
  window.scrollTo({ top: 0, behavior: "smooth" });
};

const submitArticleHandler = () => {
  const url = articleToEdit.value
    ? `/dashboard/admin/strichlistensystem/article/${articleToEdit.value.id}`
    : "/dashboard/admin/strichlistensystem/article";

  router.post(url, articleForm.value, {
    preserveScroll: true,
    preserveState: true,
    reset: ["articles"],
    onSuccess: () => {
      resetArticleForm();
    },
  });
};

const toggleArticleActive = (article: App.Models.Article) => {
  router.post(
    `/dashboard/admin/strichlistensystem/article/${article.id}/toggle-active`,
    {},
    {
      preserveScroll: true,
      preserveState: true,
      reset: ["articles"],
      onSuccess: () => {
        // keep the form in sync if the toggled article is currently edited
        if (articleToEdit.value?.id === article.id) {
          articleForm.value.is_active = !article.is_active;
        }
      },
    },
  );
};

const deleteArticle = (article: App.Models.Article) => {
  if (!window.confirm(`Soll der Artikel „${article.name}“ gelöscht werden?`)) {
    return;
  }

  router.delete(`/dashboard/admin/strichlistensystem/article/${article.id}`, {
    preserveScroll: true,
    preserveState: true,
    reset: ["articles"],
  });
};

const downloadExport = () => {
  window.open("/dashboard/admin/strichlistensystem/export", "_blank");
};

const escapeHtml = (value: string) => {
  const element = document.createElement("span");
  element.textContent = value;
  return element.innerHTML;
};

const getColumns = (): TableColumn[] => [
  new TableColumn({
    name: "name",
    sortKey: "name",
    text: "Name",
    valueFunction: (article: App.Models.Article) => escapeHtml(article.name),
  }),
  new TableColumn({
    name: "icon",
    sortKey: "icon",
    text: "Icon",
    valueFunction: (article: App.Models.Article) => escapeHtml(article.icon),
  }),
  new TableColumn({
    name: "show_order",
    sortKey: "show_order",
    text: "Reihenfolge",
    valueFunction: (article: App.Models.Article) => `${article.show_order}`,
  }),
  new TableColumn({
    name: "max_order_amount",
    sortKey: "max_order_amount",
    text: "Max. Anzahl",
    valueFunction: (article: App.Models.Article) =>
      `${article.max_order_amount}`,
  }),
  new TableColumn({
    name: "is_active",
    sortKey: "is_active",
    text: "Status",
    valueFunction: (article: App.Models.Article) =>
      article.is_active ? "Aktiv" : "Deaktiviert",
  }),
  new TableColumn({
    name: "article_action_logs_count",
    sortKey: "article_action_logs_count",
    text: "Buchungen",
    valueFunction: (article: App.Models.Article) =>
      `${article.article_action_logs_count ?? 0}`,
  }),
];

const getFunctions = (): TableFunction[] => [
  new TableButton({
    name: "edit",
    text: "Bearbeiten",
    buttonFunction: editArticle,
  }),
  new TableStateButton({
    name: "toggleActive",
    defaultState: new TableButton({
      name: "deactivate",
      text: "Deaktivieren",
      theme: "warning",
      buttonFunction: toggleArticleActive,
    }),
    states: [
      new ButtonState({
        condition: (article: App.Models.Article) => !article.is_active,
        button: new TableButton({
          name: "activate",
          text: "Aktivieren",
          buttonFunction: toggleArticleActive,
        }),
      }),
    ],
  }),
  new TableButton({
    name: "delete",
    text: "Löschen",
    theme: "danger",
    buttonFunction: deleteArticle,
    disabledFunction: (article: App.Models.Article) =>
      (article.article_action_logs_count ?? 0) > 0,
  }),
];
</script>
