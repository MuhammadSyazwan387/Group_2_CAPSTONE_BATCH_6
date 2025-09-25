<?php
// Handle AJAX request for vouchers by category
if (isset($_GET['action']) && $_GET['action'] === 'get_vouchers') {
    $category = isset($_GET['category']) ? $_GET['category'] : '';
    // Example static data, replace with database query
    $all_vouchers = [
        'Shopping' => [
            ['id'=>1, 'title'=>'50% Off Zara', 'image'=>'images/zara.jpg'],
            ['id'=>2, 'title'=>'RM100 Lazada Voucher', 'image'=>'images/lazada.jpg'],
        ],
        'Food & Dining' => [
            ['id'=>3, 'title'=>'Free Starbucks Drink', 'image'=>'images/starbucks.jpg'],
            ['id'=>4, 'title'=>'20% Off KFC', 'image'=>'images/kfc.jpg'],
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
  <style>
  /* --- Embedded CSS --- */
  body {
    margin: 0;
    font-family: 'Segoe UI', Arial, sans-serif;
    background: #f4f6fb;
    color: #222;
  }
  .navbar {
    background: #003366;
    color: #fff;
    padding: 0.8rem 2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .logo {
    font-size: 1.7rem;
    font-weight: bold;
    letter-spacing: 1px;
  }
  .logo span {
    color: #ffb300;
  }
  .nav-links a {
    color: #fff;
    text-decoration: none;
    margin: 0 1.2rem;
    font-weight: 500;
    transition: color 0.2s;
  }
  .nav-links a.active,
  .nav-links a:hover {
    color: #ffb300;
  }
  .category-hero {
    text-align: center;
    padding: 2.5rem 0 1.5rem 0;
    background: linear-gradient(90deg,#003366 70%,#ffb300 130%);
    color: #fff;
  }
  .category-hero h1 {
    font-size: 2.2rem;
    margin-bottom: 0.6rem;
  }
  .category-hero p {
    font-size: 1.1rem;
    margin-bottom: 0.2rem;
  }
  .categories-list {
    max-width: 1100px;
    margin: 2rem auto;
    padding: 0 1.2rem;
  }
  .category-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit,minmax(220px,1fr));
    gap: 1.7rem;
  }
  .category-card {
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 2px 10px #00336620;
    padding: 2rem 1.2rem 1.3rem 1.2rem;
    text-align: center;
    transition: transform 0.18s, box-shadow 0.18s;
    cursor: pointer;
    position: relative;
  }
  .category-card:hover {
    transform: translateY(-6px) scale(1.03);
    box-shadow: 0 4px 20px #00336630;
    z-index: 2;
  }
  .category-card span {
    font-size: 2.5rem;
    display: block;
    margin-bottom: 0.6rem;
  }
  .category-card h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.3rem;
    color: #003366;
  }
  .category-card p {
    font-size: 0.95rem;
    color: #555;
    margin-bottom: 0.2rem;
  }
  .vouchers-section {
    max-width: 1100px;
    margin: 2.5rem auto 2rem auto;
    padding: 0 1.2rem;
  }
  .vouchers-section h2 {
    font-size: 1.5rem;
    color: #003366;
    margin-bottom: 1rem;
  }
  .voucher-list {
    display: grid;
    grid-template-columns: repeat(auto-fit,minmax(240px,1fr));
    gap: 1.5rem;
  }
  .voucher-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 8px #00336622;
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
  }
  .voucher-card button {
    background: #003366;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 0.5rem 1.2rem;
    font-size: 1rem;
    cursor: pointer;
    transition: background 0.18s;
  }
  .voucher-card button:hover {
    background: #ffb300;
    color: #003366;
  }
  .loading, .error {
    text-align: center;
    color: #888;
    font-size: 1.1rem;
    padding: 2rem 0;
  }
  .footer {
    background: #003366;
    color: #fff;
    padding: 1.2rem 0 0.5rem 0;
    text-align: center;
    margin-top: 2.5rem;
  }
  .footer-bottom {
    font-size: 1rem;
    opacity: 0.8;
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
    <a href="#" class="active">Categories</a>
    <a href="#">Shop</a>
  </nav>
</header>

<!-- Category Hero -->
<section class="category-hero">
  <h1>Browse Voucher Categories</h1>
  <p>Find the best vouchers in your favorite category!</p>
</section>

<!-- Categories Grid -->
<section class="categories-list">
  <div class="category-grid">
    <div class="category-card" data-category="Shopping">
      <span>🛍️</span>
      <h3>Shopping</h3>
      <p>Save big on fashion, electronics and more.</p>
    </div>
    <div class="category-card" data-category="Food & Dining">
      <span>🍔</span>
      <h3>Food & Dining</h3>
      <p>Exclusive foodie deals and restaurant vouchers.</p>
    </div>
    <div class="category-card" data-category="Entertainment">
      <span>🎬</span>
      <h3>Entertainment</h3>
      <p>Movies, events, and fun activities for all ages.</p>
    </div>
    <div class="category-card" data-category="Gift Cards">
      <span>🎁</span>
      <h3>Gift Cards</h3>
      <p>The perfect gift for every occasion.</p>
    </div>
    <div class="category-card" data-category="Travel">
      <span>✈️</span>
      <h3>Travel</h3>
      <p>Discounts on hotels, flights, and holidays.</p>
    </div>
    <div class="category-card" data-category="Health & Wellness">
      <span>💪</span>
      <h3>Health & Wellness</h3>
      <p>Fitness, spa, and wellness vouchers.</p>
    </div>
  </div>
</section>

<!-- Vouchers Section -->
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