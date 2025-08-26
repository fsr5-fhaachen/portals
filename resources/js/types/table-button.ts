import { TableFunction } from "./table-function";

export class TableButton implements TableFunction {
  constructor(
    public name: string,
    public text: string,
    public disabled: boolean,
    public buttonFunction: (element: any) => void,
    public theme: string,
  ) {}
}
