import { initializeApp } from 'firebase/app';
import {
    getFirestore,
    collection,
    onSnapshot,
    query
} from 'firebase/firestore';

const firebaseConfig = {
    apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
    authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
    projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
    storageBucket: import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
    messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
    appId: import.meta.env.VITE_FIREBASE_APP_ID,
    measurementId: import.meta.env.VITE_FIREBASE_MEASUREMENT_ID,
};

let app = null;
let db = null;

if (firebaseConfig.apiKey && firebaseConfig.projectId) {
    try {
        app = initializeApp(firebaseConfig);
        db = getFirestore(app);
        console.log('[Firebase] Initialized real-time Firestore client for:', firebaseConfig.projectId);
    } catch (err) {
        console.warn('[Firebase] Initialization warning:', err);
    }
}

/**
 * Listen to real-time changes in the 'emergencies' collection in Cloud Firestore.
 * 
 * @param {Function} onUpdate Callback (type: 'added'|'modified'|'removed', data: Object)
 * @returns {Function} Unsubscribe function
 */
export function listenToEmergencies(onUpdate) {
    if (!db) {
        console.warn('[Firebase] Firestore is not initialized. Check VITE_FIREBASE_* in .env.');
        return () => {};
    }

    try {
        const emergenciesRef = collection(db, 'emergencies');
        const q = query(emergenciesRef);

        const unsubscribe = onSnapshot(q, (snapshot) => {
            snapshot.docChanges().forEach((change) => {
                const data = change.doc.data();
                // Ensure id matches the document key
                const rawDocId = change.doc.id;
                data.id = isNaN(Number(rawDocId)) ? rawDocId : Number(rawDocId);
                onUpdate(change.type, data);
            });
        }, (error) => {
            console.warn('[Firebase] Firestore onSnapshot error:', error);
        });

        return unsubscribe;
    } catch (err) {
        console.warn('[Firebase] Failed to attach listener:', err);
        return () => {};
    }
}

export { app, db };
window.FirebaseClient = { app, db, listenToEmergencies };

