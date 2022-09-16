export default (events: App.Models.Event[]) => {
  const options: Form.SelectOption[] = [];

  events.forEach((event) => {
    options.push({
      value: event.id,
      label: event.name,
    });
  });

  return options;
};
