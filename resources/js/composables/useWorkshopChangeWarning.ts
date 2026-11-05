// warning before a change of the workshops deletes assignments or priorities of users, empty if nobody is affected
export default (
  {
    assigned = 0,
    prioritized = 0,
  }: { assigned?: number; prioritized?: number },
  isPublished: boolean,
): string => {
  const warnings = [];

  if (assigned) {
    warnings.push(
      assigned === 1
        ? "1 Person ist dort eingeteilt und verliert ihren Platz."
        : `${assigned} Personen sind dort eingeteilt und verlieren ihren Platz.`,
    );

    if (isPublished) {
      warnings.push(
        "Die Einteilung ist veröffentlicht, die Betroffenen sehen die Änderung sofort.",
      );
    }
  }

  if (prioritized) {
    warnings.push(
      prioritized === 1
        ? "1 Person hat den Workshop als Wunsch angegeben und verliert diesen Wunsch."
        : `${prioritized} Personen haben den Workshop als Wunsch angegeben und verlieren diesen Wunsch.`,
    );
  }

  return warnings.join(" ");
};
