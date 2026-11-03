const textClassification = {
  label: "block text-sm font-medium text-gray-700 dark:text-gray-200",
  inner: "mt-1",
  input:
    "shadow-sm focus:ring-fhac-mint focus:border-fhac-mint block w-full sm:text-sm border-gray-300 rounded-md formkit-invalid:border-red-300 formkit-invalid:text-red-900 formkit-invalid:placeholder-red-300 formkit-invalid:focus:ring-red-500 formkit-invalid:focus:border-red-500 dark:bg-gray-700 dark:border-gray-700 dark:focus:ring-gray-500 dark:focus:border-gray-500 dark:text-gray-300 formkit-invalid:dark:placeholder-red-500",
};

const boxClassification = {
  fieldset: "max-w-md border border-gray-400 rounded-md px-2 pb-1",
  legend: "font-bold text-sm dark:text-gray-200",
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
  inner: "mt-1 flex flex-wrap items-center gap-x-4 gap-y-2",
  // the native input shows the button and the name of the selected file, the focus ring replaces the black outline
  input:
    "max-w-full cursor-pointer rounded-md text-sm text-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-fhac-mint dark:text-gray-300 file:mr-3 file:cursor-pointer file:rounded-md file:border-0 file:bg-fhac-mint-dark file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-fhac-mint",
  // the file name is already shown by the native input, only the remove button is kept
  fileList: "flex",
  fileItem: "flex",
  fileItemIcon: "hidden",
  fileName: "hidden",
  // the button text contains the file name ("Entfernen <name>"), it is shown as a short label instead
  fileRemove:
    "text-[0px] text-red-600 hover:underline dark:text-red-400 before:text-sm before:content-['Entfernen']",
  fileRemoveIcon: "hidden",
  noFiles: "hidden",
  noFilesIcon: "hidden",
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
