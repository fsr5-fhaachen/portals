import { ref } from 'vue';

export default () => {
  const placeholderPersons = ref([
    {
      firstname: "Max",
      lastname: "Mustermann",
      email: "max.mustermann@beispiel.de",
    },
    {
      firstname: "Erika",
      lastname: "Mustermann",
      email: "erika.mustermann@beispiel.de",
    },
  ]);

  return placeholderPersons.value[
    Math.floor(Math.random() * placeholderPersons.value.length)
  ];
};
