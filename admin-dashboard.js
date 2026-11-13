import { auth, db } from "./firebase.js";

import {
    onAuthStateChanged,
    signOut
} from "firebase/auth";

import {
    collection,
    getDocs,
    query,
    orderBy,
    limit,
    onSnapshot
} from "firebase/firestore";

const errorMessage = document.getElementById("dashboardError");

let unsubscribeOrders = null;

// Verify admin access
onAuthStateChanged(auth, async (user) => {

    if (!user) {
        window.location.href = "admin-login.html";
        return;
    }

    try {
        const { doc, getDoc } = await import("firebase/firestore");

        const userRef = doc(db, "users", user.uid);
        const userSnapshot = await getDoc(userRef);

        if (
            !userSnapshot.exists() ||
            String(userSnapshot.data().role || "").toLowerCase() !== "admin"
        ) {
            await signOut(auth);
            window.location.href = "admin-login.html";
            return;
        }

        // Load dashboard information
        await loadDashboardCounts();
        loadRecentOrders();

    } catch (error) {
        console.error(error);
        errorMessage.textContent =
            "Unable to load dashboard: " + error.message;
    }
});

// Get collection counts
async function loadDashboardCounts() {

    const collections = [
        { name: "orders", element: "totalOrders" },
        { name: "users", element: "totalCustomers" },
        { name: "prescriptions", element: "totalPrescriptions" },
        { name: "medications", element: "totalMedications" }
    ];

    for (const item of collections) {

        const snapshot = await getDocs(
            collection(db, item.name)
        );

        let count = snapshot.size;

        // Count customers, excluding administrators
        if (item.name === "users") {
            count = snapshot.docs.filter(
                userDoc =>
                    String(userDoc.data().role || "").toLowerCase() !== "admin"
            ).length;
        }

        document.getElementById(item.element).textContent = count;
    }

    await loadOrderStatuses();
}

// Count orders by status
async function loadOrderStatuses() {

    const snapshot = await getDocs(
        collection(db, "orders")
    );

    const counts = {
        pending: 0,
        processing: 0,
        delivery: 0,
        delivered: 0
    };

    snapshot.forEach(orderDoc => {

        const status = String(
            orderDoc.data().status || "Pending"
        ).toLowerCase();

        if (status === "pending") {
            counts.pending++;
        }

        if (
            status === "processing" ||
            status === "confirmed" ||
            status === "ready for delivery"
        ) {
            counts.processing++;
        }

        if (status === "out for delivery") {
            counts.delivery++;
        }

        if (status === "delivered") {
            counts.delivered++;
        }
    });

    document.getElementById("pendingOrders").textContent = counts.pending;
    document.getElementById("processingOrders").textContent = counts.processing;
    document.getElementById("deliveryOrders").textContent = counts.delivery;
    document.getElementById("deliveredOrders").textContent = counts.delivered;
}

// Listen for recent order changes
function loadRecentOrders() {

    const ordersRef = query(
        collection(db, "orders"),
        orderBy("createdAt", "desc"),
        limit(5)
    );

    unsubscribeOrders = onSnapshot(ordersRef, (snapshot) => {

        const container = document.getElementById("recentOrders");
        container.innerHTML = "";

        if (snapshot.empty) {
            container.innerHTML =
                '<tr><td colspan="4">No orders found.</td></tr>';
            return;
        }

        snapshot.forEach(orderDoc => {

            const data = orderDoc.data();

            const row = document.createElement("tr");

            const idCell = document.createElement("td");
            idCell.textContent = orderDoc.id;

            const customerCell = document.createElement("td");
            customerCell.textContent = data.customerName || "Unknown";

            const totalCell = document.createElement("td");
            const total = Number(data.total || 0);
            totalCell.textContent = "R" + total.toFixed(2);

            const statusCell = document.createElement("td");
            statusCell.textContent = data.status || "Pending";

            row.append(
                idCell,
                customerCell,
                totalCell,
                statusCell
            );

            container.appendChild(row);
        });

        // Refresh order counts when orders change
        loadOrderStatuses().catch(console.error);

    }, (error) => {
        console.error("Orders listener error:", error);
        errorMessage.textContent = "Unable to load recent orders.";
    });
}

// Logout
document.getElementById("logoutButton").addEventListener("click", async () => {

    try {
        if (unsubscribeOrders) {
            unsubscribeOrders();
        }

        await signOut(auth);
        localStorage.clear();

        window.location.href = "login.html";

    } catch (error) {
        console.error("Logout error:", error);
        alert("Unable to log out.");
    }
});