export type ValueFn = (element: any) => string;
export type CompareFn = (element1: any, element2: any) => number;

export class TableColumn {
  constructor(
    public name: string,
    public text: string,
    public valueFn: ValueFn,
    public compareFn: CompareFn,
  ) {}
}
