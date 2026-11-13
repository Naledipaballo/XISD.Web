
/* ==========================================
   PILL POINT DELIVERY
   Shared Firebase Configuration
========================================== */

import { initializeApp } from "https://www.gstatic.com/firebasejs/12.0.0/firebase-app.js";

import {
    getAuth
} from "https://www.gstatic.com/firebasejs/12.0.0/firebase-auth.js";

import {
    getFirestore
} from "https://www.gstatic.com/firebasejs/12.0.0/firebase-firestore.js";

import {
    getStorage
} from "https://www.gstatic.com/firebasejs/12.0.0/firebase-storage.js";


/* Firebase Configuration */

const firebaseConfig = {
    apiKey: "AIzaSyDypYuq6aZCv8P_UPknT-azegzxPlj5NcU",
    authDomain: "pillpoint-2196e.firebaseapp.com",
    projectId: "pillpoint-2196e",
    storageBucket: "pillpoint-2196e.firebasestorage.app",
    messagingSenderId: "658611379065",
    appId: "1:658611379065:web:4de653777dc0a40b122da8",
    measurementId: "G-M758XL33YB"
};


/* Initialize Firebase */

const app = initializeApp(firebaseConfig);


/* Firebase Services */

export const auth = getAuth(app);

export const db = getFirestore(app);

export const storage = getStorage(app);

export default app;
