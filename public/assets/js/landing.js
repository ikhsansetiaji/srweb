// ============================================
// Landing Page JavaScript
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    // Initialize typewriter effect
    console.log('Typewriter Started');
    initializeTypewriter();

    // Animate statistics on scroll
    animateStatisticsOnScroll();

    // Initialize tooltips if bootstrap tooltips are used
    initializeToolTips();

    // Smooth navigation
    setupSmoothNavigation();

    // CTA button interactions
    setupCTAButtons();
});

// ============================================
// Typewriter Effect
// ============================================

function initializeTypewriter() {
    const typewriterTexts = [

        "🎶 Galau? Sini, cerita lewat lagu pilihanmu...",

        "☕ Nongkrong sambil denger lagu favorit...",

        "✨ Kamu yang pilih lagunya, kamu yang buat suasananya..."

    ];

    const typewriterElement = document.querySelector('.typewriter');
    const cursorElement = document.querySelector('.cursor');

    if (!typewriterElement) return;

    let currentTextIndex = 0;
    let currentCharIndex = 0;
    let isDeleting = false;
    const typingSpeed = 80; // ms per character
    const deletingSpeed = 40; // ms per character
    const delayBetweenTexts = 3000; // ms delay before starting to delete

    function typeCharacter() {
        const currentText = typewriterTexts[currentTextIndex];

        if (!isDeleting && currentCharIndex < currentText.length) {
            // Typing forward
            typewriterElement.textContent += currentText[currentCharIndex];
            currentCharIndex++;
            setTimeout(typeCharacter, typingSpeed);
        } else if (!isDeleting && currentCharIndex === currentText.length) {
            // Finished typing, wait before deleting
            isDeleting = true;
            setTimeout(typeCharacter, delayBetweenTexts);
        } else if (isDeleting && currentCharIndex > 0) {
            // Deleting backward
            typewriterElement.textContent = currentText.substring(0, currentCharIndex - 1);
            currentCharIndex--;
            setTimeout(typeCharacter, deletingSpeed);
        } else if (isDeleting && currentCharIndex === 0) {
            // Finished deleting, move to next text
            isDeleting = false;
            currentTextIndex = (currentTextIndex + 1) % typewriterTexts.length;
            setTimeout(typeCharacter, 500);
        }
    }

    // Start typing
    typeCharacter();
}

// ============================================
// Animate Statistics Counter
// ============================================

function animateStatisticsOnScroll() {
    const statNumbers = document.querySelectorAll('.stat-number');
    const statisticsSection = document.querySelector('.statistics');

    if (!statisticsSection) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                statNumbers.forEach(stat => {
                    animateCounter(stat);
                });
                observer.unobserve(entry.target);
            }
        });
    });

    observer.observe(statisticsSection);
}

function animateCounter(element) {
    const target = parseInt(element.getAttribute('data-target'));
    const duration = 2000; // 2 seconds
    const increment = target / (duration / 16); // 60fps
    let current = 0;

    const interval = setInterval(() => {
        current += increment;

        if (current >= target) {
            element.textContent = target.toLocaleString('id-ID');
            clearInterval(interval);
        } else {
            element.textContent = Math.floor(current).toLocaleString('id-ID');
        }
    }, 16);
}

// ============================================
// Initialize Bootstrap Tooltips
// ============================================

function initializeToolTips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// ============================================
// Smooth Navigation
// ============================================

function setupSmoothNavigation() {
    const links = document.querySelectorAll('a[href*="#"]');

    links.forEach(link => {
        link.addEventListener('click', function (e) {
            const href = this.getAttribute('href');

            // Only trigger smooth scroll for same-page links
            if (href.startsWith('#') && href !== '#') {
                e.preventDefault();

                const targetId = href.substring(1);
                const targetElement = document.getElementById(targetId);

                if (targetElement) {
                    targetElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });

                    // Update navbar state
                    updateNavbarState(href);
                }
            }
        });
    });
}

// ============================================
// Update Navbar State
// ============================================

function updateNavbarState(href) {
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');

    navLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === href) {
            link.classList.add('active');
        }
    });
}

// ============================================
// CTA Button Interactions
// ============================================

function setupCTAButtons() {
    const ctaButtons = document.querySelectorAll('[href="#"]');

    ctaButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            // Prevent default navigation for placeholder buttons
            if (this.getAttribute('href') === '#') {
                e.preventDefault();
                console.log('CTA clicked - would navigate to request page');
            }
        });
    });
}

// ============================================
// Scroll Animation for Elements
// ============================================

window.addEventListener('load', function() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -100px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animated');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Observe all cards and step items
    document.querySelectorAll('.step-card, .feature-card, .accordion-item').forEach(element => {
        observer.observe(element);
    });
});

// ============================================
// Mobile Menu Close on Link Click
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    const navToggle = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');

    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            // Close mobile menu after clicking a link
            if (navToggle.offsetParent !== null) { // Check if mobile view
                navToggle.click();
            }
        });
    });
});

// ============================================
// Navbar Scroll Effect
// ============================================

window.addEventListener('scroll', function() {
    const navbar = document.querySelector('.navbar');

    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});

console.log('Landing page JavaScript loaded successfully!');

