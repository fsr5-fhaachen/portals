<template>
  <div class="py-6">
    <header>
      <div
        class="mx-auto flex max-w-7xl items-start justify-between gap-4 px-4 sm:px-6 lg:px-8"
      >
        <div>
          <UiH1>
            <slot name="title" />
          </UiH1>
          <UiH2>
            <slot name="subtitle" />
          </UiH2>
        </div>
        <!-- e.g. the color mode button on pages without navbar -->
        <div v-if="$slots.actions" class="flex-shrink-0">
          <slot name="actions" />
        </div>
      </div>
    </header>
    <main class="mt-6">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <slot />
      </div>
    </main>
  </div>
</template>
