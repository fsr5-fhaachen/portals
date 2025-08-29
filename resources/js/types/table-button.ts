import { TableFunction } from "./table-function";

export class TableButton implements TableFunction {
  constructor(
    public name: string,
    public text: string,
    public buttonFunction: (element: any) => void,
    public disabledFunction: (element: any) => boolean = (element) => false,
    public theme = "default",
  ) {}
}
