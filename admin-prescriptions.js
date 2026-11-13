import { auth, db } from "./firebase.js";
import { onAuthStateChanged, signOut } from "firebase/auth";
import {
  collection, doc, getDoc, onSnapshot, query, orderBy,
  updateDoc, addDoc, serverTimestamp
} from "firebase/firestore";

const tableBody = document.getElementById("prescriptionsTableBody");
const loadingMessage = document.getElementById("loadingMessage");
const errorMessage = document.getElementById("errorMessage");
const modal = document.getElementById("prescriptionModal");
const details = document.getElementById("prescriptionDetails");
const fileSection = document.getElementById("fileSection");
const reviewMessage = document.getElementById("reviewMessage");
const rejectionReason = document.getElementById("rejectionReason");
const approveButton = document.getElementById("approveButton");
const rejectButton = document.getElementById("rejectButton");

let allPrescriptions = [];
let selectedPrescription = null;
let currentAdmin = null;
let unsubscribePrescriptions = null;

function safeText(value) {
  return value === null || value === undefined || value === "" ? "Not provided" : String(value);
}

function formatDate(value) {
  if (!value) return "Not provided";
  const date = typeof value.toDate === "function" ? value.toDate() : new Date(value);
  return Number.isNaN(date.getTime()) ? "Not provided" : date.toLocaleString();
}

function getStatus(data) {
  return String(data.status || "Pending").trim();
}

function updateSummary() {
  document.getElementById("totalPrescriptions").textContent = allPrescriptions.length;
  document.getElementById("pendingPrescriptions").textContent = allPrescriptions.filter(p => getStatus(p) === "Pending").length;
  document.getElementById("approvedPrescriptions").textContent = allPrescriptions.filter(p => getStatus(p) === "Approved").length;
  document.getElementById("rejectedPrescriptions").textContent = allPrescriptions.filter(p => getStatus(p) === "Rejected").length;
}

function renderPrescriptions(searchTerm = "") {
  tableBody.replaceChildren();
  const term = searchTerm.trim().toLowerCase();
  const filtered = allPrescriptions.filter(p => {
    const customer = String(p.patientName || p.customerName || "").toLowerCase();
    const number = String(p.prescriptionNumber || "").toLowerCase();
    return customer.includes(term) || number.includes(term);
  });

  if (filtered.length === 0) {
    const row = document.createElement("tr");
    const cell = document.createElement("td");
    cell.colSpan = 6;
    cell.textContent = "No prescriptions found.";
    row.appendChild(cell);
    tableBody.appendChild(row);
    return;
  }

  filtered.forEach(p => {
    const row = document.createElement("tr");
    const values = [
      p.patientName || p.customerName || "Not provided",
      p.prescriptionNumber || "Not provided",
      formatDate(p.createdAt || p.prescriptionDate),
      p.fileName || (p.fileUpload ? "Uploaded" : "No file recorded"),
      getStatus(p)
    ];
    values.forEach(value => {
      const cell = document.createElement("td");
      cell.textContent = String(value);
      row.appendChild(cell);
    });
    const actionCell = document.createElement("td");
    const viewButton = document.createElement("button");
    viewButton.type = "button";
    viewButton.className = "view-button";
    viewButton.textContent = "Review";
    viewButton.addEventListener("click", () => openPrescription(p));
    actionCell.appendChild(viewButton);
    row.appendChild(actionCell);
    tableBody.appendChild(row);
  });
}

function addDetail(label, value) {
  const p = document.createElement("p");
  const strong = document.createElement("strong");
  strong.textContent = label + ": ";
  p.append(strong, document.createTextNode(safeText(value)));
  details.appendChild(p);
}

function openPrescription(prescription) {
  selectedPrescription = prescription;
  details.replaceChildren();
  fileSection.replaceChildren();
  reviewMessage.textContent = "";
  rejectionReason.value = prescription.rejectionReason || "";

  addDetail("Customer", prescription.patientName || prescription.customerName);
  addDetail("Prescription number", prescription.prescriptionNumber);
  addDetail("Doctor", prescription.doctorName);
  addDetail("Prescription date", formatDate(prescription.prescriptionDate));
  addDetail("Submitted", formatDate(prescription.createdAt));
  addDetail("Delivery method", prescription.deliveryMethod);
  addDetail("Additional notes", prescription.additionalNotes || prescription.additionalNo);
  addDetail("Status", getStatus(prescription));

  const fileName = prescription.fileName;
  const fileUrl = prescription.fileUrl || prescription.downloadURL || prescription.downloadUrl;
  const fileText = document.createElement("p");
  fileText.textContent = "File: " + safeText(fileName);
  fileSection.appendChild(fileText);
  if (fileUrl) {
    try {
      const parsedUrl = new URL(fileUrl);
      if (parsedUrl.protocol === "https:") {
        const link = document.createElement("a");
        link.href = parsedUrl.href;
        link.target = "_blank";
        link.rel = "noopener noreferrer";
        link.textContent = "Open uploaded prescription";
        fileSection.appendChild(link);
      } else {
        const note = document.createElement("p");
        note.textContent = "The saved file link is not a secure HTTPS link.";
        fileSection.appendChild(note);
      }
    } catch {
      const note = document.createElement("p");
      note.textContent = "No valid uploaded file link was found.";
      fileSection.appendChild(note);
    }
  } else {
    const note = document.createElement("p");
    note.textContent = "No downloadable file URL is saved. Only prescription details are available.";
    fileSection.appendChild(note);
  }

  const alreadyReviewed = ["Approved", "Rejected"].includes(getStatus(prescription));
  approveButton.disabled = alreadyReviewed;
  rejectButton.disabled = alreadyReviewed;
  approveButton.style.opacity = alreadyReviewed ? "0.5" : "1";
  rejectButton.style.opacity = alreadyReviewed ? "0.5" : "1";
  modal.classList.add("open");
  modal.setAttribute("aria-hidden", "false");
}

