// ============================================
// COUNTER ANIMATION
// ============================================

function animateValue(element, start, end, duration) {
    let startTimestamp = null;
    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);

        // Easing function for smooth animation
        const easeOutQuad = progress * (2 - progress);

        element.textContent = Math.floor(easeOutQuad * (end - start) + start);
        if (progress < 1) {
            window.requestAnimationFrame(step);
        }
    };
    window.requestAnimationFrame(step);
}

function runCounter(stat) {
    if (stat.classList.contains('counted')) {
        return;
    }

    const target = parseInt(stat.getAttribute('data-target'), 10);

    if (Number.isNaN(target)) {
        return;
    }

    stat.classList.add('counted');
    animateValue(stat, 0, target, 2000);
}

function showUpcomingShows() {
    document.querySelectorAll('.title-upcoming-shows, .show').forEach(show => {
        show.classList.add('visible');
    });
}

// ============================================
// INTERSECTION OBSERVER FOR SCROLL ANIMATIONS
// ============================================

const observerOptions = {
    threshold: 0.3,
    rootMargin: '0px'
};

// Counter Animation Observer
const counterObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            runCounter(entry.target);
        }
    });
}, {
    threshold: 0.1,
    rootMargin: '0px 0px -10% 0px'
});

// Department Text Animation Observer
const departmentObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting && !entry.target.classList.contains('animated')) {
            entry.target.classList.add('animated');
            entry.target.style.animation = 'departmentTextDrop 1s ease-out forwards';
        }
    });
}, observerOptions);

let homeNavigationBound = false;

// ============================================
// INITIALIZE OBSERVERS
// ============================================

function initHomePage() {
    if (!document.querySelector('.page-home-legacy')) {
        return;
    }

    // Observe all stat numbers
    const statNumbers = document.querySelectorAll('.stat-number[data-target]');
    statNumbers.forEach(stat => {
        counterObserver.observe(stat);
    });

    const checkCountersInView = () => {
        statNumbers.forEach(stat => {
            const rect = stat.getBoundingClientRect();

            if (rect.top < window.innerHeight * 0.9 && rect.bottom > 0) {
                runCounter(stat);
            }
        });
    };

    window.addEventListener('scroll', checkCountersInView, { passive: true });
    window.addEventListener('resize', checkCountersInView);
    checkCountersInView();

    // Observe all department background texts
    const departmentTexts = document.querySelectorAll('.department-bg-text');
    departmentTexts.forEach(text => {
        departmentObserver.observe(text);
    });

    // Smooth scroll for same-page navigation links.
    if (!homeNavigationBound) {
        document.addEventListener('click', (e) => {
            const anchor = e.target.closest('a[href*="#"]');

            if (!anchor) {
                return;
            }

            const url = new URL(anchor.getAttribute('href'), window.location.href);

            if (url.pathname !== window.location.pathname || !url.hash) {
                return;
            }

            const target = document.querySelector(url.hash);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                history.pushState(null, '', url.hash);

                if (url.hash === '#shows') {
                    showUpcomingShows();
                }
            }
        });

        homeNavigationBound = true;
    }

    // Video Polaroid Hover Effect
    const videoPolaroids = document.querySelectorAll('.video-polaroid');
    videoPolaroids.forEach(polaroid => {
        const video = polaroid.querySelector('.polaroid-video');

        if (video) {
            // Get custom start time or default to 0
            const startTime = parseFloat(video.getAttribute('data-start-time')) || 0;

            // Set initial start time when video loads
            video.addEventListener('loadedmetadata', () => {
                video.currentTime = startTime;
            });

            polaroid.addEventListener('mouseenter', () => {
                video.play();
            });

            polaroid.addEventListener('mouseleave', () => {
                video.pause();
                video.currentTime = startTime; // Reset to chosen frame
            });

            // Reset to custom start time when video ends
            video.addEventListener('ended', () => {
                video.pause();
                video.currentTime = startTime;
            });
        }
    });

    if (window.location.hash) {
        const hashTarget = document.querySelector(window.location.hash);

        if (hashTarget) {
            window.requestAnimationFrame(() => {
                hashTarget.scrollIntoView({
                    behavior: 'auto',
                    block: 'start'
                });
            });
        }

        if (window.location.hash === '#shows') {
            showUpcomingShows();
        }
    }
}

function initShowsAnimation() {
    if (!document.querySelector('.page-home-legacy')) {
        return;
    }

    const shows = document.querySelectorAll('.title-upcoming-shows, .show');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible'); // fade in + float
            }
        });
    }, { threshold: 0.3 }); // trigger when 30% visible

    shows.forEach(show => observer.observe(show));
}

function initLegacyHome() {
    initHomePage();
    initShowsAnimation();
}

document.addEventListener('DOMContentLoaded', initLegacyHome);
document.addEventListener('turbo:load', initLegacyHome);
