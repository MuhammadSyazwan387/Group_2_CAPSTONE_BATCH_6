let currentUserId = localStorage.getItem('userId');
let currentUserData = null;
const DEFAULT_PROFILE_IMAGE = 'images/placeholder.svg';

// Initialize page
document.addEventListener('DOMContentLoaded', () => {
  loadCartCount();
  initializeProfilePage();
});

async function loadCartCount() {
  if (!currentUserId) {
    console.warn('No user id found for cart count');
    return;
  }

  try {
    const response = await fetch('./cart/api/get_cart_count.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        user_id: currentUserId,
      }),
    });
    const data = await response.json();

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
  const cartCount = document.querySelector('.cart-count');
  if (cartCount) {
    cartCount.textContent = count;
    cartCount.style.display = count > 0 ? 'block' : 'none';
  }
}

// Fetch user profile and populate UI
async function fetchUserProfile() {
  if (!currentUserId) {
    showProfileAlert('No user session found. Please log in again.', true);
    return;
  }

  try {
    const res = await fetch('./get_user.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ user_id: currentUserId }),
    });

    const user = await res.json();
    if (!user.success) {
      throw new Error(user.message || 'Failed to load profile');
    }

    currentUserData = user.data;

  const profileImage = currentUserData.profile_image || DEFAULT_PROFILE_IMAGE;
  currentUserData.profile_image = profileImage;
  document.getElementById('profile-image').src = profileImage;
  document.getElementById('profile-pic').src = profileImage;
    document.getElementById('full-name').textContent = currentUserData.fullname || 'Member Profile';
    document.getElementById('profile-description').textContent = currentUserData.about_me || 'No description available.';
    document.getElementById('name').textContent = currentUserData.fullname || '-';
    document.getElementById('email').textContent = currentUserData.email || '-';
    document.getElementById('phone').textContent = currentUserData.phone_number || '-';
    document.getElementById('address').textContent = currentUserData.address || '-';
    document.getElementById('about').textContent = currentUserData.about_me || '-';
    document.getElementById('points').textContent = currentUserData.points ?? 0;
  } catch (err) {
    console.error('Error fetching profile:', err);
    showProfileAlert(err.message || 'Unable to load profile information.', true);
  }
}

const fetchUserRecentlyRedeemed = async () => {
  if (!currentUserId) return;

  try {
    const response = await fetch('./cart/api/get_cart_history.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        user_id: currentUserId,
        limit: 3,
        offset: 0,
      }),
    });
    const data = await response.json();
    if (data.success) {
      const historyItems = data.data.items || [];
      const totalRedeemed = data.data.pagination?.total ?? historyItems.length;
      const list = document.getElementById('redeemed-list');
      list.innerHTML = '';
      if (historyItems.length > 0) {
        historyItems.forEach((v) => {
          const div = document.createElement('div');
          div.className = 'voucher';
          div.innerHTML = `
            <img src="${v.voucher_image}" alt="${v.voucher_title}"/>
            <div>
              <div class="voucher-title">${v.voucher_title}</div>
              <div class="voucher-date">Redeemed: ${v.completed_date}</div>
            </div>
          `;
          list.appendChild(div);
        });
        document.getElementById('redeemed-count').textContent = totalRedeemed;
      } else {
        list.innerHTML = '<div class="voucher-date">No vouchers redeemed yet.</div>';
      }
    } else {
      console.error('Failed to load history items:', data.message);
    }
  } catch (error) {
    console.error('Error fetching cart history:', error);
  }
};

function initializeProfilePage() {
  const editForm = document.getElementById('editProfileForm');
  const changePasswordForm = document.getElementById('changePasswordForm');
  const changePhotoForm = document.getElementById('changePhotoForm');
  const photoInput = document.getElementById('profileImageInput');

  document.querySelectorAll('[data-close-modal]').forEach((btn) => {
    btn.addEventListener('click', () => closeModal(btn.closest('.modal')));
  });

  document.querySelectorAll('.modal').forEach((modal) => {
    modal.addEventListener('click', (event) => {
      if (event.target === modal) {
        closeModal(modal);
      }
    });
  });

  if (editForm) {
    editForm.addEventListener('submit', handleEditProfileSubmit);
  }

  if (changePasswordForm) {
    changePasswordForm.addEventListener('submit', handleChangePasswordSubmit);
  }

  if (changePhotoForm) {
    changePhotoForm.addEventListener('submit', handleChangePhotoSubmit);
  }

  if (photoInput) {
    photoInput.addEventListener('change', handlePhotoInputChange);
  }

  fetchUserProfile();
  fetchUserRecentlyRedeemed();
}

