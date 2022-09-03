export default (courses: App.Models.Course[]) => {
  const options: Form.SelectOption[] = [];

  courses.forEach((course) => {
    options.push({
      value: course.id,
      label: course.name,
    });
  });

  return options;
};
