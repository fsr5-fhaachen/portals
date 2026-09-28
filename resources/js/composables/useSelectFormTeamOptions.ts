export default (teams: Pick<App.Models.CompetitionTeam, "id" | "name">[]) => {
  const options: Form.SelectOption[] = [];

  if (teams && teams.length > 0) {
    teams.forEach((team) => {
      options.push({
        value: team.id,
        label: team.name,
      });
    });
  }

  return options;
};
