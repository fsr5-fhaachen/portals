<template>
  <div
    class="mt-16 mx-auto flex w-full max-w-7xl flex-grow flex-col justify-center px-4 sm:px-6 lg:px-8"
  >
    <div class="flex flex-shrink-0 justify-center">
      <AppLink href="/" theme="none" class="inline-flex">
        <img
          class="mx-auto h-48 w-auto"
          src="/images/logo.png"
          alt="FSR 5 Logo"
          loading="lazy"
        />
      </AppLink>
    </div>
    <div class="py-16">
      <div class="text-center">
        <p class="text-base font-semibold text-fhac-mint-dark">404</p>
        <UiH1> Seite nicht gefunden </UiH1>
        <p class="mt-2 text-base text-gray-500">
          Die angeforderte Seite konnte nicht gefunden werden.
        </p>
        <div class="mt-6">
          <AppLink href="/" class="font-medium">
            Zurück zur Startseite
          </AppLink>
        </div>
      </div>
    </div>
  </div>
</template>
