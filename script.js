/*dashboard*/
function openNav(){
    document.getElementById("sidebar").style.width="250px";
}

function closeNav(){
    document.getElementById("sidebar").style.width="0";
}

import { initializeApp } from "firebase/app";
import { getAnalytics } from "firebase/analytics";
import { getAuth, signOut } from "firebase/auth";
import { getFirestore } from "firebase/firestore";

// Firebase configuration
const firebaseConfig = {
    apiKey: "AIzaSyDypYuq6aZCv8P_UPknT-azegzxPlj5NcU",
    authDomain: "pillpoint-2196.firebaseapp.com",
    projectId: "pillpoint-2196",
    storageBucket: "pillpoint-2196e.firebasestorage.app",
    messagingSenderId: "658611379065",
    appId: "1:658611379065:web:4de653777dc0a40b122da8",
    measurementId: "G-M758XL33YB"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);

// Initialize Firebase services
const analytics = getAnalytics(app);
export const auth = getAuth(app);
export const db = getFirestore(app);

// Global Logout Handler
window.handleLogout = async function(e) {
    if (e) e.preventDefault();

    try {
        await signOut(auth);

        localStorage.clear();

        window.location.href = "login.html";

    } catch (error) {
        console.error("Logout Error:", error);
        alert("Unable to log out. Please try again.");
    }
};