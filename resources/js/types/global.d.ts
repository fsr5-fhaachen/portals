export {};
declare global {
  const __PACKAGE_NAME__: string;
  const __PACKAGE_REPOSITORY_URL__: string;

  export interface NavbarLink {
    title: string;
    href: string;
  }
}
