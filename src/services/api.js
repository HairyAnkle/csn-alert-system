import { IS_DEV, getApiBaseUrl } from '../config/env';
import { getDevMockResponse } from './devMock';

/* ============================================================================
 * API CONTRACT (build these in Laravel, all JSON, Bearer-token auth)
 *
 *  POST   /auth/login               {email, password}        -> {token, guardian:{name}, child}
 *  POST   /auth/register            {name, email, password}  -> {token, guardian:{name}, child}
 *  POST   /auth/forgot-password     {email}                  -> {message}
 *  GET    /me                                                -> {guardian:{name}, child}
 *  PUT    /child                    {medical profile fields} -> {child}
 *  POST   /emergencies              {type, lat, lng}         -> {emergency}
 *  GET    /emergencies/{id}                                  -> {emergency}
 *  PATCH  /emergencies/{id}/location {lat, lng}              -> {emergency}
 *  POST   /emergencies/{id}/cancel                           -> {emergency}
 *  GET    /emergencies                                       -> {emergencies:[...]}
 *  GET    /announcements                                     -> {announcements:[...]}
 *
 *  child:      { name, age, disability, allergies, medications,
 *                communication_notes, guardian_phone,
 *                emergency_contact_name, emergency_contact_phone }
 *  emergency:  { id, type, status: 'pending'|'responding'|'resolved'|'cancelled',
 *                responder: null | {name, unit, eta}, reported_at }
 *  announcement: { id, title, body, created_at }
 * ========================================================================== */

export async function api(path, { method = 'GET', body, token } = {}) {
  const baseUrl = getApiBaseUrl();
  let res;
  try {
    res = await fetch(`${baseUrl}${path}`, {
      method,
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
      },
      body: body ? JSON.stringify(body) : undefined,
    });
  } catch (e) {
    console.warn(`[API] Failed to fetch ${baseUrl}${path}:`, e);
    if (IS_DEV) {
      console.log(`[DEV TEST MODE] Backend unreachable at ${baseUrl}. Serving simulated response for ${path}`);
      return getDevMockResponse(path, { method, body });
    }
    throw new Error('Cannot reach the server. Check your internet connection.');
  }
  let data = null;
  try {
    data = await res.json();
  } catch {
    /* empty or non-JSON body */
  }
  if (!res.ok) {
    if (IS_DEV && (res.status >= 500 || (res.status === 404 && path.startsWith('/support/messages')))) {
      console.log(`[DEV TEST MODE] Fallback for ${path} (${res.status})`);
      return getDevMockResponse(path, { method, body });
    }
    const err = new Error((data && data.message) || `Request failed (${res.status})`);
    err.errors = data && data.errors;
    throw err;
  }
  return data;
}
