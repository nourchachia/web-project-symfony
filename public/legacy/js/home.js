import { animate, stagger, splitText, svg } from 'https://cdn.jsdelivr.net/npm/animejs@4/dist/bundles/anime.esm.js';

// ============================================
// COUNTER ANIMATION
// ============================================

function animateValue(element, start, end, duration) {
    let startTimestamp = null;
    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        const easeOutQuad = progress * (2 - progress);
        element.textContent = Math.floor(easeOutQuad * (end - start) + start);
        if (progress < 1) {
            window.requestAnimationFrame(step);
        }
    };
    window.requestAnimationFrame(step);
}

// ============================================
// INTERSECTION OBSERVER FOR SCROLL ANIMATIONS
// ============================================

const observerOptions = {
    threshold: 0.3,
    rootMargin: '0px'
};

const counterObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting && !entry.target.classList.contains('counted')) {
            const target = parseInt(entry.target.getAttribute('data-target'));
            animateValue(entry.target, 0, target, 2000);
            entry.target.classList.add('counted');
        }
    });
}, observerOptions);

const departmentObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting && !entry.target.classList.contains('animated')) {
            entry.target.classList.add('animated');
            entry.target.style.animation = 'departmentTextDrop 1s ease-out forwards';
        }
    });
}, observerOptions);

// ============================================
// INITIALIZE OBSERVERS
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    const statNumbers = document.querySelectorAll('.stat-number[data-target]');
    statNumbers.forEach(stat => {
        counterObserver.observe(stat);
    });

    const departmentTexts = document.querySelectorAll('.department-bg-text');
    departmentTexts.forEach(text => {
        departmentObserver.observe(text);
    });

    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    const videoPolaroids = document.querySelectorAll('.video-polaroid');
    videoPolaroids.forEach(polaroid => {
        const video = polaroid.querySelector('.polaroid-video');
        if (video) {
            const startTime = parseFloat(video.getAttribute('data-start-time')) || 0;
            video.addEventListener('loadedmetadata', () => {
                video.currentTime = startTime;
            });
            polaroid.addEventListener('mouseenter', () => {
                video.play();
            });
            polaroid.addEventListener('mouseleave', () => {
                video.pause();
                video.currentTime = startTime;
            });
            video.addEventListener('ended', () => {
                video.pause();
                video.currentTime = startTime;
            });
        }
    });
});

// ============================================
// UPCOMING SHOW FLOATING EFFECT
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    const section = document.querySelector('.upcoming-shows');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                section.classList.add('visible');
            } else {
                section.classList.remove('visible');
            }
        });
    }, { threshold: 0.2 });

    observer.observe(section);
});

// ============================================
// UPCOMING SHOW SWITCH EFFECT
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    const section = document.querySelector('.upcoming-shows');

    const imageGroups = [
        ['/legacy/assets/0.jpg',  '/legacy/assets/10.jpg'],
        ['/legacy/assets/1.jpg',  '/legacy/assets/11.jpg'],
        ['/legacy/assets/2.jpg',  '/legacy/assets/12.jpg'],
        ['/legacy/assets/3.jpg',  '/legacy/assets/13.jpg'],
        ['/legacy/assets/4.jpg',  '/legacy/assets/14.jpg'],
        ['/legacy/assets/5.jpg',  '/legacy/assets/15.jpg'],
        ['/legacy/assets/6.jpg',  '/legacy/assets/16.jpg'],
        ['/legacy/assets/7.jpg',  '/legacy/assets/17.jpg'],
        ['/legacy/assets/8.jpg',  '/legacy/assets/18.jpg'],
        ['/legacy/assets/9.jpg',  '/legacy/assets/19.jpg'],
    ];

    const boxes = document.querySelectorAll(
        '.upcoming-shows .box1, .upcoming-shows .box2, .upcoming-shows .box3, .upcoming-shows .box4, .upcoming-shows .box5, .upcoming-shows .box6, .upcoming-shows .box7, .upcoming-shows .box8, .upcoming-shows .box9, .upcoming-shows .box10'
    );

    imageGroups.forEach(group => {
        group.forEach(src => {
            const img = new Image();
            img.src = src;
        });
    });

    let hasAnimated = false;
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !hasAnimated) {
                section.classList.add('animated');
                hasAnimated = true;
                observer.disconnect();
            }
        });
    }, { threshold: 0.2 });

    observer.observe(section);

    const currentIndexes = Array(boxes.length).fill(0);

    setInterval(() => {
        boxes.forEach((box, i) => {
            const img = box.querySelector('img');
            if (!img) return;
            if (img.dataset.transitioning === 'true') return;
            img.dataset.transitioning = 'true';

            img.style.opacity = '0';

            img.addEventListener('transitionend', function onFadeOut(e) {
                if (e.propertyName !== 'opacity') return;
                img.removeEventListener('transitionend', onFadeOut);

                currentIndexes[i] = (currentIndexes[i] + 1) % imageGroups[i].length;
                const nextSrc = imageGroups[i][currentIndexes[i]];

                const preloaded = new Image();
                preloaded.onload = () => {
                    img.src = nextSrc;
                    void img.offsetWidth;
                    img.style.opacity = '1';

                    img.addEventListener('transitionend', function onFadeIn(e) {
                        if (e.propertyName !== 'opacity') return;
                        img.removeEventListener('transitionend', onFadeIn);
                        img.dataset.transitioning = 'false';
                    });
                };
                preloaded.src = nextSrc;
            });
        });
    }, 3500);
});

// ============================================
// PREVIOUS SHOWS — ANIMEJS
// ============================================

const titles = document.querySelectorAll('.section-title');
const allChars = [];
titles.forEach(title => {
    const { chars } = splitText(title, { words: false, chars: true });
    allChars.push(...chars);
});

animate(allChars, {
    y: [
        { to: '-2.75rem', ease: 'outExpo', duration: 600 },
        { to: 0, ease: 'outBounce', duration: 800, delay: 100 }
    ],
    rotate: {
        from: '-1turn',
        delay: 0
    },
    delay: stagger(50),
    ease: 'inOutCirc',
    loopDelay: 600,
    loop: true
});

animate(svg.createDrawable('#arrow-path'), {
    draw: '0 1',
    ease: 'linear',
    duration: 5000,
    loop: true,
});