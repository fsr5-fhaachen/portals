declare namespace Models {
  export interface User extends App.Models.User {
    rolesArray: string[];
    permissionsArray: string[];
    avatarUrl?: string;
    has_pin?: boolean;
    // open amount of the strichlistensystem in cents, negative if the user paid more
    strichlistensystem_open?: number;
  }
}
