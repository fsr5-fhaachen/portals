import { TableFunction } from "./table-function";

export class TableButton implements TableFunction {
  constructor(
    public name: string,
    public text: string,
    public buttonFunction: (element: any) => void,
    public disabled = false,
    public theme = "default",
  ) {}
}
