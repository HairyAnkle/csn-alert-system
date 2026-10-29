export const FAQ_ITEMS = [
  {
    q: 'How does the 1-second Hold-to-Send SOS work?',
    a: 'To prevent accidental triggers in a pocket or bag, press and hold the red Hold-to-Send button for 1 full second, or tap "Send Alert" and confirm in the dialog.',
  },
  {
    q: 'What is the difference between High and Low Urgency?',
    a: 'High Urgency immediately alerts both the Parañaque CSN Center and Parañaque DRRMO / 911 Ambulance units for life-threatening crises, seizures, or missing children. Low Urgency notifies CSN center staff for non-life-threatening sensory or behavioral assistance.',
  },
  {
    q: 'Does my child’s medical profile get sent automatically?',
    a: 'Yes. As soon as you trigger an alert, responders see your child’s name, age, disability classification (e.g. ASD), allergies, medications, and communication notes (e.g. Non-verbal, sound sensitivity) so they approach without sirens if needed.',
  },
  {
    q: 'What if I lose internet or GPS signal during an emergency?',
    a: 'If your internet connection drops, use the red "Call 911" or "Call CSN Hotline" buttons at the top of Support & Active screens to dial over cellular voice.',
  },
];

export const SAFETY_RESOURCES = [
  {
    title: 'Sensory Overload & Meltdown De-escalation Protocol',
    tag: 'ASD / Sensory Guide',
    icon: 'ear-outline',
    color: '#005B96',
    steps: [
      'Move your child to a quiet, dimly lit space away from crowds.',
      'Offer noise-canceling headphones or a familiar comfort item.',
      'Use short, calm visual cues or AAC cards rather than complex verbal questions.',
      'If in public and unsafe, trigger a Low or High Urgency Seizure/Sensory Alert so responders arrive with sirens turned off.',
    ],
  },
  {
    title: 'Wandering / Elopement Emergency Checklist',
    tag: 'Missing Child Protocol',
    icon: 'walk-outline',
    color: '#E11D48',
    steps: [
      'Immediately trigger a "Wandering / Missing" High Urgency Alert with Live GPS enabled.',
      'Check nearby water hazards, busy intersections, and favorite sensory spots first.',
      'Share the child’s current clothing description in the Optional Situation Message box.',
    ],
  },
  {
    title: 'Pediatric Seizure First-Aid Guide',
    tag: 'Medical Crisis',
    icon: 'medkit-outline',
    color: '#10B981',
    steps: [
      'Ease the child gently to the floor and turn them onto their side to keep airways clear.',
      'Clear hard or sharp objects from the area; place something soft under their head.',
      'Time the seizure—if it lasts longer than 5 minutes, dispatch a High Urgency Medical Crisis alert immediately.',
    ],
  },
];

export const VIDEO_TUTORIALS = [
  {
    id: 'vid-1',
    title: 'How to Trigger an SOS Alert & Share Live GPS',
    duration: '1:45',
    summary: 'Walkthrough of selecting an Alert Type, choosing High vs. Low Urgency, and streaming GPS coordinates to Parañaque responders.',
    steps: [
      '0:00 — Tap "TRIGGER EMERGENCY ALERT" in the thumb zone on the Dashboard.',
      '0:30 — Choose the incident type (Medical, Seizure/Sensory, Fire, Wandering, or General).',
      '1:00 — Select High Urgency (notifies Ambulance) and hold the confirmation bar for 1 second.',
    ],
  },
  {
    id: 'vid-2',
    title: 'Setting Up Your Child’s CSN Medical & Sensory Profile',
    duration: '2:10',
    summary: 'How to configure Disability Classification, allergies, and sensory notes so emergency responders know how to communicate with your child.',
    steps: [
      '0:00 — Open the Medical Info tab from the bottom navigation bar.',
      '0:45 — Choose a Disability Classification from the dropdown (e.g., Autism Spectrum Disorder).',
      '1:30 — Tap quick-tags like "Non-verbal" and "Sound sensitivity", then tap "Save Changes".',
    ],
  },
  {
    id: 'vid-3',
    title: 'Using the Interactive Map & Nearby Emergency Centers',
    duration: '1:30',
    summary: 'How to locate the nearest Parañaque hospital, DRRMO command post, or CSN Center and start/stop live coordinate sharing.',
    steps: [
      '0:00 — Open the Live GPS tab.',
      '0:30 — Toggle "Live Coordinate Streaming" on or off at any time.',
      '1:00 — Tap any facility pin on the map to view distance in km, get directions, or call directly.',
    ],
  },
];

export const CHAT_QUICK_TOPICS = [
  'Request a Callback',
  'CSN Center Schedule & Address',
  'Sensory Overload Advice',
  'Update Child Medical Info',
];
