import { auth, db } from "./firebase.js";

import {
    collection,
    addDoc,
    doc,
    getDoc,
    updateDoc,
    deleteDoc,
    onSnapshot,
    serverTimestamp
} from "https://www.gstatic.com/firebasejs/12.0.0/firebase-firestore.js";

import {
    onAuthStateChanged,
    signOut
} from "https://www.gstatic.com/firebasejs/12.0.0/firebase-auth.js";

// Get HTML elements.
const pharmacyForm = document.getElementById("pharmacyForm");
const pharmacyTable = document.getElementById("pharmacyTable");
const totalPharmacies = document.getElementById("totalPharmacies");
const formMessage = document.getElementById("formMessage");
const tableMessage = document.getElementById("tableMessage");
const searchInput = document.getElementById("searchInput");
const saveBtn = document.getElementById("saveBtn");
const cancelBtn = document.getElementById("cancelBtn");
const formTitle = document.getElementById("formTitle");
const adminEmail = document.getElementById("adminEmail");

let pharmacies = [];
let currentAdmin = null;

// Check that the signed-in user is an administrator.
onAuthStateChanged(auth, async (user) => {
    if (!user) {
        window.location.href = "admin-login.html";
        return;
    }

    try {
        const userRef = doc(db, "users", user.uid);
        const userSnapshot = await getDoc(userRef);

        if (!userSnapshot.exists() ||
            userSnapshot.data().role !== "admin") {
            await signOut(auth);
            alert("Access denied. Administrator account required.");
            window.location.href = "admin-login.html";
            return;
        }

        currentAdmin = user;
        adminEmail.textContent = user.email || "Administrator";

        // Start listening for pharmacy changes.
        loadPharmacies();

    } catch (error) {
        console.error("Admin verification error:", error);
        alert("Unable to verify administrator access.");
        await signOut(auth);
        window.location.href = "admin-login.html";
    }
});

// Listen to the pharmacies collection in Firestore.
function loadPharmacies() {
    onSnapshot(
        collection(db, "pharmacies"),
        (snapshot) => {
            pharmacies = snapshot.docs.map((item) => ({
                id: item.id,
                ...item.data()
            }));

            pharmacies.sort((a, b) =>
                String(a.name || "").localeCompare(String(b.name || ""))
            );

            totalPharmacies.textContent = pharmacies.length;
            displayPharmacies();
        },
        (error) => {
            console.error("Pharmacy loading error:", error);
            pharmacyTable.innerHTML =
                '<tr><td colspan="5">Unable to load pharmacies. Check Firestore permissions.</td></tr>';
        }
    );
}

// Display pharmacies in the table.
function displayPharmacies() {
    const searchTerm = searchInput.value.trim().toLowerCase();

    const filtered = pharmacies.filter((pharmacy) => {
        const searchableText = [
            pharmacy.name,
            pharmacy.phone,
            pharmacy.email,
            pharmacy.city,
            pharmacy.province,
            pharmacy.address
        ].join(" ").toLowerCase();

        return searchableText.includes(searchTerm);
    });

    pharmacyTable.replaceChildren();

    if (filtered.length === 0) {
        const row = document.createElement("tr");
        const cell = document.createElement("td");

        cell.colSpan = 5;
        cell.textContent = pharmacies.length === 0
            ? "No pharmacies have been added yet."
            : "No matching pharmacies found.";

        row.appendChild(cell);
        pharmacyTable.appendChild(row);
        return;
    }

    filtered.forEach((pharmacy) => {
        const row = document.createElement("tr");

        const nameCell = document.createElement("td");
        nameCell.textContent = pharmacy.name || "Not provided";

        const contactCell = document.createElement("td");
        contactCell.textContent =
            `${pharmacy.phone || "No phone"}\n${pharmacy.email || ""}`;

        const locationCell = document.createElement("td");
        locationCell.textContent =
            `${pharmacy.address || ""}\n${pharmacy.city || ""}, ${pharmacy.province || ""}`;

        const hoursCell = document.createElement("td");
        hoursCell.textContent = pharmacy.operatingHours || "Not provided";

        const actionsCell = document.createElement("td");

        const editButton = document.createElement("button");
        editButton.type = "button";
        editButton.className = "edit-btn";
        editButton.textContent = "Edit";
        editButton.addEventListener("click", () => editPharmacy(pharmacy.id));

        const deleteButton = document.createElement("button");
        deleteButton.type = "button";
        deleteButton.className = "delete-btn";
        deleteButton.textContent = "Delete";
        deleteButton.addEventListener("click", () => deletePharmacy(pharmacy.id));

        actionsCell.append(editButton, deleteButton);
        row.append(nameCell, contactCell, locationCell, hoursCell, actionsCell);
        pharmacyTable.appendChild(row);
    });
}

// Search pharmacies.
searchInput.addEventListener("input", displayPharmacies);

