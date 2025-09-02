import { TableFunction } from "./table-function";

// A button that is displayed per element (row in a table) (see components/app/Table.vue)
export class TableButton implements TableFunction {
  // Internal name (must be unique per table)
  public name: string;

  // Displayed text
  public text: string;

  // Function to execute when button is clicked. Takes an element as parameter
  public buttonFunction: (element: any) => void;

  // Function to determine if the button is disabled for an element
  public disabledFunction: (element: any) => boolean;

  // Button theme (see components/app/Button.vue)
  public theme: string;

  constructor({
    name,
    text,
    buttonFunction,
    disabledFunction = () => false,
    theme = "default",
  }: {
    name: string;
    text: string;
    buttonFunction: (element: any) => void;
    disabledFunction?: (element: any) => boolean;
    theme?: string;
  }) {
    this.name = name;
    this.text = text;
    this.buttonFunction = buttonFunction;
    this.disabledFunction = disabledFunction;
    this.theme = theme;
  }
}
