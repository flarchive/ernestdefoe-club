import app from 'flarum/forum/app';
import extractText from 'flarum/common/utils/extractText';

export const t = (key, params) => app.translator.trans(`ernestdefoe-club.forum.${key}`, params);
export const text = (key, params) => extractText(t(key, params));

export const monthName = (month) => (month ? dayjs(month).format('MMMM YYYY') : '');
export const dueDate = (due) => (due ? dayjs(due).format('ll') : null);

export const apiUrl = (path) => `${app.forum.attribute('apiUrl')}/${path}`;

/** "Section 2 · Chapters 6–10" */
export const sectionName = (pick, n) => {
  const section = (pick.sections() || [])[n - 1];
  return section ? text('section.name', { number: n, label: section.label }) : text('section.number', { number: n });
};
