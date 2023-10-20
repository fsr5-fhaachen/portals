declare namespace Models {
  export interface User extends App.Models.User {
    rolesArray: string[];
    permissionsArray: string[];
    avatarUrl?: string;
  }
}
