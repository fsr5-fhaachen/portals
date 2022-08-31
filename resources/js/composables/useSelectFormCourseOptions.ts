import { FormKitOptionsList } from '@formkit/vue';

export default (courses: any[]) => {
  const options: FormKitOptionsList = [];

  courses.forEach((course: any) => {
    options.push({
      value: course.id,
      label: course.name,
    });
  }

  return options;
}