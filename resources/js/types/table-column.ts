// Column of a table (see components/app/Table.vue)
export class TableColumn {
  // internal name of the column (must be unique per table)
  public name: string;

  // Displayed column header
  public header: string;

  // Function to get this columns value per element. Can be html
  public valueFunction: (element: any) => string;

  // Sort key of the column on the server (see app/Helpers/Table). Columns without sort key can not be sorted
  public sortKey: string | null;

  constructor({
    name,
    text,
    valueFunction,
    sortKey = null,
  }: {
    name: string;
    text: string;
    valueFunction: (element: any) => string;
    sortKey?: string | null;
  }) {
    this.name = name;
    this.header = text;
    this.valueFunction = valueFunction;
    this.sortKey = sortKey;
  }
}