function closeModal() {
  modal.classList.remove("open");
  modal.setAttribute("aria-hidden", "true");
  selectedPrescription = null;
}

async function reviewPrescription(newStatus) {
  if (!selectedPrescription || !currentAdmin) return;
  const reason = rejectionReason.value.trim();
  if (newStatus === "Rejected" && !reason) {
    reviewMessage.textContent = "Please enter a reason for rejecting this prescription.";
    return;
  }
  if (["Approved", "Rejected"].includes(getStatus(selectedPrescription))) {
    reviewMessage.textContent = "This prescription has already been reviewed.";
    return;
  }

  approveButton.disabled = true;
  rejectButton.disabled = true;
  reviewMessage.textContent = "Saving review...";
  try {
    const prescriptionRef = doc(db, "prescriptions", selectedPrescription.id);
    const updateData = {
      status: newStatus,
      reviewedAt: serverTimestamp(),
      reviewedBy: currentAdmin.uid,
      reviewedByEmail: currentAdmin.email || ""
    };
    if (newStatus === "Rejected") {
      updateData.rejectionReason = reason;
    } else {
      updateData.rejectionReason = "";
    }
    await updateDoc(prescriptionRef, updateData);
    await addDoc(collection(db, "prescriptions", selectedPrescription.id, "reviewHistory"), {
      fromStatus: getStatus(selectedPrescription),
      toStatus: newStatus,
      reason: newStatus === "Rejected" ? reason : "",
      reviewedAt: serverTimestamp(),
      reviewedBy: currentAdmin.uid,
      reviewedByEmail: currentAdmin.email || ""
    });
    reviewMessage.textContent = "Prescription " + newStatus.toLowerCase() + " successfully.";
  } catch (error) {
    console.error("Prescription review error:", error);
    reviewMessage.textContent = "Unable to save the review: " + (error.message || "Please try again.");
    approveButton.disabled = false;
    rejectButton.disabled = false;
  }
}

document.getElementById("searchPrescription").addEventListener("input", event => {
  renderPrescriptions(event.target.value);
});
document.getElementById("closeModal").addEventListener("click", closeModal);
modal.addEventListener("click", event => { if (event.target === modal) closeModal(); });
approveButton.addEventListener("click", () => reviewPrescription("Approved"));
rejectButton.addEventListener("click", () => reviewPrescription("Rejected"));

document.getElementById("logoutLink").addEventListener("click", async event => {
  event.preventDefault();
  if (unsubscribePrescriptions) unsubscribePrescriptions();
  await signOut(auth);
  window.location.href = "login.html";
});

onAuthStateChanged(auth, async user => {
  if (!user) {
    window.location.href = "admin-login.html";
    return;
  }
  try {
    const adminSnapshot = await getDoc(doc(db, "users", user.uid));
    if (!adminSnapshot.exists() || String(adminSnapshot.data().role || "").toLowerCase() !== "admin") {
      await signOut(auth);
      window.location.href = "admin-login.html";
      return;
    }
    currentAdmin = user;
    const prescriptionsQuery = query(collection(db, "prescriptions"), orderBy("createdAt", "desc"));
    unsubscribePrescriptions = onSnapshot(prescriptionsQuery, snapshot => {
      allPrescriptions = snapshot.docs.map(item => ({ id: item.id, ...item.data() }));
      loadingMessage.textContent = "";
      errorMessage.textContent = "";
      updateSummary();
      renderPrescriptions(document.getElementById("searchPrescription").value);
      if (selectedPrescription) {
        const latest = allPrescriptions.find(p => p.id === selectedPrescription.id);
        if (latest) selectedPrescription = latest;
      }
    }, error => {
      console.error("Prescription listener error:", error);
      loadingMessage.textContent = "";
      errorMessage.textContent = "Could not load prescriptions: " + error.message;
    });
  } catch (error) {
    console.error("Admin verification error:", error);
    errorMessage.textContent = "Unable to verify administrator access.";
  }
});