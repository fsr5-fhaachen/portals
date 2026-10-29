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
            <UiH2>Abrechnung</UiH2>
            <p class="text-gray-500 dark:text-gray-400">
              Offene Beträge aller Personen ansehen und Zahlungen eintragen.
            </p>
          </div>
          <AppLink href="/dashboard/admin/strichlistensystem/billing">
            Zur Abrechnung
          </AppLink>
        </div>
      </CardBase>

      <CardBase>
        <div
          class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
          <div>
            <UiH2>Kiosk-Geräte</UiH2>
            <p class="text-gray-500 dark:text-gray-400">
              Tablets für den Kiosk freischalten oder sperren.
            </p>
          </div>
          <AppLink href="/dashboard/admin/strichlistensystem/kiosk-devices">
            Zu den Kiosk-Geräten
          </AppLink>
        </div>
      </CardBase>

      <CardBase>
        <div
          class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
          <div>
            <UiH2>Export</UiH2>
            <p class="text-gray-500 dark:text-gray-400">
              Anzahl und Beträge der gebuchten Artikel (ohne Stornierungen),
              Gesamtsumme, bezahlter und offener Betrag pro Person als CSV.
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
                type="file"
                name="image"
                label="Bild (optional)"
                accept=".png,.jpg,.jpeg,.webp"
                help="PNG, JPG oder WebP, höchstens 2 MB. Das Bild wird statt des Icons angezeigt. Ein PNG mit transparentem Hintergrund passt zum hellen und dunklen Design."
              />
              <img
                v-if="imagePreviewUrl"
                :src="imagePreviewUrl"
                alt="Vorschau"
                class="h-16 w-16 flex-shrink-0 self-end object-contain"
              />
            </FormRow>
            <FormRow
              v-if="articleToEdit?.image_url && !articleForm.image.length"
            >
              <FormKit
                type="checkbox"
                name="remove_image"
                label="Bild entfernen"
                help="Danach wird wieder das Icon angezeigt."
              />
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
                type="text"
                name="price"
                label="Preis in €"
                placeholder="1,50"
                inputmode="decimal"
                autocomplete="off"
                help="Gilt nur für neue Buchungen. Bisherige Buchungen behalten ihren Preis. Buchungen ohne Preis erhalten einmalig den ersten festgelegten Preis."
                :validation="[['required'], ['matches', priceInputPattern]]"
                :validation-messages="{
                  matches:
                    'Bitte gib einen Preis zwischen 0 und 1000 € mit höchstens 2 Nachkommastellen an, z. B. 1,50.',
                }"
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
import { computed, PropType, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { reset } from "@formkit/core";
import {
  centsToPriceInput,
  formatPrice,
  parsePriceToCents,
} from "../../../../composables/usePrice";
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

// 0 to 1000 euro with max. 2 decimals, comma or dot as decimal separator
const priceInputPattern = /^(\d{1,3}([.,]\d{1,2})?|1000([.,]0{1,2})?)$/;

type ArticleForm = {
  name: string;
  icon: string;
  show_order: number;
  max_order_amount: number;
  // price in euro as entered, e.g. "1,50"
  price: string;
  is_active: boolean;
  // selected files of the formkit file input, only the first one is uploaded
  image: { name: string; file?: File }[];
  remove_image: boolean;
};

const emptyArticleForm = (): ArticleForm => ({
  name: "",
  icon: "",
  show_order: 0,
  max_order_amount: 1,
  price: "",
  is_active: true,
  image: [],
  remove_image: false,
});

const articleForm = ref<ArticleForm>(emptyArticleForm());
const articleToEdit = ref<App.Models.Article | null>(null);

// preview of the selected image, otherwise of the current image of the edited article
const selectedImageUrl = ref<string | null>(null);
watch(
  () => articleForm.value.image?.[0]?.file,
  (file) => {
    if (selectedImageUrl.value) {
      URL.revokeObjectURL(selectedImageUrl.value);
    }
    selectedImageUrl.value = file ? URL.createObjectURL(file) : null;
  },
);
const imagePreviewUrl = computed(() => {
  if (selectedImageUrl.value) {
    return selectedImageUrl.value;
  }

  return articleForm.value.remove_image
    ? null
    : (articleToEdit.value?.image_url ?? null);
});

const resetArticleForm = () => {
  articleToEdit.value = null;
  // reset via formkit, otherwise the form stays submitted and shows the validation errors of the empty fields
  reset("article", emptyArticleForm());
  articleForm.value = emptyArticleForm();
};

const editArticle = (article: App.Models.Article) => {
  articleToEdit.value = article;
  articleForm.value = {
    name: article.name,
    icon: article.icon,
    show_order: article.show_order,
    max_order_amount: article.max_order_amount,
    price: centsToPriceInput(article.price).replace(".", ","),
    is_active: article.is_active,
    image: [],
    remove_image: false,
  };
  window.scrollTo({ top: 0, behavior: "smooth" });
};

const submitArticleHandler = () => {
  const url = articleToEdit.value
    ? `/dashboard/admin/strichlistensystem/article/${articleToEdit.value.id}`
    : "/dashboard/admin/strichlistensystem/article";

  // the price is sent in cents, with an image the data is sent as form data
  router.post(
    url,
    {
      ...articleForm.value,
      price: parsePriceToCents(articleForm.value.price),
      image: articleForm.value.image?.[0]?.file ?? null,
    },
    {
      preserveScroll: true,
      preserveState: true,
      reset: ["articles"],
      onSuccess: () => {
        resetArticleForm();
      },
    },
  );
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
    text: "Icon / Bild",
    valueFunction: (article: App.Models.Article) =>
      article.image_url
        ? `<img src="${escapeHtml(article.image_url)}" alt="" class="h-8 w-8 object-contain" />`
        : escapeHtml(article.icon),
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
    name: "price",
    sortKey: "price",
    text: "Preis",
    valueFunction: (article: App.Models.Article) =>
      article.price !== null
        ? formatPrice(article.price)
        : '<span class="text-red-600 dark:text-red-400">kein Preis</span>',
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
