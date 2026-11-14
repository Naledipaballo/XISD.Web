import { auth } from "./firebase.js";

import {
    signOut
} from "https://www.gstatic.com/firebasejs/12.0.0/firebase-auth.js";


/* ==========================================
   3. GLOBAL LOGOUT HANDLER
========================================== */

window.handleLogout = async function (e) {

    if (e) {
        e.preventDefault();
    }

    try {

        // Sign out the currently logged-in user
        await signOut(auth);

        // Clear saved local session information
        localStorage.clear();

        // Redirect to login page
        window.location.href = "login.html";

    } catch (error) {

        console.error("Logout Error:", error);

        alert("Unable to log out. Please try again.");

    }

    /* ==========================================
   PILL POINT DELIVERY
   CUSTOMER HAMBURGER MENU
========================================== */

// Open sidebar
window.openNav = function () {
    const sidebar = document.getElementById("sidebar");

    if (sidebar) {
        sidebar.style.width = "250px";
    } else {
        console.error("Sidebar element with ID 'sidebar' was not found.");
    }
};


// Close sidebar
window.closeNav = function () {
    const sidebar = document.getElementById("sidebar");

    if (sidebar) {
        sidebar.style.width = "0";
    }
};

};
