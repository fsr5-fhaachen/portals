export default (groups: App.Models.Group[]) => {
  const options: Form.SelectOption[] = [];

  if (groups && groups.length > 0) {
    groups.forEach((group) => {
      options.push({
        value: group.id,
        label: group.name,
      });
    });
  }

  return options;
};
