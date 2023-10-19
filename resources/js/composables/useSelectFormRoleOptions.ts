export default (roles: App.Models.Role[]): Form.SelectOption[] => {
  const options: Form.SelectOption[] = [];

  roles.forEach((role) => {
    options.push({
      value: role.id,
      label: role.name,
    });
  });

  return options;
};
