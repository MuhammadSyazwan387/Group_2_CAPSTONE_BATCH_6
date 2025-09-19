// Dummy voucher codes for testing
const validVouchers = {
    'SAVE2024': { value: '$50 Shopping Credit', type: 'Shopping' },
    'FOOD25': { value: '$25 Food Delivery', type: 'Food' },
    'GAME100': { value: '$100 Gaming Credit', type: 'Gaming' },
    'WELCOME10': { value: '$10 Welcome Bonus', type: 'Bonus' },
    'MOVIE15': { value: '$15 Movie Tickets', type: 'Entertainment' }
};

function logout() {
    if (confirm('Are you sure you want to logout?')) {
        localStorage.removeItem('userLoggedIn');
        localStorage.removeItem('userEmail');
        localStorage.removeItem('userName');
        localStorage.removeItem('redeemedVouchers');
        
        window.location.href = 'authentication_page.html';
    }
}

function redeemVoucher(code) {
    const resultDiv = document.getElementById('redemptionResult');
    const upperCode = code.toUpperCase();
    
    // Check if voucher exists
    if (!validVouchers[upperCode]) {
        showRedemptionResult('Invalid voucher code. Please check and try again.', 'error');
        return;
    }
    
    // Check if already redeemed
    const redeemedVouchers = JSON.parse(localStorage.getItem('redeemedVouchers') || '[]');
    if (redeemedVouchers.includes(upperCode)) {
        showRedemptionResult('This voucher has already been redeemed.', 'error');
        return;
    }
    
    // Redeem voucher
    const voucher = validVouchers[upperCode];
    redeemedVouchers.push(upperCode);
    localStorage.setItem('redeemedVouchers', JSON.stringify(redeemedVouchers));
    
    showRedemptionResult(`🎉 Success! You've redeemed ${voucher.value} (${voucher.type})`, 'success');
    
    // Clear the input
    document.getElementById('voucherCode').value = '';
    
    // Add to recent activity (simulation)
    addRecentRedemption(voucher, upperCode);
}

function showRedemptionResult(message, type) {
    const resultDiv = document.getElementById('redemptionResult');
    resultDiv.textContent = message;
    resultDiv.className = `redemption-result ${type}`;
    resultDiv.classList.remove('hidden');
    
    // Hide after 5 seconds
    setTimeout(() => {
        resultDiv.classList.add('hidden');
    }, 5000);
}

function addRecentRedemption(voucher, code) {
    const activityList = document.querySelector('.activity-list');
    const newActivity = document.createElement('div');
    newActivity.className = 'activity-item';
    newActivity.innerHTML = `
        <div class="activity-icon">🎟️</div>
        <div class="activity-details">
            <h4>${voucher.type} Voucher - ${voucher.value}</h4>
            <p>Just now • Code: ${code}</p>
        </div>
    `;
    
    // Add to top of list
    activityList.insertBefore(newActivity, activityList.firstChild);
    
    // Remove last item if more than 5 items
    if (activityList.children.length > 5) {
        activityList.removeChild(activityList.lastChild);
    }
}

// Feature button handlers with voucher-specific functionality
function handleFeatureClick(featureTitle) {
    switch(featureTitle) {
        case 'Available Vouchers':
            showAvailableVouchers();
            break;
        case 'Redemption History':
            showRedemptionHistory();
            break;
        case 'Rewards Balance':
            showRewardsBalance();
            break;
        case 'Notifications':
            alert('Notification settings would be implemented here.');
            break;
        default:
            alert(`${featureTitle} feature would be implemented here.`);
    }
}

function showAvailableVouchers() {
    const voucherList = Object.keys(validVouchers).map(code => {
        const voucher = validVouchers[code];
        return `${code}: ${voucher.value} (${voucher.type})`;
    }).join('\n');
    
    alert(`Available Voucher Codes:\n\n${voucherList}\n\nTry entering one of these codes!`);
}

function showRedemptionHistory() {
    const redeemedVouchers = JSON.parse(localStorage.getItem('redeemedVouchers') || '[]');
    
    if (redeemedVouchers.length === 0) {
        alert('No vouchers redeemed yet. Try redeeming a voucher code!');
        return;
    }
    
    const historyList = redeemedVouchers.map(code => {
        const voucher = validVouchers[code];
        return `${code}: ${voucher.value} (${voucher.type})`;
    }).join('\n');
    
    alert(`Your Redemption History:\n\n${historyList}`);
}

function showRewardsBalance() {
    const redeemedVouchers = JSON.parse(localStorage.getItem('redeemedVouchers') || '[]');
    const totalRewards = redeemedVouchers.length;
    const totalValue = redeemedVouchers.reduce((sum, code) => {
        const voucher = validVouchers[code];
        if (voucher && voucher.value.includes('$')) {
            const value = parseInt(voucher.value.match(/\$(\d+)/)[1]);
            return sum + value;
        }
        return sum;
    }, 0);
    
    alert(`Rewards Summary:\n\nVouchers Redeemed: ${totalRewards}\nTotal Value: $${totalValue}\nStatus: ${totalRewards >= 3 ? 'Gold Member' : 'Silver Member'}`);
}

document.addEventListener('DOMContentLoaded', function() {
    // Check authentication
    const userLoggedIn = localStorage.getItem('userLoggedIn');
    if (!userLoggedIn) {
        window.location.href = 'authentication_page.html';
        return;
    }
    
    // Display user information
    const userName = localStorage.getItem('userName');
    const userPoints = localStorage.getItem('userPoints');
    
    if (userName) {
        const welcomeText = document.querySelector('.welcome-text');
        if (welcomeText) {
            welcomeText.textContent = `Welcome back, ${userName}!`;
        }
    }
    
    // Voucher form handler
    document.getElementById('voucherForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const code = document.getElementById('voucherCode').value.trim();
        if (code) {
            redeemVoucher(code);
        }
    });
    
    // Feature button handlers
    const featureButtons = document.querySelectorAll('.feature-btn');
    featureButtons.forEach(button => {
        button.addEventListener('click', function() {
            const featureTitle = this.parentElement.querySelector('h3').textContent;
            handleFeatureClick(featureTitle);
        });
    });
    
    console.log('Optima Bank voucher redemption system loaded successfully');
});
