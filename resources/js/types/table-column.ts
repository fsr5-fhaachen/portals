// Column of a table (see components/app/Table.vue)
export class TableColumn {
  // internal name of the column (must be unique per table)
  public name: string;

  // Displayed column header
  public header: string;

  // Function to get this columns value per element. Can be html
  public valueFunction: (element: any) => string;

  // Function to compare two elements. Used for sorting. Returns either -1, 0 or 1
  public compareFunction: (element1: any, element2: any) => number;

  constructor({
    name,
    text,
    valueFunction,
    compareFunction,
  }: {
    name: string;
    text: string;
    valueFunction: (element: any) => string;
    compareFunction: (element1: any, element2: any) => number;
  }) {
    this.name = name;
    this.header = text;
    this.valueFunction = valueFunction;
    this.compareFunction = compareFunction;
  }
}
