// main.js - Complete JavaScript for Mercy Pastries

// Initialize AOS (Animate on Scroll)
AOS.init({
    duration: 1000,
    once: true,
    offset: 100,
    easing: 'ease-in-out'
});

// Loading animation
window.addEventListener('load', function() {
    setTimeout(function() {
        const loader = document.querySelector('.loading');
        if (loader) {
            loader.classList.add('hide');
        }
    }, 500);
});

// ==================== CART FUNCTIONALITY ====================
let cart = JSON.parse(localStorage.getItem('cart')) || {};

function updateCartCount() {
    const count = Object.values(cart).reduce((total, quantity) => total + quantity, 0);
    const cartCountElements = document.querySelectorAll('#cart-count');
    cartCountElements.forEach(el => {
        el.textContent = count;
        if (count > 0) {
            el.style.display = 'block';
        } else {
            el.style.display = 'none';
        }
    });
}

function addToCart(productId, quantity = 1) {
    quantity = parseInt(quantity);
    
    if (cart[productId]) {
        cart[productId] += quantity;
    } else {
        cart[productId] = quantity;
    }
    
    // Save to localStorage
    localStorage.setItem('cart', JSON.stringify(cart));
    
    // Also save to cookie for server-side
    document.cookie = 'cart=' + JSON.stringify(cart) + ';path=/;max-age=' + (30 * 24 * 60 * 60);
    
    updateCartCount();
    showNotification('Product added to cart!', 'success');
    
    // Animate cart icon
    const cartIcon = document.querySelector('.fa-shopping-cart');
    if (cartIcon) {
        cartIcon.classList.add('fa-bounce');
        setTimeout(() => {
            cartIcon.classList.remove('fa-bounce');
        }, 1000);
    }
}

function removeFromCart(productId) {
    delete cart[productId];
    localStorage.setItem('cart', JSON.stringify(cart));
    document.cookie = 'cart=' + JSON.stringify(cart) + ';path=/;max-age=' + (30 * 24 * 60 * 60);
    updateCartCount();
    showNotification('Product removed from cart', 'info');
    
    // Reload if on cart page
    if (window.location.pathname.includes('cart.php')) {
        location.reload();
    }
}

function updateCartItem(productId, quantity) {
    quantity = parseInt(quantity);
    
    if (quantity <= 0) {
        removeFromCart(productId);
    } else {
        cart[productId] = quantity;
        localStorage.setItem('cart', JSON.stringify(cart));
        document.cookie = 'cart=' + JSON.stringify(cart) + ';path=/;max-age=' + (30 * 24 * 60 * 60);
    }
    
    updateCartCount();
    
    // Reload if on cart page
    if (window.location.pathname.includes('cart.php')) {
        location.reload();
    }
}

function clearCart() {
    cart = {};
    localStorage.removeItem('cart');
    document.cookie = 'cart=;path=/;max-age=0';
    updateCartCount();
    showNotification('Cart cleared', 'info');
    
    // Reload if on cart page
    if (window.location.pathname.includes('cart.php')) {
        location.reload();
    }
}

// ==================== WISHLIST FUNCTIONALITY ====================
let wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];

function updateWishlistCount() {
    const count = wishlist.length;
    const wishlistCountElements = document.querySelectorAll('#wishlist-count');
    wishlistCountElements.forEach(el => {
        el.textContent = count;
        if (count > 0) {
            el.style.display = 'block';
        } else {
            el.style.display = 'none';
        }
    });
}

function addToWishlist(productId) {
    if (!wishlist.includes(productId)) {
        wishlist.push(productId);
        localStorage.setItem('wishlist', JSON.stringify(wishlist));
        updateWishlistCount();
        showNotification('Added to wishlist!', 'success');
        
        // Update heart icon
        const heartIcon = document.querySelector(`.wishlist-btn[data-id="${productId}"] i`);
        if (heartIcon) {
            heartIcon.classList.remove('far');
            heartIcon.classList.add('fas');
        }
    } else {
        showNotification('Already in wishlist', 'info');
    }
}

