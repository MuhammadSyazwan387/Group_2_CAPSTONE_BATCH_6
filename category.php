 <?php
// Handle AJAX request for vouchers by category
if (isset($_GET['action']) && $_GET['action'] === 'get_vouchers') {
    $category = isset($_GET['category']) ? $_GET['category'] : '';
    $all_vouchers = [
        'Shopping' => [
            ['id'=>1, 'title'=>'50% Off Zara', 'image'=>'images/zara.jpg'],
            ['id'=>2, 'title'=>'RM100 Lazada Voucher', 'image'=>'images/lazada.jpg'],
        ],
        'Food & Dining' => [
            ['id'=>3, 'title'=>'Free Tealive Drink', 'image'=>'images/tealive.png'],
            ['id'=>4, 'title'=>'20% Off KFC', 'image'=>'images/kfc.png'],
        ],
        'Entertainment' => [
            ['id'=>5, 'title'=>'Cinema Ticket', 'image'=>'images/cinema.jpg'],
        ],
        'Gift Cards' => [
            ['id'=>6, 'title'=>'RM50 Gift Card', 'image'=>'images/giftcard.jpg'],
        ],
        'Travel' => [
            ['id'=>7, 'title'=>'Hotel Discount', 'image'=>'images/hotel.jpg'],
        ],
        'Health & Wellness' => [
            ['id'=>8, 'title'=>'Spa Voucher', 'image'=>'images/spa.jpg'],
        ],
    ];
    $vouchers = $all_vouchers[$category] ?? [];
    header('Content-Type: application/json');
    echo json_encode($vouchers);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Voucher Categories - Optima Bank</title>
  <link rel="stylesheet" href="style.css"/>
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', Arial, sans-serif;
      background: #21002c;
      color: #fff;
    }
    /* NAVBAR from homepage */
    .navbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 40px;
  background: #111;
  color: white;
  position: sticky;
  top: 0;
  z-index: 1000;
}

.logo {
  font-size: 1.5rem;
  font-weight: bold;
}

