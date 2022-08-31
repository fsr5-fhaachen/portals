export default (courses: any[]) => {
  const options: Form.SelectOption[] = [];

  courses.forEach((course: any) => {
    options.push({
      value: course.id,
      label: course.name,
    });
  });

  return options;
};
