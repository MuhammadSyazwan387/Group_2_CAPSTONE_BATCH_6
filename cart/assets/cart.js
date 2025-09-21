// Add interactive functionality
document.querySelectorAll(".remove-btn").forEach((btn) => {
  btn.addEventListener("click", function () {
    const cartItem = this.closest(".cart-item");
    cartItem.style.animation = "fadeOut 0.5s ease";
    setTimeout(() => {
      cartItem.remove();
      updateTotals();
    }, 500);
  });
});

function updateTotals() {
  const items = document.querySelectorAll(".cart-item");
  let total = 0;

  items.forEach((item) => {
    const subtotal = parseInt(item.querySelector(".item-subtotal").textContent);
    total += subtotal;
  });

  document.querySelectorAll(".summary-row span")[1].textContent = total;
  document.querySelectorAll(".summary-row.total span")[1].textContent = total;
}

// Add fade out animation
const style = document.createElement("style");
style.textContent = `
            @keyframes fadeOut {
                from { opacity: 1; transform: translateX(0); }
                to { opacity: 0; transform: translateX(-100%); }
            }
        `;
document.head.appendChild(style);

// Add smooth scrolling and interactive elements
document.querySelector(".checkout-btn").addEventListener("click", function () {
  this.style.transform = "scale(0.95)";
  setTimeout(() => {
    this.style.transform = "translateY(-2px)";
  }, 100);
});

document
  .querySelector(".update-cart-btn")
  .addEventListener("click", function () {
    this.textContent = "UPDATING...";
    setTimeout(() => {
      this.textContent = "UPDATE CART";
    }, 1500);
  });


// common js file
// Logout function
function logout() {
    if (confirm('Are you sure you want to logout?')) {
        localStorage.removeItem('userLoggedIn');
        localStorage.removeItem('userEmail');
        localStorage.removeItem('userName');
        localStorage.removeItem('redeemedVouchers');
        
        window.location.href = 'authentication_page.html';
    }
}

// Fetch and display vouchers
async function loadVouchers() {
    try {
        console.log('Attempting to fetch vouchers...');
        const response = await fetch('voucher.php');
        console.log('Response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const vouchers = await response.json();
        console.log('Vouchers data:', vouchers);
        
        if (vouchers.error) {
            console.error('Database error:', vouchers.error);
            showError('Database error: ' + vouchers.error);
            return;
        }
        
        const voucherGrid = document.getElementById('voucher-grid');
        
        // Clear loading text
        voucherGrid.innerHTML = '';
        
        // Check if we have vouchers
        if (!vouchers || vouchers.length === 0) {
            showError('No vouchers found in database');
            return;
        }
        
        // Display only first 3 vouchers (as per your requirement)
        vouchers.slice(0, 3).forEach(voucher => {
            const voucherCard = createVoucherCard(voucher);
            voucherGrid.appendChild(voucherCard);
        });
        
    } catch (error) {
        console.error('Error loading vouchers:', error);
        showError('Error: ' + error.message);
    }
}

function createVoucherCard(voucher) {
    const card = document.createElement('div');
    card.className = 'product-card';
    
    // Log the image path for debugging
    console.log('Loading image:', voucher.image);
    
    card.innerHTML = `
        <div class="image">
            <img src="${voucher.image}" alt="${voucher.title}" 
                 onload="console.log('Image loaded successfully:', '${voucher.image}')"
                 onerror="console.log('Image failed to load:', '${voucher.image}'); this.src='images/placeholder.jpg';">
        </div>
        <h3>${voucher.title}</h3>
        <button class="btn small" onclick="viewVoucherDetails(${voucher.id})">View Details</button>
    `;
    
    return card;
}

function viewVoucherDetails(voucherId) {
    // Redirect to voucher details page with the voucher ID
    window.location.href = `voucher_details.html?id=${voucherId}`;
}

function showError(message) {
    const voucherGrid = document.getElementById('voucher-grid');
    
    voucherGrid.innerHTML = '<div class="error">' + (message || 'Unable to load vouchers') + '</div>';
}

// Profile dropdown functionality
document.addEventListener('click', function(e) {
    const profile = document.querySelector('.profile');
    if (!profile) return;

    if (profile.contains(e.target)) {
        const isProfileClick = e.target.closest('.profile-pic') || e.target.closest('.profile');
        if (isProfileClick) {
            profile.classList.toggle('open');
        }
        return;
    }

    profile.classList.remove('open');
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const profile = document.querySelector('.profile');
        if (profile) profile.classList.remove('open');
    }
});

// Load vouchers when page loads
document.addEventListener('DOMContentLoaded', loadVouchers);