export default (slots: App.Models.Slot[]) => {
  const options: Form.SelectOption[] = [];

  slots.forEach((slot) => {
    options.push({
      value: slot.id,
      label: slot.name,
    });
  });

  return options;
};
