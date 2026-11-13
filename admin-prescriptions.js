import { auth, db } from "./firebase.js";

import {
    onAuthStateChanged,
    signOut
} from "https://www.gstatic.com/firebasejs/12.0.0/firebase-auth.js";

import {
    collection,
    doc,
    getDoc,
    onSnapshot,
    query,
    orderBy,
    updateDoc,
    addDoc,
    serverTimestamp
} from "https://www.gstatic.com/firebasejs/12.0.0/firebase-firestore.js";


/* ==========================================
   HTML ELEMENTS
========================================== */

const totalPrescriptions =
    document.getElementById("totalPrescriptions");

const pendingPrescriptions =
    document.getElementById("pendingPrescriptions");

const approvedPrescriptions =
    document.getElementById("approvedPrescriptions");

const rejectedPrescriptions =
    document.getElementById("rejectedPrescriptions");

const prescriptionsTableBody =
    document.getElementById("prescriptionsTableBody");

const loadingMessage =
    document.getElementById("loadingMessage");

const errorMessage =
    document.getElementById("errorMessage");

const searchPrescription =
    document.getElementById("searchPrescription");

const prescriptionModal =
    document.getElementById("prescriptionModal");

const prescriptionDetails =
    document.getElementById("prescriptionDetails");

const fileSection =
    document.getElementById("fileSection");

const closeModal =
    document.getElementById("closeModal");

const approveButton =
    document.getElementById("approveButton");

const rejectButton =
    document.getElementById("rejectButton");

const rejectionReason =
    document.getElementById("rejectionReason");

const reviewMessage =
    document.getElementById("reviewMessage");

const logoutLink =
    document.getElementById("logoutLink");


/* ==========================================
   GLOBAL VARIABLES
========================================== */

let allPrescriptions = [];
let selectedPrescription = null;
let currentAdmin = null;
let unsubscribePrescriptions = null;


/* ==========================================
   HELPER FUNCTIONS
========================================== */

// Safely display text in the HTML.
function safeText(value) {
    return String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}


// Format Firestore dates.
function formatDate(value) {

    if (!value) {
        return "Not available";
    }

    let date;

    if (value.toDate) {
        date = value.toDate();
    } else {
        date = new Date(value);
    }

    if (Number.isNaN(date.getTime())) {
        return "Not available";
    }

    return date.toLocaleString("en-ZA", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit"
    });
}


// Get prescription status.
function getStatus(prescription) {

    const status = String(
        prescription.status || "Pending"
    ).toLowerCase();

    if (status === "approved") {
        return "Approved";
    }

    if (status === "rejected") {
        return "Rejected";
    }

    return "Pending";
}


// Get customer name.
function getCustomerName(prescription) {

    return (
        prescription.customerName ||
        prescription.fullName ||
        prescription.name ||
        prescription.userName ||
        prescription.email ||
        "Unknown customer"
    );
}


// Get prescription number.
function getPrescriptionNumber(prescription, id) {

    return (
        prescription.prescriptionNumber ||
        prescription.prescriptionNo ||
        prescription.referenceNumber ||
        id
    );
}


// Get prescription file URL.
function getFileUrl(prescription) {

    return (
        prescription.fileUrl ||
        prescription.downloadURL ||
        prescription.downloadUrl ||
        prescription.imageUrl ||
        prescription.imageURL ||
        ""
    );
}


// Display errors.
function showError(message) {

    if (errorMessage) {
        errorMessage.textContent = message;
    }

    console.error(message);
}


/* ==========================================
   ADMIN AUTHENTICATION
========================================== */

