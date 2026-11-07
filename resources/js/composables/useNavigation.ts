import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";

/**
 * Navigation of the navbar sorted into groups, based on the shared props of every page.
 * Links the user can not use and empty groups are left out.
 */
export default () => {
  const page = usePage();

  const groups = computed<NavbarGroup[]>(() => {
    const user = page.props.user as Models.User | null;
    const modules = (page.props.modules ?? {}) as Record<
      string,
      App.Models.Module
    >;
    const pages = (page.props.pages ?? []) as App.Models.Page[];

    const can = (permission: string) =>
      user?.permissionsArray?.includes(permission) ?? false;
    const isModuleActive = (key: string) => modules[key]?.active ?? false;
    const isTutor = ["admin", "esa", "stage tutor", "tutor"].some((role) =>
      user?.rolesArray?.includes(role),
    );
    const isTutorPage = page.url.startsWith("/dashboard/tutor");

    // only keep the links that are visible for the user
    const visible = (links: (NavbarLink & { visible: boolean })[]) =>
      links
        .filter((link) => link.visible)
        .map(({ visible: _, ...link }) => link);

    return [
      {
        title: "Allgemein",
        links: visible([
          {
            title: "Veranstaltungen",
            href: "/dashboard" + (isTutorPage ? "/tutor" : ""),
            visible: true,
          },
          {
            title: "Strichliste",
            href: "/strichlistensystem",
            visible: isModuleActive("strichlistensystem"),
          },
          {
            title: "Zufallsgenerator",
            href: "/dashboard/random-generator",
            visible:
              isModuleActive("randomGenerator") &&
              !!page.props.randomGeneratorOptOutEnabled &&
              !isTutor,
          },
          {
            title: "Zufallsgenerator",
            href: "/dashboard/tutor/random-generator",
            visible:
              isModuleActive("randomGenerator") &&
              !!page.props.randomGeneratorTutorOptOutEnabled &&
              isTutor,
          },
          {
            title: "Scoring",
            href: "/dashboard/tutor/scoring",
            matchPrefix: true,
            visible: isModuleActive("scoring") && isTutor,
          },
        ]),
      },
      {
        title: "Informationen",
        links: usePagesAsNavigation(pages, "/dashboard/"),
      },
      {
        title: "Benutzer",
        isAdmin: true,
        links: visible([
          {
            title: "User Verwaltung",
            href: "/dashboard/admin/users",
            visible: can("manage users"),
          },
          {
            title: "User registrieren/zuweisen",
            href: "/dashboard/admin/register",
            visible: can("manage users"),
          },
        ]),
      },
      {
        title: "Funktionen",
        isAdmin: true,
        links: visible([
          {
            title: "Strichliste",
            href: "/dashboard/admin/strichlistensystem",
            visible:
              isModuleActive("strichlistensystem") &&
              can("manage strichlistensystem"),
          },
          {
            title: "Scoring",
            href: "/dashboard/admin/scoring",
            matchPrefix: true,
            visible: isModuleActive("scoring") && can("manage scoring"),
          },
        ]),
      },
      {
        title: "Bühne",
        isAdmin: true,
        links: visible([
          {
            title: "Punktesystem",
            href: "/dashboard/admin/score-system",
            visible:
              isModuleActive("scoreSystem") && can("manage score system"),
          },
          {
            title: "Zufallsgenerator",
            href: "/dashboard/admin/random-generator",
            visible:
              isModuleActive("randomGenerator") &&
              can("manage random generator"),
          },
          {
            title: "Countdown",
            href: "/dashboard/admin/countdown",
            visible: isModuleActive("countdown") && can("manage countdown"),
          },
        ]),
      },
      {
        title: "Auswertung",
        isAdmin: true,
        links: visible([
          {
            title: "Statistik",
            href: "/dashboard/admin",
            visible: can("view statistics"),
          },
          {
            title: "Audit-Log",
            href: "/dashboard/admin/audit-log",
            matchPrefix: true,
            visible: can("view audit log"),
          },
        ]),
      },
    ].filter((group) => group.links.length > 0);
  });

  const isLinkActive = (link: NavbarLink) => {
    const path = page.url.split("?")[0];

    return link.matchPrefix ? path.startsWith(link.href) : path === link.href;
  };

  return { groups, isLinkActive };
};
