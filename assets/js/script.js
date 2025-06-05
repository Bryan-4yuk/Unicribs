// filepath: c:/xampp/htdocs/UNICRIBS/assets/js/script.js
document.addEventListener('DOMContentLoaded', function () {
    // Utility: Add event listener if element exists
    function on(id, event, handler) {
        const el = document.getElementById(id);
        if (el) el.addEventListener(event, handler);
    }

    // Mobile menu toggle
    on('mobileMenuBtn', 'click', function () {
        const mobileMenu = document.getElementById('mobileMenu');
        if (mobileMenu) mobileMenu.classList.toggle('hidden');
    });

    // Navbar scroll effect
    const navbar = document.getElementById('navbar');
    if (navbar) {
        function updateNavbar() {
            if (window.scrollY > 10) {
                navbar.classList.remove('nav-transparent');
                navbar.classList.add('nav-solid');
            } else {
                navbar.classList.add('nav-transparent');
                navbar.classList.remove('nav-solid');
            }
        }
        window.addEventListener('scroll', updateNavbar);
        updateNavbar();
    }

    // Sidebar Toggle
    on('sidebarToggle', 'click', function () {
        const sidebar = document.getElementById('sidebar');
        if (sidebar) sidebar.classList.toggle('sidebar-collapsed');
    });

    // Mobile Footer Active State
    document.querySelectorAll('.mobile-footer-item').forEach(item => {
        item.addEventListener('click', function () {
            document.querySelectorAll('.mobile-footer-item').forEach(i => i.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // Like Button Functionality
    document.querySelectorAll('.like-btn').forEach(button => {
        button.addEventListener('click', function () {
            this.classList.toggle('active');
            const countElement = this.querySelector('.like-count');
            if (countElement) {
                let count = parseInt(countElement.textContent, 10);
                count = this.classList.contains('active') ? count + 1 : count - 1;
                countElement.textContent = count;
            }
        });
    });

    // Image Upload Preview
    document.querySelectorAll('.image-upload-input').forEach(input => {
        input.addEventListener('change', function (e) {
            const previewContainer = this.closest('.image-upload')?.querySelector('.image-preview-container');
            if (!previewContainer) return;
            previewContainer.innerHTML = '';
            Array.from(e.target.files).slice(0, 5).forEach(file => {
                const reader = new FileReader();
                reader.onload = function (event) {
                    const preview = document.createElement('div');
                    preview.className = 'relative';
                    const img = document.createElement('img');
                    img.src = event.target.result;
                    img.className = 'image-preview mr-2 mb-2';
                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center';
                    removeBtn.innerHTML = '×';
                    removeBtn.addEventListener('click', function () {
                        preview.remove();
                    });
                    preview.appendChild(img);
                    preview.appendChild(removeBtn);
                    previewContainer.appendChild(preview);
                };
                reader.readAsDataURL(file);
            });
        });
    });

    // Room Gallery Slider
    document.querySelectorAll('.room-gallery').forEach(gallery => {
        const mainImage = gallery.querySelector('.main-image');
        const thumbnails = gallery.querySelectorAll('.thumbnail');
        thumbnails.forEach(thumb => {
            thumb.addEventListener('click', function () {
                if (mainImage) mainImage.src = this.src;
                thumbnails.forEach(t => t.classList.remove('border-primary'));
                this.classList.add('border-primary');
            });
        });
    });

    // Comment Reply Toggle (handled in setupCommentSystem too, but here for initial DOM)
    document.querySelectorAll('.reply-btn').forEach(button => {
        button.addEventListener('click', function () {
            const replyForm = this.closest('.comment')?.querySelector('.reply-form');
            if (replyForm) replyForm.classList.toggle('hidden');
        });
    });

    // Rating stars interaction
    document.querySelectorAll('.rating-stars .star').forEach(star => {
        star.addEventListener('click', function () {
            const rating = parseInt(this.getAttribute('data-rating'), 10);
            const container = this.closest('.rating-stars');
            container.querySelectorAll('.star').forEach(s => {
                if (parseInt(s.getAttribute('data-rating'), 10) <= rating) {
                    s.classList.add('text-yellow-400');
                    s.classList.remove('text-gray-300');
                } else {
                    s.classList.add('text-gray-300');
                    s.classList.remove('text-yellow-400');
                }
            });
            const ratingInput = container.querySelector('input[name="rating"]');
            if (ratingInput) ratingInput.value = rating;
        });
    });

    // Carousel functionality
    const track = document.querySelector('.reviews-carousel-track');
    const dots = document.querySelectorAll('.carousel-dot');
    if (track && dots.length) {
        track.addEventListener('scroll', function () {
            const scrollPos = track.scrollLeft;
            const cardWidth = track.querySelector('.review-card')?.offsetWidth || 1;
            const activeIndex = Math.round(scrollPos / cardWidth);
            dots.forEach((dot, index) => {
                if (index === activeIndex) {
                    dot.classList.add('bg-primary');
                    dot.classList.remove('bg-gray-300');
                } else {
                    dot.classList.remove('bg-primary');
                    dot.classList.add('bg-gray-300');
                }
            });
        });
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                const cardWidth = track.querySelector('.review-card')?.offsetWidth || 1;
                track.scrollTo({ left: cardWidth * index, behavior: 'smooth' });
            });
        });
    }

    // Booking Modal Functionality
    if (typeof setupBookingModal === 'function') setupBookingModal();

    // Real-time Comments and Reviews
    setupCommentSystem();

    // Review form submission (prevent duplicate event listeners)
    const reviewForm = document.getElementById('reviewForm');
    if (reviewForm && !reviewForm.dataset.listenerAdded) {
        reviewForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('../core/ajax/add_review.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Review submitted successfully!', 'success');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        showNotification(data.message || 'Failed to submit review', 'error');
                    }
                })
                .catch(() => showNotification('An error occurred. Please try again.', 'error'));
        });
        reviewForm.dataset.listenerAdded = 'true';
    }

    // Google Maps
    if (typeof initMap === 'function') {
        initMap();
    }

    // Google OAuth and Maps scripts
    if (document.getElementById('googleSignInButton') || document.getElementById('map')) {
        loadGoogleScripts();
    }

    // Auth Modal Functionality
    const authModal = document.getElementById('authModal');
    if (authModal) {
        const loginTab = document.getElementById('loginTab');
        const registerTab = document.getElementById('registerTab');
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');
        const closeAuthModal = document.getElementById('closeAuthModal');
        const studentTypeBtn = document.getElementById('studentTypeBtn');
        const landlordTypeBtn = document.getElementById('landlordTypeBtn');
        const cniField = document.getElementById('cniField');
        const loginBtn = document.getElementById('loginBtn');
        const registerBtn = document.getElementById('registerBtn');
        const mobileLoginBtn = document.getElementById('mobileLoginBtn');
        const mobileRegisterBtn = document.getElementById('mobileRegisterBtn');
        const studentRegisterBtn = document.getElementById('studentRegisterBtn');
        const landlordRegisterBtn = document.getElementById('landlordRegisterBtn');
        const studentCTA = document.getElementById('studentCTA');
        const landlordCTA = document.getElementById('landlordCTA');
        const finalCTA = document.getElementById('finalCTA');

        function openLoginModal() {
            authModal.classList.remove('hidden');
            loginTab?.classList.add('tab-active');
            loginTab?.classList.remove('text-gray-500');
            registerTab?.classList.remove('tab-active');
            registerTab?.classList.add('text-gray-500');
            loginForm?.classList.remove('hidden');
            registerForm?.classList.add('hidden');
            document.body.style.overflow = 'hidden';
        }

        function openRegisterModal(isLandlord = false) {
            authModal.classList.remove('hidden');
            registerTab?.classList.add('tab-active');
            registerTab?.classList.remove('text-gray-500');
            loginTab?.classList.remove('tab-active');
            loginTab?.classList.add('text-gray-500');
            registerForm?.classList.remove('hidden');
            loginForm?.classList.add('hidden');
            document.body.style.overflow = 'hidden';
            if (isLandlord) {
                landlordTypeBtn?.click();
            } else {
                studentTypeBtn?.click();
            }
        }

        function closeModal() {
            authModal.classList.add('hidden');
            document.body.style.overflow = '';
        }

        loginTab?.addEventListener('click', openLoginModal);
        registerTab?.addEventListener('click', () => openRegisterModal());
        studentTypeBtn?.addEventListener('click', function () {
            studentTypeBtn.classList.add('bg-primary', 'text-white');
            studentTypeBtn.classList.remove('text-gray-700');
            landlordTypeBtn?.classList.remove('bg-primary', 'text-white');
            landlordTypeBtn?.classList.add('text-gray-700');
            cniField?.classList.add('hidden');
        });
        landlordTypeBtn?.addEventListener('click', function () {
            landlordTypeBtn.classList.add('bg-primary', 'text-white');
            landlordTypeBtn.classList.remove('text-gray-700');
            studentTypeBtn?.classList.remove('bg-primary', 'text-white');
            studentTypeBtn?.classList.add('text-gray-700');
            cniField?.classList.remove('hidden');
        });

        loginBtn?.addEventListener('click', openLoginModal);
        mobileLoginBtn?.addEventListener('click', openLoginModal);
        registerBtn?.addEventListener('click', () => openRegisterModal());
        mobileRegisterBtn?.addEventListener('click', () => openRegisterModal());
        studentRegisterBtn?.addEventListener('click', () => openRegisterModal());
        landlordRegisterBtn?.addEventListener('click', () => openRegisterModal(true));
        studentCTA?.addEventListener('click', () => openRegisterModal());
        landlordCTA?.addEventListener('click', () => openRegisterModal(true));
        finalCTA?.addEventListener('click', () => openRegisterModal());
        closeAuthModal?.addEventListener('click', closeModal);

        authModal.addEventListener('click', function (e) {
            if (e.target === authModal) closeModal();
        });

        // Prevent form submission for demo
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
            });
        });
    }
});

