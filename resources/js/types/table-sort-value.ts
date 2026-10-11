// Sortable value of a table that is not a column (see components/app/Table.vue)
export class TableSortValue {
  // internal name of the sort value (must be unique per table)
  public name: string;

  // Displayed text
  public text: string;

  // Sort key on the server (see app/Helpers/Table)
  public sortKey: string;

  constructor({
    name,
    text,
    sortKey,
  }: {
    name: string;
    text: string;
    sortKey: string;
  }) {
    this.name = name;
    this.text = text;
    this.sortKey = sortKey;
  }
}
