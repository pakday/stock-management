var siteLanguage = document.documentElement.lang;

jQuery("[id$='eerste_huurdag']").on("change", function () {
  var eersteHuurdagDate = new parseDate(jQuery(this).val());
  var laatsteHuurdagDate = new parseDate(jQuery("[id$='retourdatum']").val());

  if (eersteHuurdagDate > laatsteHuurdagDate) {
    alert("Waarschuwing: Eerste Huurdag should be less than Laatste Huurdag");
    jQuery(this).val("");
  }
});

jQuery("[id$='retourdatum']").on("change", function () {
  var eersteHuurdagDate = new parseDate(jQuery("[id$='eerste_huurdag']").val());
  var laatsteHuurdagDate = new parseDate(jQuery(this).val());

  if (laatsteHuurdagDate < eersteHuurdagDate) {
    alert(
      "Waarschuwing: Laatste Huurdag should be greater than Eerste Huurdag"
    );
    jQuery(this).val("");
  }
});

function parseDate(dateString) {
  if (siteLanguage === "nl-NL") {
    return parseDutchDate(dateString);
  } else {
    return new Date(dateString);
  }
}

function parseDutchDate(dateString) {
  const [day, month, year] = dateString.split(" ");
  const monthIndex = [
    "januari",
    "februari",
    "maart",
    "april",
    "mei",
    "juni",
    "juli",
    "augustus",
    "september",
    "oktober",
    "november",
    "december",
  ].indexOf(month);
  console.log("month", month);
  return new Date(year, monthIndex, parseInt(day));
}
