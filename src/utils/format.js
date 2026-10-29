export const formatDateTime = (iso) => {
  if (!iso) return '';
  const d = new Date(iso);
  if (isNaN(d.getTime())) return '';
  return d.toLocaleString('en-PH', {
    timeZone: 'Asia/Manila',
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  });
};

/** Formats child disability badge e.g. "ASD • Non-verbal" per Table 1.4 */
export function formatDisabilitySummary(child) {
  if (!child || !child.disability) return '';
  let shortDis = child.disability;
  if (/autism|asd/i.test(shortDis)) shortDis = 'ASD';
  const comm = child.communication_notes || '';
  if (/non-verbal|nonverbal/i.test(comm)) return `${shortDis} • Non-verbal`;
  if (/sound sensitivity/i.test(comm)) return `${shortDis} • Sound sensitivity`;
  return shortDis;
}
