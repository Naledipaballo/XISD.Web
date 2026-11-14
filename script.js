
import { auth } from "./firebase.js";

import {
    signOut
} from "https://www.gstatic.com/firebasejs/12.0.0/firebase-auth.js";

/* ==========================================
   PILL POINT DELIVERY - GLOBAL LOGOUT
========================================== */

window.handleLogout = async function (e) {
    if (e) {
        e.preventDefault();
    }

    try {
        await signOut(auth);
        localStorage.clear();
        window.location.href = "login.html";

    } catch (error) {
        console.error("Logout Error:", error);
        alert("Unable to log out. Please try again.");
    }
};


/* ==========================================
   PILL POINT DELIVERY - HAMBURGER MENU
========================================== */

// Open the sidebar
window.openNav = function () {
    const sidebar = document.getElementById("sidebar");

    if (sidebar) {
        sidebar.classList.add("sidebar-open");
    } else {
        console.error("Sidebar with ID 'sidebar' was not found.");
    }
};


// Close the sidebar
window.closeNav = function () {
    const sidebar = document.getElementById("sidebar");

    if (sidebar) {
        sidebar.classList.remove("sidebar-open");
    }
};


// Close the sidebar when the user clicks outside it
document.addEventListener("click", function (event) {
    const sidebar = document.getElementById("sidebar");
    const menuButton = document.querySelector(".menu-btn");

    if (!sidebar || !sidebar.classList.contains("sidebar-open")) {
        return;
    }

    if (
        !sidebar.contains(event.target) &&
        menuButton &&
        !menuButton.contains(event.target)
    ) {
        window.closeNav();
    }
});


// Close sidebar using the Escape key
document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
        window.closeNav();
    }
});
