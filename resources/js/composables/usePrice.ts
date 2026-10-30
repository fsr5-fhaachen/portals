// prices of the strichlistensystem are always integer cents
const priceFormatter = new Intl.NumberFormat("de-DE", {
  style: "currency",
  currency: "EUR",
});

// format cents as euro, e.g. 150 => "1,50 €"
export const formatPrice = (cents: number): string =>
  priceFormatter.format(cents / 100);

// convert a euro input (e.g. "1,50", "1.5" or 1.5) into cents, null if it is not a valid amount with max. 2 decimals
export const parsePriceToCents = (
  value: string | number | null | undefined,
): number | null => {
  if (value === null || value === undefined) {
    return null;
  }

  const normalizedValue = String(value).trim().replace(",", ".");
  const match = /^(\d+)(?:\.(\d{1,2}))?$/.exec(normalizedValue);
  if (!match) {
    return null;
  }

  return (
    parseInt(match[1], 10) * 100 + parseInt((match[2] ?? "").padEnd(2, "0"), 10)
  );
};

// convert cents into the value of a euro input, e.g. 150 => "1.50"
export const centsToPriceInput = (cents: number | null | undefined): string =>
  cents === null || cents === undefined ? "" : (cents / 100).toFixed(2);
