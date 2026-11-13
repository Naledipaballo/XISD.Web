import { auth, db } from "./firebase.js";
import { onAuthStateChanged, signOut } from "firebase/auth";
import {
  collection, doc, getDoc, onSnapshot, query, orderBy,
  addDoc, updateDoc, deleteDoc, serverTimestamp
} from "firebase/firestore";

const tableBody = document.getElementById("medicationsTableBody");
const loadingMessage = document.getElementById("loadingMessage");
const errorMessage = document.getElementById("errorMessage");
const modal = document.getElementById("medicationModal");
const form = document.getElementById("medicationForm");
const formMessage = document.getElementById("formMessage");
const saveButton = document.getElementById("saveMedicationButton");

let medications = [];
let editingMedicationId = null;
let currentAdmin = null;
let unsubscribeMedications = null;

function money(value) {
  const amount = Number(value);
  return Number.isFinite(amount) ? "R" + amount.toFixed(2) : "R0.00";
}

function showModal(medication = null) {
  form.reset();
  formMessage.textContent = "";
  editingMedicationId = medication ? medication.id : null;
  document.getElementById("modalTitle").textContent = medication ? "Edit Medication" : "Add Medication";
  document.getElementById("medicationName").value = medication?.name || medication?.medicationName || "";
  document.getElementById("medicationCategory").value = medication?.category || "";
  document.getElementById("medicationDescription").value = medication?.description || "";
  document.getElementById("medicationPrice").value = medication?.price ?? "";
  document.getElementById("medicationStock").value = medication?.stock ?? medication?.quantity ?? 0;
  document.getElementById("medicationImage").value = medication?.imageUrl || medication?.image || "";
  document.getElementById("medicationAvailable").checked = medication?.available !== false;
  modal.classList.add("open");
  modal.setAttribute("aria-hidden", "false");
}

function closeModal() {
  modal.classList.remove("open");
  modal.setAttribute("aria-hidden", "true");
  editingMedicationId = null;
}

function renderMedications(searchTerm = "") {
  tableBody.replaceChildren();
  const term = searchTerm.trim().toLowerCase();
  const filtered = medications.filter(m =>
    String(m.name || m.medicationName || "").toLowerCase().includes(term) ||
    String(m.category || "").toLowerCase().includes(term)
  );

  if (!filtered.length) {
    const row = document.createElement("tr");
    const cell = document.createElement("td");
    cell.colSpan = 6;
    cell.textContent = "No medications found.";
    row.appendChild(cell);
    tableBody.appendChild(row);
    return;
  }

  filtered.forEach(m => {
    const row = document.createElement("tr");
    const values = [
      m.name || m.medicationName || "Unnamed medication",
      m.category || "Not provided",
      money(m.price),
      m.stock ?? m.quantity ?? 0,
      m.available === false ? "Unavailable" : "Available"
    ];
    values.forEach(value => {
      const cell = document.createElement("td");
      cell.textContent = String(value);
      if (value === "Unavailable") cell.innerHTML = "<span class=\"status\">Unavailable</span>";
      row.appendChild(cell);
    });

    const actionCell = document.createElement("td");
    const actions = document.createElement("div");
    actions.className = "action-buttons";
    const editButton = document.createElement("button");
    editButton.className = "edit-button";
    editButton.textContent = "Edit";
    editButton.addEventListener("click", () => showModal(m));
    const deleteButton = document.createElement("button");
    deleteButton.className = "delete-button";
    deleteButton.textContent = "Delete";
    deleteButton.addEventListener("click", () => removeMedication(m));
    actions.append(editButton, deleteButton);
    actionCell.appendChild(actions);
    row.appendChild(actionCell);
    tableBody.appendChild(row);
  });
}

function updateSummary() {
  document.getElementById("totalMedications").textContent = medications.length;
  document.getElementById("availableMedications").textContent = medications.filter(m => m.available !== false && Number(m.stock ?? m.quantity ?? 0) > 0).length;
  document.getElementById("outOfStockMedications").textContent = medications.filter(m => Number(m.stock ?? m.quantity ?? 0) <= 0).length;
}

async function removeMedication(medication) {
  const name = medication.name || medication.medicationName || "this medication";
  if (!confirm(`Are you sure you want to delete ${name}?`)) return;
  try {
    await deleteDoc(doc(db, "medications", medication.id));
  } catch (error) {
    console.error("Delete medication error:", error);
    errorMessage.textContent = "Could not delete medication: " + error.message;
  }
}

form.addEventListener("submit", async event => {
  event.preventDefault();
  if (!currentAdmin) return;

  const name = document.getElementById("medicationName").value.trim();
  const category = document.getElementById("medicationCategory").value.trim();
  const description = document.getElementById("medicationDescription").value.trim();
  const price = Number(document.getElementById("medicationPrice").value);
  const stock = Number(document.getElementById("medicationStock").value);
  const imageUrl = document.getElementById("medicationImage").value.trim();
  const available = document.getElementById("medicationAvailable").checked;

  if (!name || !category || !Number.isFinite(price) || price < 0 || !Number.isInteger(stock) || stock < 0) {
    formMessage.textContent = "Enter a valid name, category, price and whole-number stock quantity.";
    return;
  }

  if (imageUrl) {
    try {
      if (new URL(imageUrl).protocol !== "https:") throw new Error("HTTPS required");
    } catch {
      formMessage.textContent = "Please enter a valid HTTPS image URL or leave it blank.";
      return;
    }
  }

  saveButton.disabled = true;
  saveButton.textContent = "Saving...";
  formMessage.textContent = "";

  const medicationData = {
    name,
    category,
    description,
    price,
    stock,
    available: available && stock > 0,
    imageUrl,
    updatedAt: serverTimestamp(),
    updatedBy: currentAdmin.uid
  };

  try {
    if (editingMedicationId) {
      await updateDoc(doc(db, "medications", editingMedicationId), medicationData);
      formMessage.textContent = "Medication updated successfully.";
    } else {
      await addDoc(collection(db, "medications"), {
        ...medicationData,
        createdAt: serverTimestamp(),
        createdBy: currentAdmin.uid
      });
      formMessage.textContent = "Medication added successfully.";
    }
    closeModal();
  } catch (error) {
    console.error("Save medication error:", error);
    formMessage.textContent = "Could not save medication: " + error.message;
  } finally {
    saveButton.disabled = false;
    saveButton.textContent = "Save Medication";
  }
});

document.getElementById("addMedicationButton").addEventListener("click", () => showModal());
document.getElementById("closeModal").addEventListener("click", closeModal);
modal.addEventListener("click", event => { if (event.target === modal) closeModal(); });
document.getElementById("searchMedication").addEventListener("input", event => renderMedications(event.target.value));

document.getElementById("logoutLink").addEventListener("click", async event => {
  event.preventDefault();
  if (unsubscribeMedications) unsubscribeMedications();
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
    const medicationsQuery = query(collection(db, "medications"), orderBy("name"));
    unsubscribeMedications = onSnapshot(medicationsQuery, snapshot => {
      medications = snapshot.docs.map(item => ({ id: item.id, ...item.data() }));
      loadingMessage.textContent = "";
      errorMessage.textContent = "";
      updateSummary();
      renderMedications(document.getElementById("searchMedication").value);
    }, error => {
      loadingMessage.textContent = "";
      errorMessage.textContent = "Could not load medications: " + error.message;
    });
  } catch (error) {
    console.error("Admin verification error:", error);
    errorMessage.textContent = "Unable to verify administrator access.";
  }
});