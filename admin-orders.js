import { auth, db } from "./firebase.js";

import {
    onAuthStateChanged,
    signOut
} from "firebase/auth";

import {
    collection,
    doc,
    getDoc,
    query,
    orderBy,
    onSnapshot,
    where,
    writeBatch,
    serverTimestamp
} from "firebase/firestore";

// Page elements
const ordersTableBody = document.getElementById("ordersTableBody");
const loadingMessage = document.getElementById("loadingMessage");
const errorMessage = document.getElementById("errorMessage");
const searchInput = document.getElementById("searchOrder");

const orderModal = document.getElementById("orderModal");
const orderDetails = document.getElementById("orderDetails");
const orderStatus = document.getElementById("orderStatus");
const updateStatusButton = document.getElementById("updateStatusButton");
const statusMessage = document.getElementById("statusMessage");
const statusHistory = document.getElementById("statusHistory");

let allOrders = [];
let selectedOrderId = null;
let selectedOrderData = null;

let unsubscribeOrders = null;
let unsubscribeHistory = null;
let adminVerified = false;

// Verify the signed-in user is an administrator
onAuthStateChanged(auth, async (user) => {

    if (!user) {
        window.location.href = "admin-login.html";
        return;
    }

    try {
        const userRef = doc(db, "users", user.uid);
        const userSnapshot = await getDoc(userRef);

        if (
            !userSnapshot.exists() ||
            String(userSnapshot.data().role || "").trim().toLowerCase() !== "admin"
        ) {
            await signOut(auth);
            window.location.href = "admin-login.html";
            return;
        }

        adminVerified = true;
        loadOrders();

    } catch (error) {
        console.error("Admin verification error:", error);
        errorMessage.textContent = "Unable to verify administrator access.";
    }
});

// Listen for orders from Firestore
function loadOrders() {

    loadingMessage.textContent = "Loading orders...";

    const ordersQuery = query(
        collection(db, "orders"),
        orderBy("createdAt", "desc")
    );

    unsubscribeOrders = onSnapshot(
        ordersQuery,
        (snapshot) => {

            allOrders = snapshot.docs.map(orderDoc => ({
                id: orderDoc.id,
                ...orderDoc.data()
            }));

            loadingMessage.textContent = "";

            updateSummaryCards();
            displayOrders(allOrders);

            // Refresh an open order if it changes
            if (selectedOrderId) {
                const updatedOrder = allOrders.find(
                    order => order.id === selectedOrderId
                );

                if (updatedOrder) {
                    selectedOrderData = updatedOrder;
                    orderStatus.value = updatedOrder.status || "Pending";
                    displayOrderDetails(updatedOrder);
                }
            }
        },
        (error) => {
            console.error("Orders loading error:", error);
            loadingMessage.textContent = "";
            errorMessage.textContent =
                "Unable to load orders. Check your Firestore security rules.";
        }
    );
}

// Update summary cards
function updateSummaryCards() {

    let pending = 0;
    let processing = 0;
    let delivered = 0;

    allOrders.forEach(order => {

        const status = String(order.status || "Pending").toLowerCase();

        if (status === "pending") {
            pending++;
        }

        if (
            status === "confirmed" ||
            status === "processing" ||
            status === "ready for delivery"
        ) {
            processing++;
        }

        if (status === "delivered") {
            delivered++;
        }
    });

    document.getElementById("totalOrders").textContent = allOrders.length;
    document.getElementById("pendingOrders").textContent = pending;
    document.getElementById("processingOrders").textContent = processing;
    document.getElementById("deliveredOrders").textContent = delivered;
}

