
import { auth, db } from "./firebase.js";

import {
    signInWithEmailAndPassword,
    signOut
} from "https://www.gstatic.com/firebasejs/12.0.0/firebase-auth.js";

import {
    doc,
    getDoc
} from "https://www.gstatic.com/firebasejs/12.0.0/firebase-firestore.js";

// Get HTML elements
const loginForm = document.getElementById("adminLoginForm");
const emailInput = document.getElementById("email");
const passwordInput = document.getElementById("password");
const loginButton = document.getElementById("loginButton");
const errorMessage = document.getElementById("errorMessage");

// Display error messages
function showError(message) {
    errorMessage.textContent = message;
    errorMessage.style.color = "#C1121F";
}

// Admin login
loginForm.addEventListener("submit", async (event) => {

    event.preventDefault();

    const email = emailInput.value.trim();
    const password = passwordInput.value;

    errorMessage.textContent = "";
    loginButton.disabled = true;
    loginButton.textContent = "Logging in...";

    try {

        // Sign in using Firebase Authentication
        const userCredential = await signInWithEmailAndPassword(
            auth,
            email,
            password
        );

        const user = userCredential.user;

        // Find the user's Firestore document
        const userRef = doc(db, "users", user.uid);
        const userSnapshot = await getDoc(userRef);

        // Check whether the user document exists
        if (!userSnapshot.exists()) {

            await signOut(auth);

            showError(
                "Access denied. Administrator account was not found."
            );

            return;
        }

        // Check administrator role
        const userData = userSnapshot.data();
        const role = String(userData.role || "").trim().toLowerCase();

        if (role !== "admin") {

            await signOut(auth);

            showError(
                "Access denied. You do not have administrator permissions."
            );

            return;
        }

        // Administrator verified
        window.location.href = "admin-dashboard.html";

    } catch (error) {

        console.error("Admin login error:", error);

        if (
            error.code === "auth/invalid-credential" ||
            error.code === "auth/wrong-password" ||
            error.code === "auth/user-not-found"
        ) {
            showError("Incorrect email address or password.");

        } else if (error.code === "auth/invalid-email") {
            showError("Please enter a valid email address.");

        } else if (error.code === "auth/too-many-requests") {
            showError("Too many attempts. Please try again later.");

        } else {
            showError("Login failed: " + error.message);
        }

    } finally {

        loginButton.disabled = false;
        loginButton.textContent = "Login";
    }
});
