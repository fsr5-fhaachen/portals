export default (appEventType: "demo" | "erstiwoche" | "gerolstein") => {
  if (appEventType === "demo") {
    return {
      title: "Demo",
    };
  } else if (appEventType === "erstiwoche") {
    return {
      title: "Erstiwoche",
    };
  } else if (appEventType === "gerolstein") {
    return {
      title: "Gerolstein",
    };
  } else {
    return {
      title: "Unkown type",
    };
  }
};
