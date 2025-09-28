// Sortable value of a table (see components/app/Table.vue)
export class TableSortValue {
  // internal name of the column (must be unique per table)
  public name: string;

  // Displayed column header
  public text: string;

  // Function to compare two elements. Used for sorting. Returns either -1, 0 or 1
  public compareFunction: (element1: any, element2: any) => number;

  constructor({
    name,
    text,
    compareFunction,
  }: {
    name: string;
    text: string;
    compareFunction: (element1: any, element2: any) => number;
  }) {
    this.name = name;
    this.text = text;
    this.compareFunction = compareFunction;
  }
}
