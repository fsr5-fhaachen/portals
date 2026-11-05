declare namespace Workshops {
  // registration as provided by the admin (see DashboardAdminWorkshopController::index)
  type RegistrationRow = {
    id: number;
    user: Pick<
      App.Models.User,
      "id" | "firstname" | "lastname" | "email" | "course_id"
    > & {
      course?: Pick<
        App.Models.Course,
        "id" | "abbreviation" | "classes"
      > | null;
    };
    // workshop ids ordered by priority
    priorities: number[];
    // session id by block id
    assignments: Record<number, number> | [];
  };
}