function openModal(id) {
  const modal = document.getElementById(id);
  if (!modal) return;
  modal.classList.add('active');
  modal.setAttribute('aria-hidden', 'false');
}

function closeModal(modal) {
  if (!modal) return;
  modal.classList.remove('active');
  modal.setAttribute('aria-hidden', 'true');
  const message = modal.querySelector('.form-message');
  if (message) {
    message.textContent = '';
    message.classList.remove('success');
  }
  const form = modal.querySelector('form');
  if (form) {
    form.reset();
  }

  if (modal.id === 'changePhotoModal') {
    resetPhotoPreview();
  }
}

function showProfileAlert(message, isError = false) {
  const alertEl = document.getElementById('profile-alert');
  if (!alertEl) return;
  alertEl.textContent = message;
  alertEl.classList.remove('error', 'hidden', 'show');
  if (isError) {
    alertEl.classList.add('error');
  }
  alertEl.classList.add('show');

  clearTimeout(alertEl.dataset.timeoutId);
  const timeoutId = setTimeout(() => {
    alertEl.classList.remove('show');
  }, 4000);
  alertEl.dataset.timeoutId = timeoutId;
}

function showFormMessage(elementId, message, isSuccess = false) {
  const el = document.getElementById(elementId);
  if (!el) return;
  el.textContent = message;
  el.classList.toggle('success', Boolean(isSuccess));
}

function populateEditProfileForm() {
  if (!currentUserData) return;

  const form = document.getElementById('editProfileForm');
  if (!form) return;

  form.fullname.value = currentUserData.fullname || '';
  form.phone.value = currentUserData.phone_number || '';
  form.address.value = currentUserData.address || '';
  form.about.value = currentUserData.about_me || '';
}

function editProfile() {
  if (!currentUserId) {
    showProfileAlert('Please log in to edit your profile.', true);
    return;
  }
  populateEditProfileForm();
  openModal('editProfileModal');
}

function changePassword() {
  if (!currentUserId) {
    showProfileAlert('Please log in to change your password.', true);
    return;
  }
  const form = document.getElementById('changePasswordForm');
  if (form) {
    form.reset();
  }
  openModal('changePasswordModal');
}

function changeProfilePhoto() {
  if (!currentUserId) {
    showProfileAlert('Please log in to update your profile picture.', true);
    return;
  }

  const previewImage = document.getElementById('photoPreviewImage');
  if (previewImage) {
    previewImage.src = currentUserData?.profile_image || DEFAULT_PROFILE_IMAGE;
  }

  openModal('changePhotoModal');
}

function resetPhotoPreview() {
  const previewImage = document.getElementById('photoPreviewImage');
  if (previewImage) {
    previewImage.src = currentUserData?.profile_image || DEFAULT_PROFILE_IMAGE;
  }
  const message = document.getElementById('changePhotoMessage');
  if (message) {
    message.textContent = '';
    message.classList.remove('success');
  }
}

function handlePhotoInputChange(event) {
  const file = event.target.files?.[0];
  const messageId = 'changePhotoMessage';

  if (!file) {
    showFormMessage(messageId, 'Please choose an image to upload.');
    return;
  }

  const maxSize = 5 * 1024 * 1024; // 5 MB
  if (file.size > maxSize) {
    event.target.value = '';
    resetPhotoPreview();
    showFormMessage(messageId, 'Image file is too large. Maximum size is 5 MB.');
    return;
  }

  if (!file.type.startsWith('image/')) {
    event.target.value = '';
    resetPhotoPreview();
    showFormMessage(messageId, 'Unsupported file type. Please upload an image.');
    return;
  }

  const reader = new FileReader();
  reader.onload = () => {
    const previewImage = document.getElementById('photoPreviewImage');
    if (previewImage && reader.result) {
      previewImage.src = reader.result;
    }
    showFormMessage(messageId, '');
  };
  reader.readAsDataURL(file);
}

