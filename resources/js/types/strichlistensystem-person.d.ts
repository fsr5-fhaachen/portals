declare namespace Strichlistensystem {
  // person as provided by the kiosk (see StrichlistensystemKioskController::toPerson)
  type Person = {
    id: number;
    firstname: string;
    lastname: string;
    course: App.Models.Course | null;
    avatar_url: string | null;
    roles?: string[];
    has_pin?: boolean;
  };
}
