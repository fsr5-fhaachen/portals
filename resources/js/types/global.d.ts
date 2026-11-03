export {};
declare global {
  const __PACKAGE_NAME__: string;
  const __PACKAGE_REPOSITORY_URL__: string;

  export interface NavbarLink {
    title: string;
    href: string;
    // also active on sub pages (e.g. /dashboard/admin/scoring/1)
    matchPrefix?: boolean;
  }

  export interface NavbarGroup {
    title: string;
    // admin groups are highlighted in red
    isAdmin?: boolean;
    links: NavbarLink[];
  }
}
