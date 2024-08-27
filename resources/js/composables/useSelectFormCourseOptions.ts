export default (
  courses: App.Models.Course[],
  showAll = false,
): Form.SelectOption[] => {
  const options: Form.SelectOption[] = [];

  courses.forEach((course) => {
    // skip if course is not shown on registration form
    if (!showAll && !course.show_on_registration) {
      return;
    }

    options.push({
      value: course.id,
      label: course.name,
    });
  });

  return options;
};
