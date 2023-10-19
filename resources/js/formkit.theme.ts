const textClassification = {
  label: "block text-sm font-medium text-gray-700 dark:text-gray-200",
  inner: "mt-1",
  input:
    "shadow-sm focus:ring-fhac-mint focus:border-fhac-mint block w-full sm:text-sm border-gray-300 rounded-md formkit-invalid:border-red-300 formkit-invalid:text-red-900 formkit-invalid:placeholder-red-300 formkit-invalid:focus:ring-red-500 formkit-invalid:focus:border-red-500 dark:bg-gray-700 dark:border-gray-700 dark:focus:ring-gray-500 dark:focus:border-gray-500 dark:text-gray-300 formkit-invalid:dark:placeholder-red-500",
};

const boxClassification = {
  fieldset: "max-w-md border border-gray-400 rounded-md px-2 pb-1",
  legend: "font-bold text-sm",
  wrapper: "relative flex items-start cursor-pointer items-center",
  help: "mb-2",
  inner: "flex items-center h-5",
  input:
    "focus:ring-fhac-mint h-4 w-4 text-fhac-mint-dark border-gray-300 rounded",
  label: "ml-3 text-sm text-gray-700",
};
const buttonClassification = {
  wrapper: "mb-1",
  input:
    "w-full flex gap-2 items-center justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-fhac-mint-dark hover:bg-fhac-mint focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-fhac-mint",
};
const checkboxClassification = {
  ...boxClassification,
  label: "ml-3 text-md text-gray-900 dark:text-gray-200",
};

const fileUploadClassification = {
  label: "block text-sm font-medium text-gray-700 dark:text-gray-200",
  inner: "max-w-md cursor-pointer",
  input:
    "text-gray-600 text-sm mb-1 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:bg-blue-500 file:text-white hover:file:bg-blue-600",
  noFiles: "block text-sm font-medium text-gray-700 dark:text-gray-200",
  fileItem: "block text-sm font-medium text-gray-700 dark:text-gray-200",
  removeFiles: "block text-sm font-medium text-gray-700 dark:text-gray-200",
};


export default {
  global: {
    outer: "flex-1 formkit-disabled:opacity-60",
    help: "mt-2 text-sm text-gray-500",
    messages: "list-none p-0 mt-1",
    message: "mt-2 text-sm text-red-600",
  },
  button: buttonClassification,
  color: {
    label: "block mb-1 font-bold text-sm",
    input:
      "w-16 h-8 appearance-none cursor-pointer border border-gray-300 rounded-md mb-2 p-1",
  },
  date: textClassification,
  "datetime-local": textClassification,
  checkbox: checkboxClassification,
  email: textClassification,
  file: fileUploadClassification,
  month: textClassification,
  number: textClassification,
  password: textClassification,
  radio: {
    ...boxClassification,
    input: boxClassification.input.replace("rounded-sm", "rounded-full"),
  },
  range: {
    inner: "max-w-md",
    input:
      "form-range appearance-none w-full h-2 p-0 bg-gray-200 rounded-full focus:outline-none focus:ring-0 focus:shadow-none",
  },
  search: textClassification,
  select: textClassification,
  submit: buttonClassification,
  tel: textClassification,
  text: textClassification,
  textarea: {
    ...textClassification,
    input:
      "shadow-sm focus:ring-fhac-mint focus:border-fhac-mint block w-full sm:text-sm border-gray-300 rounded-md",
  },
  time: textClassification,
  url: textClassification,
  week: textClassification,
};