function removeFromWishlist(productId) {
    wishlist = wishlist.filter(id => id != productId);
    localStorage.setItem('wishlist', JSON.stringify(wishlist));
    updateWishlistCount();
    showNotification('Removed from wishlist', 'info');
    
    // Update heart icon
    const heartIcon = document.querySelector(`.wishlist-btn[data-id="${productId}"] i`);
    if (heartIcon) {
        heartIcon.classList.remove('fas');
        heartIcon.classList.add('far');
    }
    
    // Reload if on wishlist page
    if (window.location.pathname.includes('wishlist.php')) {
        location.reload();
    }
}

// ==================== NOTIFICATION SYSTEM ====================
function showNotification(message, type = 'info') {
    // Create notification container if it doesn't exist
    let container = document.querySelector('.notification-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'notification-container';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        `;
        document.body.appendChild(container);
    }
    
    // Create notification
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show`;
    notification.style.cssText = `
        margin-bottom: 10px;
        min-width: 300px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        animation: slideIn 0.3s ease;
    `;
    
    // Icon based on type
    let icon = '';
    switch(type) {
        case 'success':
            icon = '<i class="fas fa-check-circle me-2"></i>';
            break;
        case 'danger':
            icon = '<i class="fas fa-exclamation-circle me-2"></i>';
            break;
        case 'warning':
            icon = '<i class="fas fa-exclamation-triangle me-2"></i>';
            break;
        default:
            icon = '<i class="fas fa-info-circle me-2"></i>';
    }
    
    notification.innerHTML = `
        ${icon}${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    container.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// ==================== QUANTITY SELECTOR ====================
function updateQuantity(input, change) {
    let value = parseInt(input.value) || 1;
    const min = parseInt(input.min) || 1;
    const max = parseInt(input.max) || 999;
    
    value += change;
    
    if (value < min) value = min;
    if (value > max) value = max;
    
    input.value = value;
    
    // Trigger change event
    const event = new Event('change', { bubbles: true });
    input.dispatchEvent(event);
}

// ==================== PRODUCT IMAGE GALLERY ====================
function changeImage(element, imageSrc) {
    const mainImage = document.getElementById('main-product-image');
    if (mainImage) {
        mainImage.src = imageSrc;
        
        // Remove active class from all thumbnails
        document.querySelectorAll('.thumbnail-img').forEach(thumb => {
            thumb.classList.remove('active');
        });
        
        // Add active class to clicked thumbnail
        element.classList.add('active');
    }
}

// ==================== SEARCH FUNCTIONALITY ====================
function performSearch() {
    const searchInput = document.getElementById('search-input');
    if (searchInput && searchInput.value.trim()) {
        window.location.href = 'products.php?search=' + encodeURIComponent(searchInput.value.trim());
    }
}

// ==================== FILTER FUNCTIONALITY ====================
function filterProducts() {
    const form = document.getElementById('filter-form');
    if (form) {
        form.submit();
    }
}

function sortProducts(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('sort', value);
    window.location.href = url.toString();
}

// ==================== PRICE RANGE SLIDER ====================
function initPriceRange() {
    const priceRange = document.getElementById('priceRange');
    const minPrice = document.getElementById('minPrice');
    const maxPrice = document.getElementById('maxPrice');
    
    if (priceRange && minPrice && maxPrice) {
        priceRange.addEventListener('input', function() {
            const value = this.value.split(',');
            minPrice.value = value[0];
            maxPrice.value = value[1];
        });
    }
}

// ==================== FORM VALIDATION ====================
(function() {
    'use strict';
    
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
})();

// ==================== SMOOTH SCROLL ====================
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        
        const targetId = this.getAttribute('href');
        if (targetId === '#') return;
        
        const target = document.querySelector(targetId);
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// ==================== NAVBAR SCROLL EFFECT ====================
window.addEventListener('scroll', function() {
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        if (window.scrollY > 50) {
            navbar.style.padding = '0.5rem 0';
            navbar.style.boxShadow = '0 2px 20px rgba(0,0,0,0.2)';
        } else {
            navbar.style.padding = '1rem 0';
            navbar.style.boxShadow = '0 2px 20px rgba(0,0,0,0.1)';
        }
    }
});

// ==================== BACK TO TOP BUTTON ====================
function createBackToTop() {
    const btn = document.createElement('button');
    btn.id = 'back-to-top';
    btn.innerHTML = '<i class="fas fa-arrow-up"></i>';
    btn.style.cssText = `
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6f42c1, #0d6efd);
        color: white;
        border: none;
        cursor: pointer;
        display: none;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        transition: all 0.3s ease;
        z-index: 999;
    `;
    
    btn.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-5px)';
        this.style.boxShadow = '0 10px 25px rgba(111,66,193,0.4)';
    });
    
    btn.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
        this.style.boxShadow = '0 5px 15px rgba(0,0,0,0.2)';
    });
    
    btn.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    document.body.appendChild(btn);
    
    window.addEventListener('scroll', function() {
        if (window.scrollY > 500) {
            btn.style.display = 'flex';
        } else {
            btn.style.display = 'none';
        }
    });
}

