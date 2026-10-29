/* ============================================================================
 * DEV TESTING MOCK DATA & FALLBACKS (Active ONLY when IS_DEV is true)
 * Provides seamless phone testing on iPhone Safari, Android, and offline devices.
 * In deployment (!IS_DEV), this mock layer is completely bypassed.
 * ========================================================================== */

export const devState = {
  guardian: {
    id: 999,
    name: 'Maria Garcia',
    email: 'guardian@csn.test',
    phone: '09171234567',
    role: 'guardian',
  },
  child: {
    id: 101,
    name: 'Mateo Garcia',
    age: 8,
    disability: 'Autism Spectrum Disorder (ASD)',
    allergies: 'Peanuts, Penicillin',
    medications: 'Cetirizine 5mg (as needed)',
    communication_notes: 'Non-verbal • Sound sensitivity, responds best to visual picture cards',
    guardian_phone: '09171234567',
    emergency_contact_name: 'Carlos Garcia',
    emergency_contact_phone: '09187654321',
  },
  emergencies: [
    {
      id: 'EMG-101',
      type: 'medical_crisis',
      urgency: 'high',
      status: 'resolved',
      message: 'Difficulty breathing and high fever at home.',
      lat: 14.4793,
      lng: 121.0198,
      location: 'Brgy. San Antonio, Parañaque City',
      reported_at: new Date(Date.now() - 86400000 * 3).toISOString(),
      responder: { name: 'Officer D. Ramos', unit: 'Parañaque PDRRMO Unit 2', eta: 'Arrived', phone: '09178889911' },
    },
    {
      id: 'EMG-102',
      type: 'seizure_sensory',
      urgency: 'high',
      status: 'resolved',
      message: 'Severe sensory overload in public area, non-verbal.',
      lat: 14.4841,
      lng: 121.0152,
      location: 'Parañaque City Hall Compound',
      reported_at: new Date(Date.now() - 86400000 * 10).toISOString(),
      responder: { name: 'Dr. C. Mendoza', unit: 'City Health Office Team 1', eta: 'Arrived', phone: '09175554433' },
    },
  ],
  announcements: [
    {
      id: 1,
      category: 'Emergency Drill',
      title: 'Upcoming City-Wide CSN Emergency Drill',
      body: 'Parañaque DRRMO and CSN Center will conduct a scheduled emergency response simulation this Friday at 9:00 AM. Guardians may receive a test notification.',
      created_at: new Date().toISOString(),
    },
    {
      id: 2,
      category: 'Admin Announcement',
      title: 'CSN Center Free Therapy & Consultation',
      body: 'Free developmental pediatric consultation and sensory therapy sessions are available this Thursday at the Parañaque CSN Center from 8:00 AM to 3:00 PM.',
      created_at: new Date(Date.now() - 86400000 * 2).toISOString(),
    },
    {
      id: 3,
      category: 'App Update',
      title: 'CSN Alert App v2.4 — Live Location Sharing',
      body: 'New real-time GPS coordinate streaming and urgency level selection have been enabled for faster responder dispatch.',
      created_at: new Date(Date.now() - 86400000 * 5).toISOString(),
    },
  ],
  supportMessages: [
    {
      id: 'msg-welcome',
      sender: 'support',
      sender_name: 'CSN Center Parañaque Desk',
      body: 'Mabuhay! Welcome to CSN Center Parañaque Live Support (Col. E. de Leon St., Brgy. Sto. Niño). How can our CSWDD / PDAO team assist you and your child today?',
      created_at: new Date().toISOString(),
    },
  ],
  activeEmergency: null,
};

export function generateSmartSupportReply(userText, child) {
  const lower = (userText || '').toLowerCase();
  const childName = child && child.name ? child.name : 'your child';

  if (lower.includes('callback') || lower.includes('call me') || lower.includes('phone') || lower.includes('tawag')) {
    return `We have logged a priority callback request for ${childName}'s caregiver. A CSN Social Welfare Coordinator at (02) 8820-7783 will call your registered number shortly.`;
  }
  if (lower.includes('schedule') || lower.includes('address') || lower.includes('where') || lower.includes('visit') || lower.includes('therapy') || lower.includes('appointment')) {
    return 'CSN Center Parañaque is located at Col. E. de Leon Street, Barangay Sto. Niño, Parañaque City (partnered with CSWDD/CSWDO & PDAO). Office hours are Monday–Friday, 8:00 AM – 5:00 PM. Would you like us to reserve a consultation slot?';
  }
  if (lower.includes('sensory') || lower.includes('meltdown') || lower.includes('autism') || lower.includes('asd') || lower.includes('seizure')) {
    return `For ${childName}'s sensory or medical needs: please move to a quiet, low-stimulation space. If you need responder assistance without loud sirens, trigger a Low or High Urgency "Seizure / Sensory Overload" alert from the Home tab so our team approaches calmly.`;
  }
  if (lower.includes('profile') || lower.includes('medical') || lower.includes('allerg') || lower.includes('medication')) {
    return `You can update ${childName}'s Disability Classification, allergies, medications, and communication tags anytime in the "Medical Info" tab. Changes sync immediately to the CSN Admin Dashboard.`;
  }
  return `Thank you for your message. Your inquiry for ${childName} has been forwarded to the CSN Center Parañaque Help Desk (Brgy. Sto. Niño). A coordinator will reply here in real time. For urgent emergencies, please use the red SOS button or dial 911.`;
}

