import { TableButton } from "./table-button";
import { TableFunction } from "./table-function";

export class ButtonState {
  constructor(
    public condition: (element: any) => boolean,
    public button: TableButton,
  ) {}
}

export class TableStateButton implements TableFunction {
  constructor(
    public name: string,
    public defaultState: TableButton,
    public states: Array<ButtonState>,
  ) {}
}
