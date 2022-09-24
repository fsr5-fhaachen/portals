export default (pages: App.Models.Page[], prefix: string = "/") => {
  const navigationLinks: NavbarLink[] = [];

  pages.forEach((page: any) => {
    navigationLinks.push({
      title: page.title,
      href: prefix + page.slug,
    });
  });

  return navigationLinks;
};
