/**
 * Pure helpers for schedules, so they can be tested without a browser.
 */

/** The share of a pick done, 0-100. */
export function percent(done, count) {
  if (!count) return 0;
  return Math.round((Math.min(Math.max(done, 0), count) / count) * 100);
}

/**
 * `parts` sections spread evenly over the month `month` (YYYY-MM-01), each due
 * on its last day, labelled `label(n)`.
 */
export function splitMonth(month, parts, label) {
  const [y, m] = month.split('-').map(Number);
  const days = new Date(Date.UTC(y, m, 0)).getUTCDate();
  const out = [];

  for (let n = 1; n <= parts; n++) {
    const day = Math.round((days * n) / parts);
    out.push({ label: label(n), due: `${y}-${String(m).padStart(2, '0')}-${String(day).padStart(2, '0')}` });
  }

  return out;
}

/**
 * Which section a member should be on: the first one they haven't finished
 * (or the last, once they are done). 1-based.
 */
export function current(done, count) {
  return Math.max(1, Math.min(count, done + 1));
}

/** The default section for a new reply: what the member last finished. */
export function replySection(done, count) {
  if (!count) return null;
  return Math.max(1, Math.min(count, done || 1));
}