onAuthStateChanged(auth, async (user) => {

    try {

        if (!user) {

            window.location.href = "admin-login.html";
            return;
        }

        currentAdmin = user;

        // Get admin profile from Firestore.
        const adminRef = doc(db, "users", user.uid);

        const adminSnapshot = await getDoc(adminRef);

        if (!adminSnapshot.exists()) {

            await signOut(auth);

            alert("Access denied. Admin profile not found.");

            window.location.href = "admin-login.html";
            return;
        }

        const adminData = adminSnapshot.data();

        // Check admin role.
        if (String(adminData.role || "").toLowerCase() !== "admin") {

            await signOut(auth);

            alert("Access denied. Administrator privileges required.");

            window.location.href = "admin-login.html";
            return;
        }

        // Admin verified. Load prescriptions.
        loadPrescriptions();

    } catch (error) {

        console.error("Admin authentication error:", error);

        showError(
            "Unable to verify administrator access: " +
            (error.message || error.code || "Unknown error")
        );
    }
});


/* ==========================================
   LOAD PRESCRIPTIONS FROM FIRESTORE
========================================== */

function loadPrescriptions() {

    loadingMessage.textContent = "Loading prescriptions...";

    errorMessage.textContent = "";

    const prescriptionsRef = collection(db, "prescriptions");

    const prescriptionsQuery = query(
        prescriptionsRef,
        orderBy("createdAt", "desc")
    );

    unsubscribePrescriptions = onSnapshot(
        prescriptionsQuery,

        (snapshot) => {

            allPrescriptions = [];

            snapshot.forEach((documentSnapshot) => {

                allPrescriptions.push({
                    id: documentSnapshot.id,
                    ...documentSnapshot.data()
                });

            });

            loadingMessage.textContent = "";

            updateSummary();

            displayPrescriptions();

            // Refresh the open modal if its prescription changes.
            if (selectedPrescription) {

                const updatedPrescription = allPrescriptions.find(
                    item => item.id === selectedPrescription.id
                );

                if (updatedPrescription) {
                    selectedPrescription = updatedPrescription;
                    openPrescriptionDetails(updatedPrescription);
                }
            }

        },

        (error) => {

            console.error("Prescription loading error:", error);

            loadingMessage.textContent = "";

            showError(
                "Could not load prescriptions. " +
                (error.message || error.code || "Unknown error")
            );

        }
    );
}


/* ==========================================
   UPDATE SUMMARY CARDS
========================================== */

function updateSummary() {

    const total = allPrescriptions.length;

    const pending = allPrescriptions.filter(
        item => getStatus(item) === "Pending"
    ).length;

    const approved = allPrescriptions.filter(
        item => getStatus(item) === "Approved"
    ).length;

    const rejected = allPrescriptions.filter(
        item => getStatus(item) === "Rejected"
    ).length;

    totalPrescriptions.textContent = total;

    pendingPrescriptions.textContent = pending;

    approvedPrescriptions.textContent = approved;

    rejectedPrescriptions.textContent = rejected;
}


/* ==========================================
   DISPLAY PRESCRIPTIONS TABLE
========================================== */

function displayPrescriptions() {

    const searchTerm = (
        searchPrescription.value || ""
    ).toLowerCase().trim();

    const filteredPrescriptions = allPrescriptions.filter(
        prescription => {

            const customer = getCustomerName(
                prescription
            ).toLowerCase();

            const number = getPrescriptionNumber(
                prescription,
                prescription.id
            ).toLowerCase();

            const email = String(
                prescription.email || ""
            ).toLowerCase();

            return (
                customer.includes(searchTerm) ||
                number.includes(searchTerm) ||
                email.includes(searchTerm)
            );

        }
    );

    prescriptionsTableBody.innerHTML = "";

    if (filteredPrescriptions.length === 0) {

        prescriptionsTableBody.innerHTML = `
            <tr>
                <td colspan="6">
                    No prescriptions found.
                </td>
            </tr>
        `;

        return;
    }

    filteredPrescriptions.forEach((prescription) => {

        const status = getStatus(prescription);

        const fileUrl = getFileUrl(prescription);

        const fileDisplay = fileUrl
            ? `<a href="${safeText(fileUrl)}"
                 target="_blank"
                 rel="noopener noreferrer">View file</a>`
            : "No file";

        const row = document.createElement("tr");

        row.innerHTML = `
            <td>${safeText(getCustomerName(prescription))}</td>

            <td>
                ${safeText(
                    getPrescriptionNumber(
                        prescription,
                        prescription.id
                    )
                )}
            </td>

            <td>
                ${safeText(formatDate(prescription.createdAt))}
            </td>

            <td>${fileDisplay}</td>

            <td>
                <span class="status ${status.toLowerCase()}">
                    ${status}
                </span>
            </td>

            <td>
                <button
                    type="button"
                    class="view-button"
                    data-prescription-id="${safeText(prescription.id)}">
                    View
                </button>
            </td>
        `;

        prescriptionsTableBody.appendChild(row);

    });

}


