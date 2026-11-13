import { auth, db } from "./firebase.js";

import {
    signInWithEmailAndPassword,
    signOut
} from "firebase/auth";

import {
    doc,
    getDoc
} from "firebase/firestore";

const loginForm = document.getElementById("adminLoginForm");
const loginButton = document.getElementById("loginButton");
const errorMessage = document.getElementById("errorMessage");

loginForm.addEventListener("submit", async function(e) {
    e.preventDefault();

    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value;

    errorMessage.textContent = "";
    loginButton.disabled = true;
    loginButton.textContent = "Checking...";

    try {
        // Sign in using Firebase Authentication
        const userCredential = await signInWithEmailAndPassword(
            auth,
            email,
            password
        );

        const user = userCredential.user;

        // Find the user's document in Firestore
        const userRef = doc(db, "users", user.uid);
        const userSnapshot = await getDoc(userRef);

        // Check whether the user document exists
        if (!userSnapshot.exists()) {
            await signOut(auth);
            throw new Error("Admin profile was not found.");
        }

        const userData = userSnapshot.data();

        // Verify administrator role
        if (
            String(userData.role || "").trim().toLowerCase() !== "admin"
        ) {
            await signOut(auth);
            throw new Error("Access denied. Admin accounts only.");
        }

        // Administrator verified
        window.location.href = "admin-dashboard.html";

    } catch (error) {
        console.error("Admin Login Error:", error);

        errorMessage.textContent =
            error.message || "Login failed. Please try again.";

    } finally {
        loginButton.disabled = false;
        loginButton.textContent = "Login";
    }
});