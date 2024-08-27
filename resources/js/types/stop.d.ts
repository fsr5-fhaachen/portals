declare namespace App.Models {
  type Stop = {
    id: number;
    created_at: string /* Date */ | null;
    updated_at: string /* Date */ | null;
    group_id: number;
    station_id: number;
    arrival_at: string /* Date */ | null;
    departure_at: string /* Date */ | null;
    group?: Group | null;
    station?: Station | null;
  };
}
