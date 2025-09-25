// Global variables for cart management
let currentUserId = localStorage.userId; // This should be set dynamically from your PHP session
let cartItems = new Map(); // Store cart items in memory for faster updates

// Initialize cart on page load
document.addEventListener("DOMContentLoaded", function () {
  loadCartItems();
  fetchUserProfile();
});

// fetch user profile
async function fetchUserProfile() {
  try {
    const res = await fetch("../get_user.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ user_id: currentUserId }),
    });
    if (!res.ok) throw new Error("Failed to fetch profile");
    const user = await res.json();


    document.getElementById("profile-pic").src = '../' + user.data.profile_image || "images/placeholder.svg";
  } catch (err) {
    console.error(err);
  }
}

// Load cart items from database
async function loadCartItems() {
  try {
    const response = await fetch("api/get_cart.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        user_id: currentUserId,
      }),
    });

    const res = await response.json();
    console.log("Cart data:", res.data);
    if (res.success) {
      renderCartItems(res.data.items);
      updateTotals();
    } else {
      showError("Failed to load cart items");
    }
  } catch (error) {
    console.error("Error loading cart:", error);
    showError("Error loading cart items");
  }
}

// Render cart items dynamically
function renderCartItems(items) {
  const cartContainer = document.querySelector(".cart-items");
  const header = cartContainer.querySelector(".items-header");
  const updateBtn = cartContainer.querySelector(".update-cart-btn");

  // Clear existing items (keep header and update button)
  const existingItems = cartContainer.querySelectorAll(".cart-item");
  existingItems.forEach((item) => item.remove());

  items.forEach((item) => {
    cartItems.set(item.id, item);
    const cartItemElement = createCartItemElement(item);
    cartContainer.insertBefore(cartItemElement, updateBtn);
  });
}

// Create cart item HTML element
function createCartItemElement(item) {
  const cartItem = document.createElement("div");
  cartItem.className = "cart-item";
  cartItem.dataset.itemId = item.id;
  cartItem.dataset.userId = item.user_id;

  cartItem.innerHTML = `
                <div class="item-details">
                    <button class="remove-btn" data-item-id="${
                      item.id
                    }">×</button>
                    <div class="item-image" style="background-image: url('../${
                      item.image_url
                    }');background-size: cover;">V</div>
                    <div class="item-info">
                        <h3>${item.voucher_name || "Voucher Name"}</h3>
                    </div>
                </div>
               
                <div class="item-quantity">
                  <div class="quantity-wrapper" data-item-id="${item.id}">
                    <button class="quantity-btn minus">−</button>
                    <span class="quantity-value">${item.quantity}</span>
                    <button class="quantity-btn plus">+</button>
                  </div>
                </div>

                <div class= "item-points">
                <div class="item-points-required" style="text-align: center;">${
                  item.points_required
                }</div> 
                </div>
                
                <div class="item-subtotal">${calculateSubtotal(item)}</div>
            `;

  // Add event listeners for this item
  addItemEventListeners(cartItem);

  return cartItem;
}

// Calculate subtotal for an item
function calculateSubtotal(item) {
  if (typeof item === "object" && item.quantity && item.points_required) {
    return item.quantity * item.points_required;
  }
  // For DOM-based calculation
  const cartItemElement = document.querySelector(
    `[data-item-id="${item.id || item}"]`
  );
  if (cartItemElement) {
    const quantity = parseInt(
      cartItemElement.querySelector(".quantity-value").textContent
    );
    const pointsRequired = parseInt(
      cartItemElement.querySelector(".item-points-required").textContent
    );
    return quantity * pointsRequired;
  }
  return 0;
}

// Add event listeners to cart item
function addItemEventListeners(cartItem) {
  // Remove button
  const removeBtn = cartItem.querySelector(".remove-btn");
  if (removeBtn) {
    removeBtn.addEventListener("click", function () {
      const itemId = this.dataset.itemId;
      removeCartItem(itemId, cartItem);
    });
  }

  const plusBtn = cartItem.querySelector(".quantity-btn.plus");
  const minusBtn = cartItem.querySelector(".quantity-btn.minus");
  const quantityValue = cartItem.querySelector(".quantity-value");
  const subtotalElem = cartItem.querySelector(".item-subtotal");

  function updateQuantityDisplay(newQuantity) {
    quantityValue.textContent = newQuantity;

    // Calculate new subtotal
    const pointsRequired = parseInt(
      cartItem.querySelector(".item-points-required").textContent
    );
    const newSubtotal = newQuantity * pointsRequired;
    subtotalElem.textContent = newSubtotal.toLocaleString();

    // Update totals immediately
    updateTotals();
  }

  if (plusBtn) {
    plusBtn.addEventListener("click", function () {
      let currentQuantity = parseInt(quantityValue.textContent);
      if (currentQuantity < 99) {
        updateQuantityDisplay(currentQuantity + 1);
      }
    });
  }

  if (minusBtn) {
    minusBtn.addEventListener("click", function () {
      let currentQuantity = parseInt(quantityValue.textContent);
      if (currentQuantity > 1) {
        updateQuantityDisplay(currentQuantity - 1);
      }
    });
  }
}

// Remove cart item
async function removeCartItem(itemId, cartItemElement) {
  try {
    // Show loading state
    cartItemElement.style.opacity = "0.5";
    cartItemElement.querySelector(".remove-btn").disabled = true;

    const response = await fetch("api/remove_cart_item.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        id: itemId,
        user_id: currentUserId,
      }),
    });

    const data = await response.json();
    if (data.success) {
      // Animate removal
      cartItemElement.style.animation = "fadeOut 0.5s ease";
      setTimeout(() => {
        cartItemElement.remove();
        cartItems.delete(itemId);
        updateTotals();
        showSuccess("Item removed from cart");
      }, 500);
    } else {
      // Restore if failed
      cartItemElement.style.opacity = "1";
      cartItemElement.querySelector(".remove-btn").disabled = false;
      showError(data.message || "Failed to remove item");
    }
  } catch (error) {
    console.error("Error removing item:", error);
    cartItemElement.style.opacity = "1";
    cartItemElement.querySelector(".remove-btn").disabled = false;
    showError("Error removing item from cart");
  }
}

