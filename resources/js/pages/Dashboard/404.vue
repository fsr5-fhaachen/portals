<template>
  <LayoutDashboardContent>
    <div class="flex flex-shrink-0 justify-center">
      <AppLink href="/dashboard" theme="none" class="inline-flex">
        <img
          class="mx-auto h-48 w-auto dark:hidden"
          src="/images/logo.png"
          alt="FSR 5 Logo"
          loading="lazy"
        />
        <img
          class="mx-auto hidden h-48 w-auto dark:block"
          src="/images/logo-dark.png"
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
          <AppLink href="/dashboard" class="font-medium">
            Zurück zur Startseite
          </AppLink>
        </div>
      </div>
    </div>
  </LayoutDashboardContent>
</template>