// Google OAuth
function initGoogleOAuth() {
    google.accounts.id.initialize({
        client_id: "1053163492688-lnmh4fpgpmhndl7g9l5j0qt9n36ue8cf.apps.googleusercontent.com",
        callback: handleCredentialResponse
    });
    google.accounts.id.renderButton(
        document.getElementById("googleSignInButton"),
        { theme: "outline", size: "large" }
    );
    google.accounts.id.prompt();
}

// Load Google APIs
function loadGoogleScripts() {
    const script1 = document.createElement('script');
    script1.src = 'https://accounts.google.com/gsi/client';
    script1.async = true;
    script1.defer = true;
    script1.onload = initGoogleOAuth;
    document.body.appendChild(script1);

    const script2 = document.createElement('script');
    script2.src = `https://maps.googleapis.com/maps/api/js?key=AIzaSyCPMmHq7Cuad8V5zB1tF1ZHz7us-JKpnVo&libraries=places&callback=initMap`;
    script2.async = true;
    script2.defer = true;
    document.body.appendChild(script2);
}

// Real-time Comments and Reviews
function setupCommentSystem() {
    // Handle comment submission
    document.querySelectorAll('.comment-form').forEach(form => {
        if (form.dataset.listenerAdded) return;
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            const roomId = this.getAttribute('data-room-id');
            const parentId = this.querySelector('[name="parent_id"]')?.value;
            const content = this.querySelector('[name="content"]').value.trim();
            if (!content) return;
            fetch('../core/ajax/add_comment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    room_id: roomId,
                    content: content,
                    parent_id: parentId || ''
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.querySelector('[name="content"]').value = '';
                        if (parentId) this.classList.add('hidden');
                        loadComments(roomId);
                    } else {
                        showNotification(data.message || 'Failed to post comment', 'error');
                    }
                })
                .catch(() => showNotification('An error occurred. Please try again.', 'error'));
        });
        form.dataset.listenerAdded = 'true';
    });

    // Handle comment deletion
    document.querySelectorAll('.delete-comment-btn').forEach(button => {
        if (button.dataset.listenerAdded) return;
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const commentId = this.getAttribute('data-comment-id');
            const roomId = this.getAttribute('data-room-id');
            if (confirm('Are you sure you want to delete this comment?')) {
                fetch('../core/ajax/delete_comment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ comment_id: commentId })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Comment deleted successfully', 'success');
                            loadComments(roomId);
                        } else {
                            showNotification(data.message || 'Failed to delete comment', 'error');
                        }
                    })
                    .catch(() => showNotification('An error occurred. Please try again.', 'error'));
            }
        });
        button.dataset.listenerAdded = 'true';
    });

    // Toggle reply form
    document.querySelectorAll('.reply-btn').forEach(button => {
        if (button.dataset.listenerAdded) return;
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const replyForm = this.closest('.comment')?.querySelector('.reply-form');
            if (replyForm) replyForm.classList.toggle('hidden');
        });
        button.dataset.listenerAdded = 'true';
    });
}

// Load comments via AJAX
function loadComments(roomId) {
    fetch(`../core/ajax/get_comments.php?room_id=${roomId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelector('.comments-section').innerHTML = data.html;
                setupCommentSystem();
            }
        })
        .catch(() => { });
}

// Show notification function
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white font-medium flex items-center gap-2 transition-all transform translate-x-48 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
    notification.innerHTML = `
        <i class="${type === 'success' ? 'ri-checkbox-circle-line' : 'ri-close-circle-line'}"></i>
        ${message}
    `;
    document.body.appendChild(notification);
    setTimeout(() => {
        notification.classList.remove('translate-x-48');
        notification.classList.add('translate-x-0');
    }, 10);
    setTimeout(() => {
        notification.classList.remove('translate-x-0');
        notification.classList.add('translate-x-48');
        setTimeout(() => notification.remove(), 300);
    }, 3000);

}


