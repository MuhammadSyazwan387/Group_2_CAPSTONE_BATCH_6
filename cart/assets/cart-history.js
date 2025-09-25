// Global variables
let currentUserId = localStorage.userId; 
let historyItems = [];
let filteredItems = [];

// Initialize page
document.addEventListener("DOMContentLoaded", function () {
  loadHistoryItems();
  setupEventListeners();
});

// Load history items from database
async function loadHistoryItems() {
  try {
    showLoadingState(true);

    const response = await fetch("api/get_cart_history.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        user_id: currentUserId,
        limit: 100,
        offset: 0,
      }),
    });

    const data = await response.json();
    if (data.success) {
      historyItems = data.data.items;
      console.log(historyItems);
      filteredItems = [...historyItems];
      renderHistoryItems();
      updateSummary();
    } else {
      showError("Failed to load history items");
      showEmptyState(true);
    }
  } catch (error) {
    console.error("Error loading history:", error);
    showError("Error loading history items");
    showEmptyState(true);
  } finally {
    showLoadingState(false);
  }
}

// Render history items
function renderHistoryItems() {
  const container = document.querySelector(".history-items");
  const header = container.querySelector(".history-header");

  // Clear existing items (keep header)
  const existingItems = container.querySelectorAll(".history-item");
  existingItems.forEach((item) => item.remove());

  if (filteredItems.length === 0) {
    showEmptyState(true);
    return;
  }

  showEmptyState(false);

  filteredItems.forEach((item) => {
    const historyItemElement = createHistoryItemElement(item);
    container.appendChild(historyItemElement);
  });
}

// Create history item HTML element
function createHistoryItemElement(item) {
  const historyItem = document.createElement("div");
  historyItem.className = "history-item";

  const completedDate = new Date(item.completed_date);
  const formattedDate = completedDate.toLocaleDateString();

  historyItem.innerHTML = `
                <div class="item-details">
                    <div class="item-image" style="background-image: url('../${
                      item.voucher_image || ""
                    }'); background-size: cover;">V</div>
                    <div class="item-info">
                        <h3>${item.voucher_title || "Voucher"}</h3>
                        <div class="item-date">${formattedDate} </div>
                    </div>
                </div>
                <div class="item-quantity">${item.quantity}</div>
                <div class="item-points">${(
                  item.quantity * (item.points || 1000)
                ).toLocaleString()}</div>
            `;

  return historyItem;
}

// Update summary statistics
function updateSummary() {
  const totalItems = filteredItems.reduce(
    (sum, item) => sum + parseInt(item.quantity),
    0
  );
  const totalPoints = filteredItems.reduce(
    (sum, item) =>
      sum + parseInt(item.quantity) * (parseInt(item.points) || 1000),
    0
  );
  const completedOrders = filteredItems.length;

  // Calculate monthly total
  const thisMonth = new Date();
  thisMonth.setDate(1);
  const monthlyItems = filteredItems.filter(
    (item) => new Date(item.completed_date) >= thisMonth
  );
  const monthlyTotal = monthlyItems.reduce(
    (sum, item) =>
      sum + parseInt(item.quantity) * (parseInt(item.points) || 1000),
    0
  );

  document.getElementById("totalItems").textContent =
    totalItems.toLocaleString();
  document.getElementById("totalPoints").textContent =
    totalPoints.toLocaleString();
  document.getElementById("completedOrders").textContent =
    completedOrders.toLocaleString();
  document.getElementById("monthlyTotal").textContent =
    monthlyTotal.toLocaleString();
}

// Setup event listeners
function setupEventListeners() {
  // Search input
  document.getElementById("searchInput").addEventListener("input", function () {
    filterItems();
  });

  // Date filter
  document.getElementById("dateFilter").addEventListener("change", function () {
    filterItems();
  });

  // Clear history button
  document
    .getElementById("clearHistoryBtn")
    .addEventListener("click", function () {
      if (
        confirm(
          "Are you sure you want to clear all history? This action cannot be undone."
        )
      ) {
        clearHistory();
      }
    });
}