/* ==========================================
   OPEN PRESCRIPTION DETAILS
========================================== */

function openPrescriptionDetails(prescription) {

    selectedPrescription = prescription;

    const status = getStatus(prescription);

    prescriptionDetails.innerHTML = `

        <p>
            <strong>Customer:</strong>
            ${safeText(getCustomerName(prescription))}
        </p>

        <p>
            <strong>Email:</strong>
            ${safeText(prescription.email || "Not provided")}
        </p>

        <p>
            <strong>Phone:</strong>
            ${safeText(
                prescription.phone ||
                prescription.phoneNumber ||
                "Not provided"
            )}
        </p>

        <p>
            <strong>Prescription number:</strong>
            ${safeText(
                getPrescriptionNumber(
                    prescription,
                    prescription.id
                )
            )}
        </p>

        <p>
            <strong>Medication:</strong>
            ${safeText(
                prescription.medicationName ||
                prescription.medication ||
                "Not specified"
            )}
        </p>

        <p>
            <strong>Date submitted:</strong>
            ${safeText(formatDate(prescription.createdAt))}
        </p>

        <p>
            <strong>Status:</strong>
            ${safeText(status)}
        </p>

        <p>
            <strong>Customer notes:</strong>
            ${safeText(
                prescription.notes ||
                prescription.description ||
                "No additional notes"
            )}
        </p>

        ${
            prescription.rejectionReason
                ? `<p>
                    <strong>Rejection reason:</strong>
                    ${safeText(prescription.rejectionReason)}
                  </p>`
                : ""
        }

    `;

    const fileUrl = getFileUrl(prescription);

    if (fileUrl) {

        fileSection.innerHTML = `
            <strong>Prescription document:</strong>
            <p>
                <a href="${safeText(fileUrl)}"
                   target="_blank"
                   rel="noopener noreferrer">
                    Open uploaded prescription
                </a>
            </p>
        `;

    } else {

        fileSection.textContent =
            "No prescription file was attached.";

    }

    // Prevent reviewing an already reviewed prescription.
    const canReview = status === "Pending";

    approveButton.disabled = !canReview;

    rejectButton.disabled = !canReview;

    approveButton.style.opacity =
        canReview ? "1" : "0.5";

    rejectButton.style.opacity =
        canReview ? "1" : "0.5";

    rejectionReason.value = "";

    reviewMessage.textContent = "";

    prescriptionModal.classList.add("open");

    prescriptionModal.setAttribute("aria-hidden", "false");
}


/* ==========================================
   VIEW BUTTON
========================================== */

prescriptionsTableBody.addEventListener("click", (event) => {

    const button = event.target.closest(
        "[data-prescription-id]"
    );

    if (!button) {
        return;
    }

    const prescriptionId =
        button.dataset.prescriptionId;

    const prescription = allPrescriptions.find(
        item => item.id === prescriptionId
    );

    if (prescription) {
        openPrescriptionDetails(prescription);
    }

});


/* ==========================================
   CLOSE MODAL
========================================== */

function closePrescriptionModal() {

    prescriptionModal.classList.remove("open");

    prescriptionModal.setAttribute("aria-hidden", "true");

    selectedPrescription = null;

    reviewMessage.textContent = "";
}


closeModal.addEventListener(
    "click",
    closePrescriptionModal
);


// Close modal when clicking outside the content.
prescriptionModal.addEventListener("click", (event) => {

    if (event.target === prescriptionModal) {
        closePrescriptionModal();
    }

});


/* ==========================================
   APPROVE PRESCRIPTION
========================================== */

