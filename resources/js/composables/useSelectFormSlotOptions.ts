export default (slots: App.Models.Slot[]) => {
  const options: Form.SelectOption[] = [];

  if (slots && slots.length > 0) {
    slots.forEach((slot) => {
      options.push({
        value: slot.id,
        label: slot.name,
      });
    });
  }

  return options;
};
