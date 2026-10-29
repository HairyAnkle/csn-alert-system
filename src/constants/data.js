// Table 1.5 — 5 Emergency Alert Types
export const EMERGENCY_TYPES = [
  { key: 'medical_crisis',  label: 'Medical Crisis',           shortLabel: 'Medical',            icon: 'medkit' },
  { key: 'seizure_sensory', label: 'Seizure / Sensory Overload', shortLabel: 'Seizure / Overload', icon: 'pulse' },
  { key: 'fire',            label: 'Fire Emergency',           shortLabel: 'Fire',               icon: 'flame' },
  { key: 'wandering',       label: 'Wandering / Missing',      shortLabel: 'Wandering / Missing',icon: 'walk' },
  { key: 'general',         label: 'General Emergency',        shortLabel: 'General',            icon: 'warning' },
];

// Table 1.7 — Disability Classifications (Dropdown & Chips)
export const DISABILITY_CHIPS = [
  'Autism Spectrum Disorder (ASD)',
  'Down Syndrome',
  'Cerebral Palsy',
  'ADHD',
  'Hearing Impairment',
  'Visual Impairment',
  'Speech & Language Disorder',
  'Intellectual Disability',
  'Others',
];

export const COMMUNICATION_TAGS = [
  'Non-verbal',
  'Sound sensitivity',
  'Uses AAC / picture cards',
  'Touch sensitivity',
  'Calms with gentle voice',
];

// Table 1.6 — Nearby Emergency Centers for Live Map View
export const NEARBY_EMERGENCY_CENTERS = [
  {
    id: 'csn-center',
    name: 'CSN Center Parañaque (Center for Children with Special Needs)',
    type: 'LGU Facility • CSWDD/CSWDO & PDAO',
    role: 'LGU Facility • CSWDD/CSWDO & PDAO',
    latitude: 14.4997,
    longitude: 120.9949,
    lat: 14.4997,
    lng: 120.9949,
    phone: '0288207783',
    address: 'Col. E. de Leon Street, Barangay Sto. Niño, Parañaque City',
    color: '#005B96',
    icon: 'heart-circle',
  },
  {
    id: 'ospital',
    name: 'Ospital ng Parañaque (OSPAR 1)',
    type: 'Nearest Public Emergency Hospital',
    role: 'Nearest Public Emergency Hospital',
    latitude: 14.4991,
    longitude: 120.9923,
    lat: 14.4991,
    lng: 120.9923,
    phone: '0288254902',
    address: 'Quirino Ave, Brgy. La Huerta, Parañaque City',
    color: '#10B981',
    icon: 'medical',
  },
  {
    id: 'pdrrmo',
    name: 'Parañaque DRRMO / 911 Command & CSWDO',
    type: '24/7 Emergency Dispatch & Ambulance',
    role: '24/7 Emergency Dispatch & Ambulance',
    latitude: 14.4796,
    longitude: 121.0194,
    lat: 14.4796,
    lng: 121.0194,
    phone: '911',
    address: 'Parañaque City Hall Compound, San Antonio Valley 1, Parañaque City',
    color: '#E11D48',
    icon: 'medkit',
  },
];

export const STATUS_STYLE = {
  pending:    { label: 'Pending',      bg: '#FEF3C7', fg: '#92400E' },
  responding: { label: 'Acknowledged / Responding', bg: '#DBEAFE', fg: '#1E40AF' },
  resolved:   { label: 'Resolved',     bg: '#D1FAE5', fg: '#065F46' },
  cancelled:  { label: 'Cancelled',    bg: '#E5E7EB', fg: '#374151' },
};
