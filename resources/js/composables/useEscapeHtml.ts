// escape user content before it is rendered with v-html (e.g. in components/app/Table.vue)
export default (value: string | number | null | undefined) => {
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
};
