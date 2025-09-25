currentUserId = localStorage.userId;

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

// fetch user profile
async function fetchUserProfile() {
  try {
    const res = await fetch("./get_user.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ user_id: currentUserId }),
    });
    if (!res.ok) throw new Error("Failed to fetch profile");
    const user = await res.json();
    console.log(user);

    // Fill UI
    document.getElementById("profile-image").src =
      user.data.profile_image || "images/placeholder.svg";
    document.getElementById("full-name").textContent = user.data.fullname;
    document.getElementById("profile-description").textContent =
      user.data.about_me || "No description available.";
    document.getElementById("name").textContent = user.data.fullname;
    document.getElementById("email").textContent = user.data.email;
    document.getElementById("phone").textContent = user.data.phone_number;
    document.getElementById("address").textContent = user.data.address;
    document.getElementById("about").textContent = user.data.about_me;
    document.getElementById("points").textContent = user.data.points;

    document.getElementById("profile-pic").src = user.data.profile_image || "images/placeholder.svg";
  } catch (err) {
    console.error(err);
  }
}

fetchUserRecentlyRedeemed = async () => {
  try {
    const response = await fetch("./cart/api/get_cart_history.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        user_id: currentUserId,
        limit: 3,
        offset: 0,
      }),
    });
    const data = await response.json();
    if (data.success) {
      historyItems = data.data.items;
      total_redeemed = data.data.pagination.total;
      console.log(historyItems);
      // Redeemed vouchers
      const list = document.getElementById("redeemed-list");
      list.innerHTML = "";
      if (historyItems.length > 0) {
        historyItems.forEach((v) => {
          const div = document.createElement("div");
          div.className = "voucher";
          div.innerHTML = `
              <img src="${v.voucher_image}" alt="${v.voucher_title}"/>
              <div>
                <div class="voucher-title">${v.voucher_title}</div>
                <div class="voucher-date">Redeemed: ${v.completed_date}</div>
              </div>
            `;
          list.appendChild(div);
        });
        document.getElementById("redeemed-count").textContent = total_redeemed;
      }
    } else {
      showError("Failed to load history items");
      showEmptyState(true);
    }
  } catch (error) {
    console.error("Error fetching cart history:", error);
    showError("An error occurred while fetching cart history");
    showEmptyState(true);
  }
};

// Dummy handlers
function editProfile() {
  alert("Open edit profile modal");
}
function changePassword() {
  alert("Open change password modal");
}

// Initialize page
document.addEventListener("DOMContentLoaded", function () {
    fetchUserProfile();
    fetchUserRecentlyRedeemed();
});
