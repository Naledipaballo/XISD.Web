
import { auth, db } from "./firebase.js";

import {
    onAuthStateChanged,
    signOut
} from "https://www.gstatic.com/firebasejs/12.0.0/firebase-auth.js";

import {
    collection,
    doc,
    getDoc,
    getDocs,
    query,
    orderBy,
    limit,
    onSnapshot
} from "https://www.gstatic.com/firebasejs/12.0.0/firebase-firestore.js";

const errorMessage = document.getElementById("dashboardError");

let unsubscribeOrders = null;

// Display dashboard errors safely
function showDashboardError(message) {
    if (errorMessage) {
        errorMessage.textContent = message;
    }
}

// Verify administrator access
onAuthStateChanged(auth, async (user) => {

    if (!user) {
        window.location.href = "admin-login.html";
        return;
    }

    try {
        // Get the signed-in user's Firestore document
        const userRef = doc(db, "users", user.uid);
        const userSnapshot = await getDoc(userRef);

        // Check whether the user exists and has the admin role
        if (!userSnapshot.exists()) {
            await signOut(auth);
            window.location.href = "admin-login.html";
            return;
        }

        const userData = userSnapshot.data();
        const userRole = String(userData.role || "").toLowerCase();

        if (userRole !== "admin") {
            await signOut(auth);
            window.location.href = "admin-login.html";
            return;
        }

        // Administrator verified: load dashboard
        await loadDashboardCounts();
        loadRecentOrders();

    } catch (error) {
        console.error("Admin verification error:", error);

        showDashboardError(
            "Unable to load dashboard: " + error.message
        );
    }
});


// Load collection counts
async function loadDashboardCounts() {

    const collectionsToCount = [
        { name: "orders", element: "totalOrders" },
        { name: "users", element: "totalCustomers" },
        { name: "prescriptions", element: "totalPrescriptions" },
        { name: "medications", element: "totalMedications" }
    ];

    try {
        for (const item of collectionsToCount) {

            const snapshot = await getDocs(
                collection(db, item.name)
            );

            let count = snapshot.size;

            // Exclude administrators from the customer count
            if (item.name === "users") {
                count = snapshot.docs.filter(userDocument => {
                    const role = String(
                        userDocument.data().role || ""
                    ).toLowerCase();

                    return role !== "admin";
                }).length;
            }

            const countElement = document.getElementById(item.element);

            if (countElement) {
                countElement.textContent = count;
            }
        }

        await loadOrderStatuses();

    } catch (error) {
        console.error("Dashboard counts error:", error);

        showDashboardError(
            "Unable to load dashboard counts: " + error.message
        );
    }
}


// Count orders according to their status
async function loadOrderStatuses() {

    try {
        const snapshot = await getDocs(
            collection(db, "orders")
        );

        const counts = {
            pending: 0,
            processing: 0,
            delivery: 0,
            delivered: 0
        };

        snapshot.forEach(orderDocument => {

            const status = String(
                orderDocument.data().status || "Pending"
            ).trim().toLowerCase();

            if (status === "pending") {
                counts.pending++;
            } else if (
                status === "processing" ||
                status === "confirmed" ||
                status === "ready for delivery"
            ) {
                counts.processing++;
            } else if (status === "out for delivery") {
                counts.delivery++;
            } else if (status === "delivered") {
                counts.delivered++;
            }
        });

        const pendingElement = document.getElementById("pendingOrders");
        const processingElement = document.getElementById("processingOrders");
        const deliveryElement = document.getElementById("deliveryOrders");
        const deliveredElement = document.getElementById("deliveredOrders");

        if (pendingElement) {
            pendingElement.textContent = counts.pending;
        }

        if (processingElement) {
            processingElement.textContent = counts.processing;
        }

        if (deliveryElement) {
            deliveryElement.textContent = counts.delivery;
        }

        if (deliveredElement) {
            deliveredElement.textContent = counts.delivered;
        }

    } catch (error) {
        console.error("Order status count error:", error);

        showDashboardError(
            "Unable to load order statuses: " + error.message
        );
    }
}


// Listen for recent order changes
function loadRecentOrders() {

    const container = document.getElementById("recentOrders");

    if (!container) {
        console.error("The recentOrders table element was not found.");
        return;
    }

    const ordersRef = query(
        collection(db, "orders"),
        orderBy("createdAt", "desc"),
        limit(5)
    );

    unsubscribeOrders = onSnapshot(
        ordersRef,

        (snapshot) => {

            container.innerHTML = "";

            if (snapshot.empty) {
                container.innerHTML =
                    '<tr><td colspan="4">No orders found.</td></tr>';
                return;
            }

            snapshot.forEach(orderDocument => {

                const data = orderDocument.data();

                const row = document.createElement("tr");

                const idCell = document.createElement("td");
                idCell.textContent = orderDocument.id;

                const customerCell = document.createElement("td");
                customerCell.textContent =
                    data.customerName || "Unknown";

                const totalCell = document.createElement("td");
                const total = Number(data.total || 0);

                totalCell.textContent =
                    "R" + total.toFixed(2);

                const statusCell = document.createElement("td");
                statusCell.textContent =
                    data.status || "Pending";

                row.append(
                    idCell,
                    customerCell,
                    totalCell,
                    statusCell
                );

                container.appendChild(row);
            });

            // Refresh order status counts when orders change
            loadOrderStatuses().catch(error => {
                console.error("Status refresh error:", error);
            });
        },

        (error) => {
            console.error("Recent orders listener error:", error);

            showDashboardError(
                "Unable to load recent orders: " + error.message
            );
        }
    );
}


// Logout administrator
const logoutButton = document.getElementById("logoutButton");

if (logoutButton) {

    logoutButton.addEventListener("click", async () => {

        try {
            // Stop listening for order updates
            if (unsubscribeOrders) {
                unsubscribeOrders();
                unsubscribeOrders = null;
            }

            await signOut(auth);

            localStorage.clear();

            window.location.href = "admin-login.html";

        } catch (error) {
            console.error("Logout error:", error);
            alert("Unable to log out. Please try again.");
        }
    });
}
