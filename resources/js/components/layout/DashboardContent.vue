<template>
  <div class="py-6">
    <header>
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <UiH1>
          <slot name="title" />
        </UiH1>
      </div>
    </header>
    <main class="mt-6">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <slot />
      </div>
    </main>
  </div>
</template>
