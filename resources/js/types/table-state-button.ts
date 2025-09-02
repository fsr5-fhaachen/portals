import { TableButton } from "./table-button";
import { TableFunction } from "./table-function";

// Possible State of a TableStateButton
export class ButtonState {
  // Condition for this state, evaluated per element
  public condition: (element: any) => boolean;

  // Button to be displayed if this condition is true
  public button: TableButton;

  constructor({
    condition,
    button,
  }: {
    condition: (element: any) => boolean;
    button: TableButton;
  }) {
    this.condition = condition;
    this.button = button;
  }
}

// A button that is displayed per element (row in a table). Depending on the element, it can be in different states (see components/app/Table.vue)
export class TableStateButton implements TableFunction {
  // internal name (must be unique per table)
  public name: string;

  // Button to display if no condition is true
  public defaultState: TableButton;

  // Different states the button can be in. Conditions get checked in the order of the array, first true condition gets selected
  public states: Array<ButtonState>;

  constructor({
    name,
    defaultState,
    states,
  }: {
    name: string;
    defaultState: TableButton;
    states: Array<ButtonState>;
  }) {
    this.name = name;
    this.defaultState = defaultState;
    this.states = states;
  }
}
