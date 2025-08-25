export type LinkFn = (element: any) => string;

export class TableLink {
  constructor(
    public name: string,
    public text: string,
    public linkFn: LinkFn,
    public theme: string,
  ) {}
}
