declare namespace App.Models {
  type CourseEvent = {
    id: number;
    created_at: string /* Date */ | null;
    updated_at: string /* Date */ | null;
    course_id: number;
    event_id: number;
    course?: Course | null;
    event?: Event | null;
  };
}
