const escapeHtml = (value: string) =>
  value
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");

/**
 * Rebuild the html of a node with only the allowed tags and without any attributes.
 * Other tags are removed and only their text is kept, so injected html is never executed.
 */
const sanitizeNode = (node: Node, allowedTags: string[]): string => {
  if (node.nodeType === Node.TEXT_NODE) {
    return escapeHtml(node.textContent ?? "");
  }

  if (node.nodeType !== Node.ELEMENT_NODE) {
    return "";
  }

  const tag = (node as Element).tagName.toLowerCase();
  const content = Array.from(node.childNodes)
    .map((child) => sanitizeNode(child, allowedTags))
    .join("");

  if (!allowedTags.includes(tag)) {
    return content;
  }

  return tag === "br" ? "<br>" : `<${tag}>${content}</${tag}>`;
};

/**
 * Sanitize html for v-html. Messages may contain formatting like <strong>, but also user input
 * (names, e-mails), which must never be rendered as html. Values that are already escaped
 * (e.g. with e() in php) are shown correctly, because the html is parsed before it is rebuilt.
 */
export const sanitizeHtml = (
  html: string | null | undefined,
  allowedTags: string[] = ["strong", "br"],
): string => {
  if (!html) {
    return "";
  }

  // DOMParser creates an inert document, scripts and event handlers are not executed
  const document = new DOMParser().parseFromString(html, "text/html");

  return Array.from(document.body.childNodes)
    .map((node) => sanitizeNode(node, allowedTags))
    .join("");
};
