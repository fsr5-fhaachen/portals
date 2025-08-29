import { TableFunction } from "./table-function";

export class TableLink implements TableFunction {
  constructor(
    public name: string,
    public text: string,
    public linkFunction: (element: any) => string,
    public theme = "default",
  ) {}
}