// Display orders in the table
function displayOrders(orders) {

    ordersTableBody.innerHTML = "";

    if (orders.length === 0) {
        const row = document.createElement("tr");
        const cell = document.createElement("td");

        cell.colSpan = 6;
        cell.textContent = "No orders found.";

        row.appendChild(cell);
        ordersTableBody.appendChild(row);
        return;
    }

    orders.forEach(order => {

        const row = document.createElement("tr");

        const idCell = document.createElement("td");
        idCell.textContent = order.id;

        const customerCell = document.createElement("td");
        customerCell.textContent = order.customerName || "Unknown";

        const dateCell = document.createElement("td");
        dateCell.textContent = formatDate(order.createdAt);

        const totalCell = document.createElement("td");
        totalCell.textContent =
            "R" + Number(order.total || 0).toFixed(2);

        const statusCell = document.createElement("td");

        const badge = document.createElement("span");
        badge.className = "status-badge";
        badge.textContent = order.status || "Pending";

        statusCell.appendChild(badge);

        const actionCell = document.createElement("td");

        const viewButton = document.createElement("button");
        viewButton.className = "view-button";
        viewButton.textContent = "View Details";

        viewButton.addEventListener("click", () => {
            openOrder(order.id);
        });

        actionCell.appendChild(viewButton);

        row.append(
            idCell,
            customerCell,
            dateCell,
            totalCell,
            statusCell,
            actionCell
        );

        ordersTableBody.appendChild(row);
    });
}

// Search orders
searchInput.addEventListener("input", () => {

    const searchValue = searchInput.value.trim().toLowerCase();

    const filteredOrders = allOrders.filter(order => {

        return (
            order.id.toLowerCase().includes(searchValue) ||
            String(order.customerName || "").toLowerCase().includes(searchValue) ||
            String(order.status || "").toLowerCase().includes(searchValue)
        );
    });

    displayOrders(filteredOrders);
});

// Open order details
function openOrder(orderId) {

    const order = allOrders.find(item => item.id === orderId);

    if (!order) {
        return;
    }

    selectedOrderId = orderId;
    selectedOrderData = order;

    statusMessage.textContent = "";
    orderStatus.value = order.status || "Pending";

    displayOrderDetails(order);

    orderModal.classList.add("show");

    loadStatusHistory(orderId);
}

// Display order information safely
function displayOrderDetails(order) {

    orderDetails.innerHTML = "";

    addDetail("Order ID", order.id);
    addDetail("Customer", order.customerName || "Not provided");
    addDetail("Phone", order.phone || order.customerPhone || "Not provided");
    addDetail("Delivery Address", order.deliveryAddress || "Not provided");
    addDetail("Order Date", formatDate(order.createdAt));
    addDetail("Payment Method", order.paymentMethod || "Not provided");
    addDetail("Current Status", order.status || "Pending");

    const itemsHeading = document.createElement("h3");
    itemsHeading.textContent = "Ordered Medication";
    itemsHeading.style.marginTop = "20px";

    orderDetails.appendChild(itemsHeading);

    const itemsList = document.createElement("ul");
    itemsList.className = "items-list";

    if (Array.isArray(order.items) && order.items.length > 0) {

        order.items.forEach(item => {

            const listItem = document.createElement("li");

            const name = item.name || item.medicationName || "Medication";
            const quantity = Number(item.quantity || 1);
            const price = Number(item.price ?? item.unitPrice ?? 0);
            const lineTotal = Number(
                item.lineTotal ?? price * quantity
            );

            listItem.textContent =
                `${name} × ${quantity} — R${lineTotal.toFixed(2)}`;

            itemsList.appendChild(listItem);
        });

    } else {

        const listItem = document.createElement("li");
        listItem.textContent = "No medication items recorded.";
        itemsList.appendChild(listItem);
    }

    orderDetails.appendChild(itemsList);

    addDetail("Subtotal", "R" + Number(order.subtotal || 0).toFixed(2));
    addDetail("Delivery Fee", "R" + Number(order.deliveryFee || 0).toFixed(2));
    addDetail("Total", "R" + Number(order.total || 0).toFixed(2));
}

// Create safe order detail rows
function addDetail(label, value) {

    const row = document.createElement("div");
    row.className = "detail-row";

    const strong = document.createElement("strong");
    strong.textContent = label + ": ";

    const text = document.createElement("span");
    text.textContent = String(value);

    row.append(strong, text);
    orderDetails.appendChild(row);
}