export function getDevMockResponse(path, { method = 'GET', body = {} } = {}) {
  const normPath = (path || '').split('?')[0];

  if (
    normPath === '/auth/login' ||
    normPath === '/auth/register' ||
    normPath === '/auth/google' ||
    normPath === '/auth/phone' ||
    normPath === '/auth/firebase'
  ) {
    if (body?.name) devState.guardian.name = body.name;
    if (body?.email) devState.guardian.email = body.email;
    if (body?.phone) devState.guardian.phone = body.phone;
    return {
      token: 'dev-token-' + Date.now(),
      guardian: { ...devState.guardian },
      child: { ...devState.child },
      user: { ...devState.guardian },
    };
  }

  if (normPath === '/auth/logout') {
    return { message: 'Logged out successfully.' };
  }

  if (normPath === '/auth/forgot-password') {
    return { message: 'Password reset link sent to your email address.' };
  }

  if (normPath === '/me') {
    return {
      guardian: { ...devState.guardian },
      child: { ...devState.child },
      user: { ...devState.guardian },
    };
  }

  if (normPath === '/child' && method === 'PUT') {
    if (body) {
      devState.child = { ...devState.child, ...body };
    }
    return { child: { ...devState.child } };
  }

  if (normPath === '/emergencies' && method === 'POST') {
    const newEmg = {
      id: 'EMG-' + Math.floor(1000 + Math.random() * 9000),
      type: body?.type || 'medical_crisis',
      urgency: body?.urgency || 'high',
      message: body?.message || '',
      lat: body?.lat ?? 14.4793,
      lng: body?.lng ?? 121.0198,
      location: body?.lat && body?.lng ? `Live GPS (${Number(body.lat).toFixed(5)}, ${Number(body.lng).toFixed(5)})` : 'Parañaque City',
      status: 'pending',
      reported_at: new Date().toISOString(),
      responder: {
        name: 'Officer J. Reyes',
        unit: 'Parañaque PDRRMO Unit 3',
        eta: body?.urgency === 'low' ? '10-15 mins' : '4-6 mins',
        phone: '09179110003',
      },
    };
    devState.activeEmergency = newEmg;
    devState.emergencies = [newEmg, ...devState.emergencies];
    return { emergency: newEmg };
  }

  if (normPath.startsWith('/emergencies/') && normPath.endsWith('/cancel')) {
    if (devState.activeEmergency) {
      devState.activeEmergency.status = 'cancelled';
    }
    return {
      emergency: devState.activeEmergency || { id: 'EMG-DEV', status: 'cancelled' },
    };
  }

  if (normPath.startsWith('/emergencies/') && normPath.endsWith('/location')) {
    if (devState.activeEmergency && body?.lat && body?.lng) {
      devState.activeEmergency.lat = body.lat;
      devState.activeEmergency.lng = body.lng;
    }
    return { status: 'location updated', emergency: devState.activeEmergency };
  }

  if (normPath.startsWith('/emergencies/') && method === 'GET') {
    if (devState.activeEmergency) {
      devState.activeEmergency.status = 'responding';
      return { emergency: { ...devState.activeEmergency } };
    }
    return {
      emergency: {
        id: 'EMG-DEV',
        status: 'responding',
        type: 'medical_crisis',
        urgency: 'high',
        reported_at: new Date().toISOString(),
        responder: { name: 'Officer J. Reyes', unit: 'Parañaque PDRRMO Unit 3', eta: '3 mins' },
      },
    };
  }

  if (normPath === '/emergencies' && method === 'GET') {
    return { emergencies: [...devState.emergencies] };
  }

  if (normPath === '/announcements' && method === 'GET') {
    return { announcements: [...devState.announcements] };
  }

  if (normPath === '/support/messages' && method === 'GET') {
    return {
      data: [...devState.supportMessages],
      messages: [...devState.supportMessages],
    };
  }

  if (normPath === '/support/messages' && method === 'POST') {
    const text = (body?.body || body?.message || '').trim();
    const userMsg = {
      id: 'msg-u-' + Date.now(),
      sender: 'guardian',
      sender_name: devState.guardian.name || 'Guardian',
      body: text,
      text: text,
      created_at: new Date().toISOString(),
    };
    const replyMsg = {
      id: 'msg-s-' + (Date.now() + 1),
      sender: 'support',
      sender_name: 'Parañaque CSN Desk',
      body: generateSmartSupportReply(text, devState.child),
      text: generateSmartSupportReply(text, devState.child),
      created_at: new Date().toISOString(),
    };
    devState.supportMessages = [...devState.supportMessages, userMsg, replyMsg];
    return {
      data: userMsg,
      reply: replyMsg,
      message: 'Message sent.',
      messages: [...devState.supportMessages],
    };
  }

  return { ok: true };
}
