export default (appEventType: "demo" | "erstiwoche" | "gerolstein") => {
  if (appEventType === "demo") {
    return {
      titleArticle: "der",
      title: "Demo",
    };
  } else if (appEventType === "erstiwoche") {
    return {
      titleArticle: "der",
      title: "Erstiwoche",
    };
  } else if (appEventType === "gerolstein") {
    return {
      titleArticle: "",
      title: "Gerolstein",
    };
  } else {
    return {
      titleArticle: "",
      title: "Unkown type",
    };
  }
};