// Filter items based on search and filters
function filterItems() {
  const searchTerm = document.getElementById("searchInput").value.toLowerCase();
  const dateFilter = document.getElementById("dateFilter").value;

  filteredItems = historyItems.filter((item) => {
    // Search filter
    const matchesSearch = item.voucher_title.toLowerCase().includes(searchTerm);

    // Date filter
    let matchesDate = true;
    if (dateFilter) {
      const itemDate = new Date(item.completed_date);
      const now = new Date();

      switch (dateFilter) {
        case "today":
          matchesDate = itemDate.toDateString() === now.toDateString();
          break;
        case "week":
          const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
          matchesDate = itemDate >= weekAgo;
          break;
        case "month":
          matchesDate =
            itemDate.getMonth() === now.getMonth() &&
            itemDate.getFullYear() === now.getFullYear();
          break;
        case "year":
          matchesDate = itemDate.getFullYear() === now.getFullYear();
          break;
      }
    }


    return matchesSearch && matchesDate;
  });

  renderHistoryItems();
  updateSummary();
}

// Clear history
async function clearHistory() {
  try {
    const response = await fetch("api/remove_cart_history.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        user_id: currentUserId,
      }),
    });

    const data = await response.json();
    if (data.success) {
      historyItems = [];
      filteredItems = [];
      renderHistoryItems();
      updateSummary();
      showSuccess("History cleared successfully");
    } else {
      showError(data.message || "Failed to clear history");
    }
  } catch (error) {
    console.error("Error clearing history:", error);
    showError("Error clearing history");
  }
}

// Show/hide loading state
function showLoadingState(show) {
  const spinner = document.getElementById("loadingSpinner");
  spinner.style.display = show ? "flex" : "none";
}

// Show/hide empty state
function showEmptyState(show) {
  const emptyState = document.getElementById("emptyState");
  emptyState.style.display = show ? "block" : "none";
}

// Utility functions for notifications
function showSuccess(message) {
  showNotification(message, "success");
}

function showError(message) {
  showNotification(message, "error");
}

function showNotification(message, type) {
  const notification = document.createElement("div");
  notification.className = `notification ${type}`;
  notification.textContent = message;
  notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 1rem 2rem;
                border-radius: 8px;
                color: white;
                font-weight: 500;
                z-index: 1000;
                animation: slideInRight 0.3s ease;
                background: ${type === "success" ? "#10b981" : "#ef4444"};
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            `;

  document.body.appendChild(notification);

  setTimeout(() => {
    notification.style.animation = "slideOutRight 0.3s ease";
    setTimeout(() => notification.remove(), 300);
  }, 3000);
}

// Add notification animations
const style = document.createElement("style");
style.textContent = `
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
document.head.appendChild(style);

// Initialize cart on page load
document.addEventListener("DOMContentLoaded", function () {
  loadCartCount();
});

async function loadCartCount() {
    try {
        const response = await fetch('./cart/api/get_cart_count.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                user_id: currentUserId,
            }),
        });
        const data = await response.json();;

        if (data.success) {
            updateCartCount(data.data.item_count);
        } else {
            console.error('Failed to load cart count:', data.message);
        }
    } catch (error) {
        console.error('Error loading cart count:', error);
    }
}

// Update cart count in navigation
function updateCartCount(count) {
  console.log(`Cart has ${count} items`);
  // Update cart count in navigation if needed
  const cartCount = document.querySelector(".cart-count");
  if (cartCount) {
    cartCount.textContent = count;
    cartCount.style.display = count > 0 ? "block" : "none";
  }
}

function logout() {
    if (confirm('Are you sure you want to logout?')) {
        localStorage.removeItem('userLoggedIn');
        localStorage.removeItem('userEmail');
        localStorage.removeItem('userName');
        localStorage.removeItem('redeemedVouchers');
        
        window.location.href = '../authentication_page.html';
    }
}