// Read and validate form values.
function getFormData() {
    const name = document.getElementById("pharmacyName").value.trim();
    const phone = document.getElementById("phone").value.trim();
    const email = document.getElementById("email").value.trim();
    const province = document.getElementById("province").value;
    const city = document.getElementById("city").value.trim();
    const operatingHours =
        document.getElementById("operatingHours").value.trim();
    const address = document.getElementById("address").value.trim();

    const latitudeInput = document.getElementById("latitude").value.trim();
    const longitudeInput = document.getElementById("longitude").value.trim();

    let latitude = null;
    let longitude = null;

    if (latitudeInput !== "" || longitudeInput !== "") {
        if (latitudeInput === "" || longitudeInput === "") {
            throw new Error("Enter both latitude and longitude.");
        }

        latitude = Number(latitudeInput);
        longitude = Number(longitudeInput);

        if (!Number.isFinite(latitude) || latitude < -90 || latitude > 90) {
            throw new Error("Enter a valid latitude between -90 and 90.");
        }

        if (!Number.isFinite(longitude) || longitude < -180 || longitude > 180) {
            throw new Error("Enter a valid longitude between -180 and 180.");
        }
    }

    if (!name || !phone || !province || !city || !address) {
        throw new Error("Please complete all required fields.");
    }

    return {
        name,
        phone,
        email,
        province,
        city,
        operatingHours,
        address,
        latitude,
        longitude
    };
}

// Add a new pharmacy or update an existing one.
pharmacyForm.addEventListener("submit", async (event) => {
    event.preventDefault();

    if (!currentAdmin) {
        formMessage.textContent = "Administrator access is required.";
        return;
    }

    saveBtn.disabled = true;
    formMessage.textContent = "Saving pharmacy...";

    try {
        const pharmacyData = getFormData();
        const pharmacyId = document.getElementById("pharmacyId").value;

        if (pharmacyId) {
            // Update existing pharmacy.
            await updateDoc(doc(db, "pharmacies", pharmacyId), {
                ...pharmacyData,
                updatedAt: serverTimestamp(),
                updatedBy: currentAdmin.uid
            });

            formMessage.textContent = "Pharmacy updated successfully.";

        } else {
            // Add new pharmacy.
            await addDoc(collection(db, "pharmacies"), {
                ...pharmacyData,
                createdAt: serverTimestamp(),
                createdBy: currentAdmin.uid
            });

            formMessage.textContent = "Pharmacy added successfully.";
        }

        resetForm();

    } catch (error) {
        console.error("Pharmacy save error:", error);
        formMessage.textContent =
            error.message || "Unable to save pharmacy.";

    } finally {
        saveBtn.disabled = false;
    }
});

// Load pharmacy information into the form for editing.
function editPharmacy(pharmacyId) {
    const pharmacy = pharmacies.find((item) => item.id === pharmacyId);

    if (!pharmacy) return;

    document.getElementById("pharmacyId").value = pharmacy.id;
    document.getElementById("pharmacyName").value = pharmacy.name || "";
    document.getElementById("phone").value = pharmacy.phone || "";
    document.getElementById("email").value = pharmacy.email || "";
    document.getElementById("province").value = pharmacy.province || "";
    document.getElementById("city").value = pharmacy.city || "";
    document.getElementById("operatingHours").value =
        pharmacy.operatingHours || "";
    document.getElementById("address").value = pharmacy.address || "";
    document.getElementById("latitude").value =
        pharmacy.latitude ?? "";
    document.getElementById("longitude").value =
        pharmacy.longitude ?? "";

    formTitle.textContent = "Edit Pharmacy";
    saveBtn.textContent = "Update Pharmacy";
    cancelBtn.hidden = false;
    formMessage.textContent = "Editing " + (pharmacy.name || "pharmacy");

    window.scrollTo({ top: 0, behavior: "smooth" });
}

// Delete a pharmacy.
async function deletePharmacy(pharmacyId) {
    const pharmacy = pharmacies.find((item) => item.id === pharmacyId);

    if (!pharmacy) return;

    const confirmed = confirm(
        `Are you sure you want to delete ${pharmacy.name}?`
    );

    if (!confirmed) return;

    try {
        await deleteDoc(doc(db, "pharmacies", pharmacyId));
        tableMessage.textContent = "Pharmacy deleted successfully.";

    } catch (error) {
        console.error("Pharmacy deletion error:", error);
        tableMessage.textContent =
            "Unable to delete pharmacy. Check your Firestore rules.";
    }
}

// Clear the form and return to Add Pharmacy mode.
function resetForm() {
    pharmacyForm.reset();
    document.getElementById("pharmacyId").value = "";
    formTitle.textContent = "Add New Pharmacy";
    saveBtn.textContent = "Add Pharmacy";
    cancelBtn.hidden = true;
}

cancelBtn.addEventListener("click", () => {
    resetForm();
    formMessage.textContent = "";
});

// Log out the administrator.
document.getElementById("logoutBtn").addEventListener("click", async (event) => {
    event.preventDefault();

    try {
        await signOut(auth);
        window.location.href = "admin-login.html";
    } catch (error) {
        console.error("Logout error:", error);
        alert("Unable to log out. Please try again.");
    }
});