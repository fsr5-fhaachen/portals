<template>
  <AppTable
    data="workshopSessions"
    searchable
    :columns="getColumns()"
    :idFunction="(session) => session.id"
    :functions="getLinks()"
    :rowClass="
      (session) => ({
        'opacity-60': session.is_cancelled,
      })
    "
  />
</template>

<script setup lang="ts">
import { TableColumn } from "../../types/table-column";
import { TableLink } from "../../types/table-link";

// row of app/Helpers/Table/WorkshopSessionsTable
type SessionRow = {
  id: number;
  workshop_name: string | null;
  block_name: string | null;
  starts_at: string | null;
  ends_at: string | null;
  location: string | null;
  is_cancelled: boolean;
  maximum_participants: number | null;
  assignments_count: number;
};

const formatTime = (value: string) =>
  new Date(value).toLocaleTimeString("de-DE", {
    hour: "2-digit",
    minute: "2-digit",
  });

function getColumns(): Array<TableColumn> {
  return [
    new TableColumn({
      name: "starts_at",
      text: "Wann",
      valueFunction: (session: SessionRow) => {
        const time =
          session.starts_at && session.ends_at
            ? `${formatTime(session.starts_at)}–${formatTime(session.ends_at)} Uhr`
            : "";

        return `<strong>${useEscapeHtml(session.block_name ?? "")}</strong><br>${time}`;
      },
      sortKey: "starts_at",
    }),
    new TableColumn({
      name: "workshop_name",
      text: "Workshop",
      valueFunction: (session: SessionRow) =>
        useEscapeHtml(session.workshop_name ?? "") +
        (session.is_cancelled
          ? ' <span class="rounded-md bg-red-700 px-2 py-1 text-xs text-white">Fällt aus</span>'
          : ""),
      sortKey: "workshop_name",
    }),
    new TableColumn({
      name: "location",
      text: "Wo",
      valueFunction: (session: SessionRow) =>
        useEscapeHtml(session.location ?? "–"),
      sortKey: "location",
    }),
    new TableColumn({
      name: "participants",
      text: "Teilnehmer",
      valueFunction: (session: SessionRow) =>
        session.maximum_participants
          ? `${session.assignments_count} / ${session.maximum_participants}`
          : `${session.assignments_count}`,
      sortKey: "assignments_count",
    }),
  ];
}

function getLinks(): Array<TableLink> {
  return [
    new TableLink({
      name: "show",
      text: "Teilnehmende",
      linkFunction: (session: SessionRow) =>
        "/dashboard/tutor/workshop-session/" + session.id,
      theme: "default",
    }),
  ];
}
</script>
