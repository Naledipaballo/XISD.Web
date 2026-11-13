import { auth, db } from "./firebase.js";

import {
  onAuthStateChanged,
  signOut
} from "firebase/auth";

import {
  collection,
  doc,
  getDoc,
  onSnapshot
} from "firebase/firestore";

const tableBody = document.getElementById("customersTableBody");
const loadingMessage = document.getElementById("loadingMessage");
const errorMessage = document.getElementById("errorMessage");

const modal = document.getElementById("customerModal");
const customerDetails = document.getElementById("customerDetails");

let customers = [];
let unsubscribeCustomers = null;

// Return the customer's full name.
function getCustomerName(customer) {
  const firstName = customer.firstName || "";
  const lastName = customer.lastName || "";

  const fullName = `${firstName} ${lastName}`.trim();

  return fullName ||
    customer.fullName ||
    customer.name ||
    "Name not provided";
}

// Format Firestore timestamps.
function formatDate(value) {
  if (!value) return "Not provided";

  const date = typeof value.toDate === "function"
    ? value.toDate()
    : new Date(value);

  if (Number.isNaN(date.getTime())) {
    return "Not provided";
  }

  return date.toLocaleString();
}

// Update the customer summary cards.
function updateSummary() {
  document.getElementById("totalCustomers").textContent =
    customers.length;

  document.getElementById("customersWithEmail").textContent =
    customers.filter(customer => customer.email).length;

  document.getElementById("customersWithPhone").textContent =
    customers.filter(customer => customer.phone).length;
}

// Create a safe table cell.
function createCell(value) {
  const cell = document.createElement("td");
  cell.textContent = value || "Not provided";
  return cell;
}

// Display customers in the table.
function renderCustomers(searchTerm = "") {
  tableBody.replaceChildren();

  const term = searchTerm.trim().toLowerCase();

  const filteredCustomers = customers.filter(customer => {
    const name = getCustomerName(customer).toLowerCase();
    const email = String(customer.email || "").toLowerCase();
    const phone = String(customer.phone || "").toLowerCase();

    return name.includes(term) ||
      email.includes(term) ||
      phone.includes(term);
  });

  if (filteredCustomers.length === 0) {
    const row = document.createElement("tr");
    const cell = document.createElement("td");

    cell.colSpan = 6;
    cell.textContent = "No customers found.";

    row.appendChild(cell);
    tableBody.appendChild(row);
    return;
  }

  filteredCustomers.forEach(customer => {
    const row = document.createElement("tr");

    row.appendChild(createCell(getCustomerName(customer)));
    row.appendChild(createCell(customer.email));
    row.appendChild(createCell(customer.phone));
    row.appendChild(createCell(customer.province));
    row.appendChild(createCell(formatDate(customer.createdAt)));

    const actionCell = document.createElement("td");

    const viewButton = document.createElement("button");
    viewButton.type = "button";
    viewButton.className = "view-button";
    viewButton.textContent = "View Details";

    viewButton.addEventListener("click", () => {
      openCustomerDetails(customer);
    });

    actionCell.appendChild(viewButton);
    row.appendChild(actionCell);

    tableBody.appendChild(row);
  });
}

// Add a detail row to the customer modal.
function addDetail(label, value) {
  const paragraph = document.createElement("p");

  const strong = document.createElement("strong");
  strong.textContent = label + ": ";

  const text = document.createTextNode(
    value === null || value === undefined || value === ""
      ? "Not provided"
      : String(value)
  );

  paragraph.append(strong, text);
  customerDetails.appendChild(paragraph);
}

// Open customer information.
function openCustomerDetails(customer) {
  customerDetails.replaceChildren();

  addDetail("Full name", getCustomerName(customer));
  addDetail("Email", customer.email);
  addDetail("Phone", customer.phone);
  addDetail("Age", customer.age);
  addDetail("Gender", customer.gender);
  addDetail("Address", customer.address);
  addDetail("Postal code", customer.postalCode);
  addDetail("Province", customer.province);
  addDetail("Username", customer.username);
  addDetail("Account role", customer.role || "Customer");
  addDetail("Registration date", formatDate(customer.createdAt));

  modal.classList.add("open");
  modal.setAttribute("aria-hidden", "false");
}

// Close customer information.
function closeModal() {
  modal.classList.remove("open");
  modal.setAttribute("aria-hidden", "true");
}

// Search customers.
document
  .getElementById("searchCustomer")
  .addEventListener("input", event => {
    renderCustomers(event.target.value);
  });

// Close modal.
document
  .getElementById("closeModal")
  .addEventListener("click", closeModal);

modal.addEventListener("click", event => {
  if (event.target === modal) {
    closeModal();
  }
});

// Admin logout.
document
  .getElementById("logoutLink")
  .addEventListener("click", async event => {
    event.preventDefault();

    if (unsubscribeCustomers) {
      unsubscribeCustomers();
    }

    try {
      await signOut(auth);
      window.location.href = "login.html";
    } catch (error) {
      console.error("Logout error:", error);
      errorMessage.textContent = "Unable to log out. Please try again.";
    }
  });

// Verify administrator and load customers.
onAuthStateChanged(auth, async user => {
  if (!user) {
    window.location.href = "admin-login.html";
    return;
  }

  try {
    const adminSnapshot = await getDoc(
      doc(db, "users", user.uid)
    );

    if (
      !adminSnapshot.exists() ||
      String(adminSnapshot.data().role || "").trim().toLowerCase() !== "admin"
    ) {
      await signOut(auth);
      window.location.href = "admin-login.html";
      return;
    }

    // Listen to customer profile changes in Firestore.
    unsubscribeCustomers = onSnapshot(
      collection(db, "users"),

      snapshot => {
        customers = snapshot.docs
          .map(item => ({
            id: item.id,
            ...item.data()
          }))
          // Do not include administrator accounts.
          .filter(customer =>
            String(customer.role || "customer")
              .trim()
              .toLowerCase() !== "admin"
          );

        loadingMessage.textContent = "";
        errorMessage.textContent = "";

        updateSummary();

        renderCustomers(
          document.getElementById("searchCustomer").value
        );
      },

      error => {
        console.error("Customer loading error:", error);

        loadingMessage.textContent = "";
        errorMessage.textContent =
          "Could not load customers: " + error.message;
      }
    );

  } catch (error) {
    console.error("Admin verification error:", error);

    errorMessage.textContent =
      "Unable to verify administrator access.";
  }
});