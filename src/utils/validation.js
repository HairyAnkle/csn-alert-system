export const evaluatePasswordStrength = (pwd) => {
  if (!pwd) return { score: 0, label: 'Enter password', color: '#94A3B8' };
  let s = 0;
  if (pwd.length >= 8) s += 1;
  if (pwd.length >= 11) s += 1;
  if (/[A-Z]/.test(pwd) && /[a-z]/.test(pwd)) s += 1;
  if (/[0-9]/.test(pwd) || /[^A-Za-z0-9]/.test(pwd)) s += 1;
  if (s <= 1) return { score: 1, label: 'Weak', color: '#E11D48' };
  if (s === 2) return { score: 2, label: 'Fair', color: '#F59E0B' };
  if (s === 3) return { score: 3, label: 'Good', color: '#0284C7' };
  return { score: 4, label: 'Strong', color: '#10B981' };
};

export const isValidEmail = (v) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim());