.logo span {
  background: linear-gradient(135deg, #ff4da6, #ffffff);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.nav-links {
  display: flex;
  gap: 20px;
}

.nav-links a {
  text-decoration: none;
  color: #bbb;
  transition: color 0.3s ease;
}

.nav-links a.active,
.nav-links a:hover {
  color: #ff4da6;
}

.nav-actions {
  display: flex;
  align-items: center;
  gap: 20px;
}

   /* Search Box */
.search-box {
  display: flex;
  align-items: center;
  background: #222;
  border-radius: 20px;
  padding: 5px 5px;
}

.search-box input {
  border: none;
  outline: none;
  background: transparent;
  color: white;
  padding: 5px;
}

.search-box button {
  background: none;
  border: none;
  cursor: pointer;
  color: #ff4da6;
  font-size: 1rem;
}

/* Cart */
.cart {
  position: relative;
  font-size: 1.2rem;
  cursor: pointer;
}

.cart-count {
  position: absolute;
  top: -8px;
  right: -10px;
  background: #ff4da6;
  color: white;
  font-size: 0.7rem;
  padding: 2px 6px;
  border-radius: 50%;
}

/* Profile */
.profile {
  position: relative;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.profile-pic {
  width: 35px;
  height: 35px;
  border-radius: 50%;
  object-fit: cover;
  background: #444;
  display: block;
}

/* Dropdown */
.dropdown {
  display: none;
  position: absolute;
  right: 0;
  top: 100%;
  margin-top: 2px;
  background: #040005;
  border-radius: 8px;
  overflow: hidden;
  min-width: 160px;
  box-shadow: 0 4px 8px rgba(79, 41, 66, 0.4);
  z-index: 1000;
}

.dropdown a {
  display: block;
  padding: 10px;
  text-decoration: none;
  color: #f4eef3;
  transition: background 0.1s;
}

.dropdown a:hover {
  background: #fb34ab;
  color: #f3edf0;
}

/* Show dropdown when hovering profile */
.profile:hover .dropdown {
  display: block;
}

    /* HERO */
    .category-hero {
      text-align: center;
      padding: 2.5rem 0 1.5rem 0;
      background: linear-gradient(90deg,#6824a8 70%,#ffb4eb 130%);
      color: #fff;
    }
    .category-hero h1 {
      font-size: 2.2rem;
      margin-bottom: 0.6rem;
      color: #ffb4eb;
      letter-spacing: 1px;
      font-weight: 700;
    }
    .category-hero p {
      font-size: 1.18rem;
      margin-bottom: 0.2rem;
      color: #fff;
    }
    /* CATEGORY GRID (match old wording/sizing but new colors) */
    .category-section {
      max-width: 1100px;
      margin: 2.8rem auto 2.2rem auto;
      padding: 0 1.2rem;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .category-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      grid-template-rows: repeat(2, 1fr);
      gap: 2rem;
      width: 100%;
      justify-items: center;
    }
    .category-card {
      background: #1a0124;
      border-radius: 20px;
      box-shadow: 0 2px 14px #6824a844;
      width: 240px;
      height: 180px;
      text-align: center;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 1.7rem 1rem 1rem 1rem;
      cursor: pointer;
      border: 2px solid #6824a8;
      transition: transform 0.14s, box-shadow 0.14s;
    }
    .category-card:hover {
      transform: translateY(-6px) scale(1.05);
      box-shadow: 0 6px 28px #ffb4eb77;
      border-color: #ffb4eb;
      background: #36003c;
    }
    .category-card .icon {
      font-size: 2.6rem;
      margin-bottom: 0.6rem;
      display: block;
    }
    .category-card .title {
      font-size: 1.3rem;
      font-weight: 700;
      margin-bottom: 0.6rem;
      color: #fff;
      font-family: inherit;
      letter-spacing: 0.4px;
    }
    .category-card .desc {
      font-size: 1rem;
      color: #ffb4eb;
      font-family: inherit;
      margin-bottom: 0;
      line-height: 1.35;
      font-weight: 400;
    }
    /* Featured Vouchers Section */
    .vouchers-section {
      max-width: 1100px;
      margin: 2.5rem auto 2rem auto;
      padding: 0 1.2rem;
    }
    .vouchers-section h2 {
      font-size: 1.5rem;
      color: #ffb4eb;
      margin-bottom: 1.4rem;
      text-align: center;
      font-family: inherit;
      letter-spacing: 1px;
    }
    .voucher-list {
      display: grid;
      grid-template-columns: repeat(auto-fit,minmax(240px,1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }
    .voucher-card {
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 2px 8px #6824a8;
      padding: 1.2rem;
      text-align: center;
      transition: box-shadow 0.18s;
    }
    .voucher-card img {
      width: 100%;
      height: 120px;
      object-fit: cover;
      border-radius: 9px;
      margin-bottom: 0.7rem;
      background: #eee;
    }
    .voucher-card h3 {
      font-size: 1.1rem;
      margin-bottom: 0.5rem;
      color: #35003c;
      font-family: inherit;
      letter-spacing: 1px;
    }
    .voucher-card button {
      background: #6824a8;
      color: #fff;
      border: none;
      border-radius: 6px;
      padding: 0.5rem 1.2rem;
      font-size: 1rem;
      cursor: pointer;
      transition: background 0.18s;
    }
    .voucher-card button:hover {
      background: #ffb4eb;
      color: #6824a8;
    }
    .loading, .error {
      text-align: center;
      color: #ffb4eb;
      font-size: 1.1rem;
      padding: 2rem 0;
    }
    .footer {
      background: #111;
      color: #fff;
      padding: 1.2rem 0 0.5rem 0;
      text-align: center;
      margin-top: 2.5rem;
    }
    .footer-bottom {
      font-size: 1rem;
      opacity: 0.8;
    }
    @media (max-width: 900px) {
      .category-card {
        width: 40vw;
        min-width: 120px;
        height: 140px;
        font-size: 1rem;
      }
      .category-grid {
        gap: 1rem;
      }
    }
    @media (max-width: 700px) {
      .category-grid {
        grid-template-columns: 1fr;
        grid-template-rows: repeat(6, 1fr);
      }
      .category-card {
        width: 90vw;
        height: 120px;
        font-size: 1rem;
      }
    }
  </style>
</head>
<body>
<!-- Navbar -->
<header class="navbar">
  <div class="logo">Optima<span>Bank</span></div>
  <nav class="nav-links">
    <a href="homepage.html">Home</a>
    <a href="#">About</a>
    <a href="#">Shop</a>
  </nav>
  <div class="nav-actions">
    <div class="search-box">
      <input type="text" placeholder="Search..." />
      <button type="submit">🔍</button>
    </div>
    <div class="cart">
      🛒
      <span class="cart-count">2</span>
    </div>
    <div class="profile">
      <img src="https://www.freepik.com/icon/profile_6878865" alt="Profile" class="profile-pic"/>
      <div class="dropdown">
        <a href="profile.html">My Account</a>
        <a href="#">Orders</a>
        <a href="#">Settings</a>
        <a href="#" onclick="logout()">Logout</a>
      </div>
    </div>
  </div>
</header>

<!-- Hero Section -->
<section class="category-hero">
  <h1>Browse Voucher Categories</h1>
  <p>Find the best vouchers in your favorite category!</p>
</section>

<!-- Categories Grid (centered, old wording/sizing, new colors) -->
<section class="category-section">
  <div class="category-grid">
    <div class="category-card" data-category="Shopping">
      <span class="icon">🛍️</span>
      <div class="title">Shopping</div>
      <div class="desc">Save big on fashion, electronics and more.</div>
    </div>
    <div class="category-card" data-category="Food & Dining">
      <span class="icon">🍔</span>
      <div class="title">Food & Dining</div>
      <div class="desc">Exclusive foodie deals and restaurant vouchers.</div>
    </div>
    <div class="category-card" data-category="Entertainment">
      <span class="icon">🎬</span>
      <div class="title">Entertainment</div>
      <div class="desc">Movies, events, and fun activities for all ages.</div>
    </div>
    <div class="category-card" data-category="Gift Cards">
      <span class="icon">🎁</span>
      <div class="title">Gift Cards</div>
      <div class="desc">The perfect gift for every occasion.</div>
    </div>
    <div class="category-card" data-category="Travel">
      <span class="icon">✈️</span>
      <div class="title">Travel</div>
      <div class="desc">Discounts on hotels, flights, and holidays.</div>
    </div>
    <div class="category-card" data-category="Health & Wellness">
      <span class="icon">💪</span>
      <div class="title">Health & Wellness</div>
      <div class="desc">Fitness, spa, and wellness vouchers.</div>
    </div>
  </div>
</section>

<!-- Featured Vouchers Section -->
<section class="vouchers-section">
  <h2 id="vouchers-title">Featured Vouchers</h2>
  <div class="voucher-list" id="voucher-list">
    <div class="loading">Select a category to view vouchers.</div>
  </div>
</section>

<!-- Footer -->
<footer class="footer">
  <div class="footer-bottom">
    <p>© 2025 Optima Bank. All rights reserved.</p>
  </div>
</footer>

<script>
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

function logout() {
    if (confirm('Are you sure you want to logout?')) {
        localStorage.removeItem('userLoggedIn');
        localStorage.removeItem('userEmail');
        localStorage.removeItem('userName');
        localStorage.removeItem('redeemedVouchers');
        window.location.href = 'authentication_page.html';
    }
}

// On category click, fetch vouchers for that category
document.querySelectorAll('.category-card').forEach(card => {
  card.addEventListener('click', function() {
    const category = this.getAttribute('data-category');
    document.getElementById('vouchers-title').innerText = category + " Vouchers";
    loadVouchers(category);
  });
});

function loadVouchers(category) {
  const list = document.getElementById('voucher-list');
  list.innerHTML = '<div class="loading">Loading vouchers...</div>';
  fetch(`category.php?action=get_vouchers&category=${encodeURIComponent(category)}`)
    .then(r => r.json())
    .then(vouchers => {
      list.innerHTML = '';
      if (!vouchers.length) {
        list.innerHTML = '<div class="error">No vouchers found for this category.</div>';
        return;
      }
      vouchers.forEach(v => {
        const card = document.createElement('div');
        card.className = 'voucher-card';
        card.innerHTML = `
          <img src="${v.image}" alt="${v.title}" onerror="this.src='images/placeholder.jpg';" />
          <h3>${v.title}</h3>
          <button onclick="window.location.href='voucher_details.html?id=${v.id}'">View Details</button>
        `;
        list.appendChild(card);
      });
    })
    .catch(e => {
      list.innerHTML = '<div class="error">Unable to load vouchers.</div>';
    });
}
</script>
</body>
</html>