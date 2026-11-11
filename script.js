/*dashboard*/
function openNav(){
    document.getElementById("sidebar").style.width="250px";
}

function closeNav(){
    document.getElementById("sidebar").style.width="0";
}

// Import the functions you need from the SDKs you need
import { initializeApp } from "firebase/app";
import { getAnalytics } from "firebase/analytics";
import { getAuth, signOut } from "firebase/auth";
// Import Realtime Database SDK
import { getDatabase, ref, set, get, child } from "firebase/database";

// Your web app's Firebase configuration
const firebaseConfig = {
  apiKey: "AIzaSyDypYuq6aZCv8P_UPknT-azegzxPlj5NcU",
  authDomain: "pillpoint-2196e.firebaseapp.com",
  databaseURL: "https://pillpoint-2196e-default-rtdb.europe-west1.firebasedatabase.app",
  projectId: "pillpoint-2196e",
  storageBucket: "pillpoint-2196e.firebasestorage.app",
  messagingSenderId: "658611379065",
  appId: "1:658611379065:web:4de653777dc0a40b122da8",
  measurementId: "G-M758XL33YB"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const analytics = getAnalytics(app);

// Initialize and export Database and Auth for your other script files to use
export const db = getDatabase(app);
export const auth = getAuth(app);

// Global Logout Handler
window.handleLogout = async function(e) {
    if (e) e.preventDefault();

    try {
        await signOut(auth);
        localStorage.clear();
        window.location.href = 'login.html';
    } catch (error) {
        console.error("Logout Error: ", error);
        alert('Unable to log out. Please try again.');
    }
};