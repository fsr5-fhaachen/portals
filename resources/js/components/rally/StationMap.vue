<template>
  <div
    v-if="pinnedStops.length === 0"
    class="text-sm text-gray-500 dark:text-gray-400"
  >
    Für die Karte wurden noch keine Standortdaten hinterlegt.
  </div>
  <div
    v-else
    class="h-72 w-full overflow-hidden rounded-lg sm:h-96"
    style="z-index: 0"
  >
    <LMap :zoom="mapZoom" :center="mapCenter" :useGlobalLeaflet="false">
      <LTileLayer
        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        attribution="&copy; OpenStreetMap-Mitwirkende"
        layer-type="base"
      />
      <LMarker
        v-for="stop in pinnedStops"
        :key="stop.round"
        :lat-lng="[stop.station.latitude, stop.station.longitude]"
        :icon="pinIcon(stop)"
      >
        <LTooltip>
          Runde {{ stop.round }}: {{ stop.station.name }}
        </LTooltip>
      </LMarker>
    </LMap>
  </div>
</template>

<script setup lang="ts">
import { computed, PropType } from "vue";
import { LMap, LTileLayer, LMarker, LTooltip } from "@vue-leaflet/vue-leaflet";
import L from "leaflet";
import "leaflet/dist/leaflet.css";

const { stops, scoringEnabled } = defineProps({
  stops: {
    type: Array as PropType<Array<any>>,
    required: true,
  },
  scoringEnabled: {
    type: Boolean,
    default: false,
  },
});

const pinnedStops = computed(() =>
  stops.filter(
    (stop) =>
      stop.station &&
      stop.station.latitude !== null &&
      stop.station.longitude !== null,
  ),
);

const mapCenter = computed<[number, number]>(() => {
  if (pinnedStops.value.length === 0) {
    return [50.7753, 6.0839]; // Aachen, nur Fallback falls keine Pins vorhanden sind
  }

  const latSum = pinnedStops.value.reduce(
    (sum, stop) => sum + stop.station.latitude,
    0,
  );
  const lngSum = pinnedStops.value.reduce(
    (sum, stop) => sum + stop.station.longitude,
    0,
  );

  return [
    latSum / pinnedStops.value.length,
    lngSum / pinnedStops.value.length,
  ];
});

const mapZoom = 14;

function pinColor(stop: any): string {
  if (scoringEnabled && stop.points !== null && stop.points !== undefined) {
    if (stop.points === 4) return "#16a34a"; // grün, gewonnen
    if (stop.points === 0) return "#dc2626"; // rot, verloren
    return "#eab308"; // gelb, unentschieden
  }

  if (stop.ends_at && new Date(stop.ends_at) < new Date()) {
    return "#16a34a"; // grün, erledigt (ohne Wertung)
  }

  return "#9ca3af"; // grau, noch ausstehend
}

function pinIcon(stop: any) {
  const color = pinColor(stop);

  return L.divIcon({
    className: "",
    html: `<div style="
      background:${color};
      color:white;
      width:2rem;
      height:2rem;
      border-radius:9999px;
      display:flex;
      align-items:center;
      justify-content:center;
      font-weight:bold;
      font-size:0.875rem;
      box-shadow:0 1px 3px rgba(0,0,0,0.4);
      border:2px solid white;
    ">${stop.round}</div>`,
    iconSize: [32, 32],
    iconAnchor: [16, 16],
  });
}
</script>