approveButton.addEventListener("click", async () => {

    if (!selectedPrescription || !currentAdmin) {
        return;
    }

    if (getStatus(selectedPrescription) !== "Pending") {
        reviewMessage.textContent =
            "This prescription has already been reviewed.";
        return;
    }

    try {

        approveButton.disabled = true;
        rejectButton.disabled = true;

        reviewMessage.textContent =
            "Approving prescription...";

        const prescriptionId =
            selectedPrescription.id;

        const prescriptionRef = doc(
            db,
            "prescriptions",
            prescriptionId
        );

        // Update prescription status.
        await updateDoc(prescriptionRef, {

            status: "Approved",

            reviewedAt: serverTimestamp(),

            reviewedBy: currentAdmin.uid,

            reviewedByEmail: currentAdmin.email || ""

        });


        // Save review history.
        await addDoc(
            collection(
                db,
                "prescriptions",
                prescriptionId,
                "reviewHistory"
            ),
            {

                action: "Approved",

                reviewedBy: currentAdmin.uid,

                reviewedByEmail: currentAdmin.email || "",

                reviewedAt: serverTimestamp()

            }
        );


        reviewMessage.textContent =
            "Prescription approved successfully.";

    } catch (error) {

        console.error("Approval error:", error);

        reviewMessage.textContent =
            "Approval failed: " +
            (error.message || error.code || "Unknown error");

        approveButton.disabled = false;
        rejectButton.disabled = false;

    }

});


/* ==========================================
   REJECT PRESCRIPTION
========================================== */

rejectButton.addEventListener("click", async () => {

    if (!selectedPrescription || !currentAdmin) {
        return;
    }

    if (getStatus(selectedPrescription) !== "Pending") {
        reviewMessage.textContent =
            "This prescription has already been reviewed.";
        return;
    }

    const reason = rejectionReason.value.trim();

    if (!reason) {

        reviewMessage.textContent =
            "Please enter a rejection reason.";

        rejectionReason.focus();

        return;
    }

    try {

        approveButton.disabled = true;
        rejectButton.disabled = true;

        reviewMessage.textContent =
            "Rejecting prescription...";

        const prescriptionId =
            selectedPrescription.id;

        const prescriptionRef = doc(
            db,
            "prescriptions",
            prescriptionId
        );

        // Update prescription status.
        await updateDoc(prescriptionRef, {

            status: "Rejected",

            rejectionReason: reason,

            reviewedAt: serverTimestamp(),

            reviewedBy: currentAdmin.uid,

            reviewedByEmail: currentAdmin.email || ""

        });


        // Save review history.
        await addDoc(
            collection(
                db,
                "prescriptions",
                prescriptionId,
                "reviewHistory"
            ),
            {

                action: "Rejected",

                rejectionReason: reason,

                reviewedBy: currentAdmin.uid,

                reviewedByEmail: currentAdmin.email || "",

                reviewedAt: serverTimestamp()

            }
        );


        reviewMessage.textContent =
            "Prescription rejected successfully.";

    } catch (error) {

        console.error("Rejection error:", error);

        reviewMessage.textContent =
            "Rejection failed: " +
            (error.message || error.code || "Unknown error");

        approveButton.disabled = false;
        rejectButton.disabled = false;

    }

});


/* ==========================================
   SEARCH PRESCRIPTIONS
========================================== */

searchPrescription.addEventListener(
    "input",
    displayPrescriptions
);


/* ==========================================
   ADMIN LOGOUT
========================================== */

logoutLink.addEventListener("click", async (event) => {

    event.preventDefault();

    try {

        if (unsubscribePrescriptions) {
            unsubscribePrescriptions();
        }

        await signOut(auth);

        window.location.href = "admin-login.html";

    } catch (error) {

        console.error("Logout error:", error);

        showError(
            "Could not log out. " +
            (error.message || error.code || "Unknown error")
        );

    }

});



window.addEventListener("beforeunload", () => {

    if (unsubscribePrescriptions) {
        unsubscribePrescriptions();
    }

});