async function handleEditProfileSubmit(event) {
  event.preventDefault();
  if (!currentUserId) {
    showFormMessage('editProfileMessage', 'User session not found.', false);
    return;
  }

  const form = event.target;
  const submitBtn = form.querySelector('button[type="submit"]');
  const payload = {
    user_id: currentUserId,
    fullname: form.fullname.value.trim(),
    phone_number: form.phone.value.trim(),
    address: form.address.value.trim(),
    about_me: form.about.value.trim(),
  };

  if (!payload.fullname) {
    showFormMessage('editProfileMessage', 'Full name is required.');
    return;
  }

  try {
    toggleButtonLoading(submitBtn, true, 'Saving...');
    const response = await fetch('./update_user.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
    });

    const data = await response.json();
    if (!data.success) {
      throw new Error(data.message || 'Failed to update profile');
    }

    showFormMessage('editProfileMessage', 'Profile updated successfully.', true);
    await fetchUserProfile();
    showProfileAlert('Personal information updated successfully.');

    setTimeout(() => {
      closeModal(document.getElementById('editProfileModal'));
    }, 800);
  } catch (error) {
    console.error('Error updating profile:', error);
    showFormMessage('editProfileMessage', error.message || 'Unable to update profile.');
  } finally {
    toggleButtonLoading(submitBtn, false, 'Save Changes');
  }
}

async function handleChangePasswordSubmit(event) {
  event.preventDefault();
  if (!currentUserId) {
    showFormMessage('changePasswordMessage', 'User session not found.');
    return;
  }

  const form = event.target;
  const submitBtn = form.querySelector('button[type="submit"]');
  const currentPassword = form.currentPassword.value;
  const newPassword = form.newPassword.value;
  const confirmPassword = form.confirmPassword.value;

  if (!currentPassword || !newPassword || !confirmPassword) {
    showFormMessage('changePasswordMessage', 'All fields are required.');
    return;
  }

  if (newPassword.length < 6) {
    showFormMessage('changePasswordMessage', 'New password must be at least 6 characters long.');
    return;
  }

  if (newPassword !== confirmPassword) {
    showFormMessage('changePasswordMessage', 'New passwords do not match.');
    return;
  }

  try {
    toggleButtonLoading(submitBtn, true, 'Updating...');
    const response = await fetch('./change_password.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        user_id: currentUserId,
        current_password: currentPassword,
        new_password: newPassword,
        confirm_password: confirmPassword,
      }),
    });

    const data = await response.json();
    if (!data.success) {
      throw new Error(data.message || 'Failed to update password');
    }

    showFormMessage('changePasswordMessage', 'Password updated successfully.', true);
    showProfileAlert('Password changed successfully.');

    setTimeout(() => {
      closeModal(document.getElementById('changePasswordModal'));
    }, 800);
  } catch (error) {
    console.error('Error changing password:', error);
    showFormMessage('changePasswordMessage', error.message || 'Unable to change password.');
  } finally {
    toggleButtonLoading(submitBtn, false, 'Update Password');
  }
}

function toggleButtonLoading(button, isLoading, loadingText) {
  if (!button) return;
  if (isLoading) {
    button.dataset.originalText = button.textContent;
    button.textContent = loadingText;
    button.disabled = true;
  } else {
    button.textContent = button.dataset.originalText || button.textContent;
    button.disabled = false;
  }
}

async function handleChangePhotoSubmit(event) {
  event.preventDefault();
  if (!currentUserId) {
    showFormMessage('changePhotoMessage', 'User session not found.');
    return;
  }

  const form = event.target;
  const fileInput = form.profileImage;
  if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
    showFormMessage('changePhotoMessage', 'Please select an image to upload.');
    return;
  }

  const file = fileInput.files[0];
  if (!file.type.startsWith('image/')) {
    showFormMessage('changePhotoMessage', 'Unsupported file type. Please upload an image.');
    return;
  }

  const maxSize = 5 * 1024 * 1024;
  if (file.size > maxSize) {
    showFormMessage('changePhotoMessage', 'Image file is too large. Maximum size is 5 MB.');
    return;
  }

  const submitBtn = form.querySelector('button[type="submit"]');
  const formData = new FormData();
  formData.append('user_id', currentUserId);
  formData.append('profile_image', file);

  try {
    toggleButtonLoading(submitBtn, true, 'Uploading...');

    const response = await fetch('./upload_profile_picture.php', {
      method: 'POST',
      body: formData,
    });

    const data = await response.json();
    if (!data.success) {
      throw new Error(data.message || 'Failed to upload profile picture');
    }

    showFormMessage('changePhotoMessage', 'Profile picture updated.', true);
    await fetchUserProfile();
    showProfileAlert('Profile picture updated successfully.');

    setTimeout(() => {
      closeModal(document.getElementById('changePhotoModal'));
    }, 800);
  } catch (error) {
    console.error('Error uploading profile picture:', error);
    showFormMessage('changePhotoMessage', error.message || 'Unable to update profile picture.');
  } finally {
    toggleButtonLoading(submitBtn, false, 'Upload Photo');
  }
}
