import { TableFunction } from "./table-function";

// Link to be displayed per row in a table (see components/app/Table.vue)
export class TableLink implements TableFunction {
  // internal name (must be unique per table)
  public name: string;

  // Displayed text
  public text: string;

  // Function to generate a link per element
  public linkFunction: (element: any) => string;

  // Theme of the link (see components/app/Link.vue)
  public theme: string;

  constructor({
    name,
    text,
    linkFunction,
    theme = "default",
  }: {
    name: string;
    text: string;
    linkFunction: (element: any) => string;
    theme?: string;
  }) {
    this.name = name;
    this.text = text;
    this.linkFunction = linkFunction;
    this.theme = theme;
  }
}