// ==================== PRODUCT QUICK VIEW ====================
function quickView(productId) {
    // Fetch product details via AJAX
    fetch('ajax/quick-view.php?id=' + productId)
        .then(response => response.text())
        .then(html => {
            const modal = document.getElementById('quickViewModal');
            if (!modal) {
                // Create modal if it doesn't exist
                const modalHTML = `
                    <div class="modal fade" id="quickViewModal" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Quick View</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body" id="quickViewContent">
                                    ${html}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                document.body.insertAdjacentHTML('beforeend', modalHTML);
            } else {
                document.getElementById('quickViewContent').innerHTML = html;
            }
            
            new bootstrap.Modal(document.getElementById('quickViewModal')).show();
        });
}

// ==================== NEWSLETTER SUBSCRIPTION ====================
function subscribeNewsletter(email) {
    fetch('ajax/newsletter.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'email=' + encodeURIComponent(email)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Successfully subscribed to newsletter!', 'success');
        } else {
            showNotification(data.message || 'Subscription failed', 'danger');
        }
    });
}

// ==================== INITIALIZATION ====================
document.addEventListener('DOMContentLoaded', function() {
    // Update cart count
    updateCartCount();
    
    // Update wishlist count
    updateWishlistCount();
    
    // Initialize price range slider
    initPriceRange();
    
    // Create back to top button
    createBackToTop();
    
    // Add to cart buttons
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.dataset.id;
            const quantity = this.dataset.quantity || 1;
            addToCart(productId, quantity);
        });
    });
    
    // Wishlist buttons
    document.querySelectorAll('.add-to-wishlist').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.dataset.id;
            
            if (this.classList.contains('in-wishlist')) {
                removeFromWishlist(productId);
                this.classList.remove('in-wishlist');
            } else {
                addToWishlist(productId);
                this.classList.add('in-wishlist');
            }
        });
    });
    
    // Quick view buttons
    document.querySelectorAll('.quick-view').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.dataset.id;
            quickView(productId);
        });
    });
    
    // Newsletter form
    const newsletterForm = document.querySelector('.newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('input[type="email"]').value;
            if (email) {
                subscribeNewsletter(email);
                this.querySelector('input[type="email"]').value = '';
            }
        });
    }
    
    // Search form
    const searchForm = document.getElementById('search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            performSearch();
        });
    }
});

// ==================== AJAX FUNCTIONS ====================
function fetchAPI(url, options = {}) {
    return fetch(url, {
        ...options,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            ...options.headers
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    });
}

// ==================== COOKIE HELPER ====================
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
}

function setCookie(name, value, days = 30) {
    const date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    document.cookie = name + '=' + value + ';path=/;expires=' + date.toUTCString();
}

function deleteCookie(name) {
    document.cookie = name + '=;path=/;expires=Thu, 01 Jan 1970 00:00:01 GMT';
}

// ==================== LAZY LOADING IMAGES ====================
if ('IntersectionObserver' in window) {
    const lazyImages = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.add('loaded');
                imageObserver.unobserve(img);
            }
        });
    });
    
    lazyImages.forEach(img => imageObserver.observe(img));
}

// ==================== ADD CSS ANIMATIONS ====================
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes bounce {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.2); }
    }
    
    .fa-bounce {
        animation: bounce 0.5s ease;
    }
    
    .loading {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: white;
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        transition: opacity 0.5s ease;
    }
    
    .loading.hide {
        opacity: 0;
        pointer-events: none;
    }
    
    .loading-spinner {
        width: 50px;
        height: 50px;
        border: 5px solid #f3f3f3;
        border-top: 5px solid #6f42c1;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
`;

document.head.appendChild(style);