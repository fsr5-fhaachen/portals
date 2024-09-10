declare namespace App.Models {
  type CourseGroup = {
    id: number;
    created_at: string /* Date */ | null;
    updated_at: string /* Date */ | null;
    course_id: number;
    group_id: number;
    course?: Course | null;
    group?: Group | null;
  };
}
