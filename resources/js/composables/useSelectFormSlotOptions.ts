export default (slots: App.Models.Slot[]) => {
  const options: Form.SelectOption[] = [];

  slots.forEach((course: any) => {
    options.push({
      value: course.id,
      label: course.name,
    });
  });

  return options;
};