// Update totals (always based on current DOM values)
function updateTotals() {
  let total = 0;
  let totalItems = 0;

  // Loop through all cart-item DOM elements
  document.querySelectorAll(".cart-item").forEach((cartItem) => {
    const quantity = parseInt(
      cartItem.querySelector(".quantity-value").textContent
    );
    const pointsRequired = parseInt(
      cartItem.querySelector(".item-points-required").textContent
    );
    const subtotal = quantity * pointsRequired;

    total += subtotal;
    totalItems += quantity;
  });

  // Update subtotal row (if exists)
  const subtotalRows = document.querySelectorAll(
    ".summary-row:not(.total) span"
  );
  if (subtotalRows.length >= 2) {
    subtotalRows[1].textContent = total.toLocaleString();
  }

  // Update total row
  const totalRows = document.querySelectorAll(".summary-row.total span");
  if (totalRows.length >= 2) {
    totalRows[1].textContent = total.toLocaleString();
  }

  // Update cart count
  updateCartCount(document.querySelectorAll(".cart-item").length);
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

// Update entire cart - Save current quantities to database
document
  .querySelector(".update-cart-btn")
  .addEventListener("click", async function () {
    const btn = this;
    const originalText = btn.textContent;

    try {
      btn.textContent = "UPDATING...";
      btn.disabled = true;

      // Collect all current quantities from DOM
      const updates = [];
      document.querySelectorAll(".cart-item").forEach((item) => {
        const itemId = item.dataset.itemId;
        const quantity = parseInt(
          item.querySelector(".quantity-value").textContent
        );
        updates.push({ id: itemId, quantity: quantity });
      });

      if (updates.length === 0) {
        showError("No items to update");
        return;
      }

      console.log(JSON.stringify({
          user_id: currentUserId,
          updates: updates,
        }));

      const response = await fetch("api/update_cart.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          user_id: currentUserId,
          updates: updates,
        }),
      });

      const data = await response.json();
      if (data.success) {
        // Update the cartItems map with new quantities
        updates.forEach((update) => {
          const item = cartItems.get(update.id);
          if (item) {
            item.quantity = update.quantity;
          }
        });

        showSuccess("Cart updated successfully");
        // Totals should already be up to date from real-time updates
      } else {
        showError(data.message || "Failed to update cart");
      }
    } catch (error) {
      console.error("Error updating cart:", error);
      showError("Error updating cart");
    } finally {
      btn.textContent = originalText;
      btn.disabled = false;
    }
  });

// Checkout button
document
  .querySelector(".checkout-btn")
  .addEventListener("click", async function () {
    const btn = this;
    btn.style.transform = "scale(0.95)";

    setTimeout(async () => {
      btn.style.transform = "translateY(-2px)";

      // Check if cart is not empty
      const cartItemCount = document.querySelectorAll(".cart-item").length;
      if (cartItemCount === 0) {
        showError("Your cart is empty");
        return;
      }

      // ✅ Confirmation before proceeding
      const confirmed = confirm("Are you sure you want to redeem your cart?");
      if (!confirmed) {
        return; // user cancelled
      }

      try {
        const response = await fetch("api/checkout.php", {
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
          showSuccess("Redeemed successfully!");
          setTimeout(() => {
            window.location.href =
              "../homepage.html";
          }, 1000);
        } else {
          showError(data.message || "Checkout failed");
        }
      } catch (error) {
        console.error("Error during checkout:", error);
        showError("Error during checkout");
      }
    }, 100);
  });


// Utility functions for user feedback
function showSuccess(message) {
  showNotification(message, "success");
}

function showError(message) {
  showNotification(message, "error");
}

function showNotification(message, type) {
  // Create notification element
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

  // Remove after 3 seconds
  setTimeout(() => {
    notification.style.animation = "slideOutRight 0.3s ease";
    setTimeout(() => notification.remove(), 300);
  }, 3000);
}

// Add notification animations
const style = document.createElement("style");
style.textContent = `
            @keyframes fadeOut {
                from { opacity: 1; transform: translateX(0); }
                to { opacity: 0; transform: translateX(-100%); }
            }
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

// common js file
// Logout function
function logout() {
  if (confirm("Are you sure you want to logout?")) {
    localStorage.removeItem("userLoggedIn");
    localStorage.removeItem("userEmail");
    localStorage.removeItem("userName");
    localStorage.removeItem("redeemedVouchers");

    window.location.href = "../authentication_page.html";
  }
}

// Profile dropdown functionality
document.addEventListener("click", function (e) {
  const profile = document.querySelector(".profile");
  if (!profile) return;

  if (profile.contains(e.target)) {
    const isProfileClick =
      e.target.closest(".profile-pic") || e.target.closest(".profile");
    if (isProfileClick) {
      profile.classList.toggle("open");
    }
    return;
  }

  profile.classList.remove("open");
});

document.addEventListener("keydown", function (e) {
  if (e.key === "Escape") {
    const profile = document.querySelector(".profile");
    if (profile) profile.classList.remove("open");
  }
});

function toggleMenu() {
  document.querySelector(".nav-links").classList.toggle("active");
  document.querySelector(".nav-actions").classList.toggle("disactive");
}