// Update order status and create history record
updateStatusButton.addEventListener("click", async () => {

    if (!adminVerified || !auth.currentUser || !selectedOrderId) {
        statusMessage.textContent = "Please sign in as an administrator.";
        return;
    }

    const newStatus = orderStatus.value;
    const oldStatus = String(
        selectedOrderData?.status || "Pending"
    );

    if (newStatus === oldStatus) {
        statusMessage.textContent = "The order already has this status.";
        return;
    }

    updateStatusButton.disabled = true;
    updateStatusButton.textContent = "Updating...";
    statusMessage.textContent = "";

    try {
        const user = auth.currentUser;

        const orderRef = doc(db, "orders", selectedOrderId);

        const historyRef = doc(
            collection(db, "orders", selectedOrderId, "statusHistory")
        );

        const batch = writeBatch(db);

        // Update the order
        batch.update(orderRef, {
            status: newStatus,
            statusUpdatedAt: serverTimestamp(),
            statusUpdatedBy: user.uid
        });

        // Record the status change
        batch.set(historyRef, {
            fromStatus: oldStatus,
            toStatus: newStatus,
            updatedAt: serverTimestamp(),
            updatedBy: user.uid,
            adminEmail: user.email || ""
        });

        await batch.commit();

        statusMessage.textContent = "Order status updated successfully.";

    } catch (error) {

        console.error("Status update error:", error);

        statusMessage.textContent =
            "Unable to update status: " + error.message;

    } finally {

        updateStatusButton.disabled = false;
        updateStatusButton.textContent = "Update Status";
    }
});

// Listen for status history
function loadStatusHistory(orderId) {

    if (unsubscribeHistory) {
        unsubscribeHistory();
        unsubscribeHistory = null;
    }

    statusHistory.textContent = "Loading history...";

    const historyQuery = query(
        collection(db, "orders", orderId, "statusHistory"),
        orderBy("updatedAt", "desc")
    );

    unsubscribeHistory = onSnapshot(
        historyQuery,
        (snapshot) => {

            statusHistory.innerHTML = "";

            if (snapshot.empty) {
                statusHistory.textContent = "No status changes recorded yet.";
                return;
            }

            snapshot.forEach(historyDoc => {

                const history = historyDoc.data();

                const item = document.createElement("div");
                item.className = "history-item";

                const change = document.createElement("strong");
                change.textContent =
                    `${history.fromStatus || "Created"} → ${history.toStatus || "Updated"}`;

                const date = document.createElement("p");
                date.textContent = formatDate(history.updatedAt);

                const admin = document.createElement("p");
                admin.textContent =
                    "Updated by: " + (history.adminEmail || "Administrator");

                item.append(change, date, admin);
                statusHistory.appendChild(item);
            });
        },
        (error) => {
            console.error("History loading error:", error);
            statusHistory.textContent =
                "Unable to load status history. Check Firestore rules.";
        }
    );
}

// Format Firestore timestamps
function formatDate(timestamp) {

    if (!timestamp) {
        return "Not available";
    }

    const date = timestamp.toDate
        ? timestamp.toDate()
        : new Date(timestamp);

    if (Number.isNaN(date.getTime())) {
        return "Not available";
    }

    return date.toLocaleString("en-ZA");
}

// Close order details
document.getElementById("closeModal").addEventListener("click", closeModal);

function closeModal() {

    orderModal.classList.remove("show");

    selectedOrderId = null;
    selectedOrderData = null;

    if (unsubscribeHistory) {
        unsubscribeHistory();
        unsubscribeHistory = null;
    }
}

orderModal.addEventListener("click", (event) => {

    if (event.target === orderModal) {
        closeModal();
    }
});

// Lo gout
document.getElementById("logoutButton").addEventListener("click", async () => {

    if (unsubscribeOrders) {
        unsubscribeOrders();
    }

    if (unsubscribeHistory) {
        unsubscribeHistory();
    }

    try {
        await signOut(auth);
        localStorage.clear();
        window.location.href = "login.html";

    } catch (error) {
        console.error("Logout error:", error);
        alert("Unable to log out. Please try again.");
    }
});