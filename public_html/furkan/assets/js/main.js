"use strict";

var body = document.body;
var header = document.querySelector("[data-header]");
var menuToggle = document.querySelector(".menu-toggle");
var navLinks = document.querySelectorAll(".nav-link");
var isHomePage = window.location.pathname.replace(/\/$/, "").endsWith("index.html") || window.location.pathname === "/" || window.location.pathname === "/furkan" || window.location.pathname === "/furkan/";

/* header scroll state */
function syncHeader() {
    if (!header) return;
    header.classList.toggle("is-scrolled", window.scrollY > 30);
}

window.addEventListener("scroll", syncHeader, { passive: true });
syncHeader();

/* mobile menu */
function closeMenu() {
    body.classList.remove("menu-open");
    if (menuToggle) menuToggle.setAttribute("aria-expanded", "false");
}

if (menuToggle) {
    menuToggle.addEventListener("click", function () {
        var isOpen = body.classList.toggle("menu-open");
        menuToggle.setAttribute("aria-expanded", String(isOpen));
    });
}

/* nav link click — close mobile menu on tap */
navLinks.forEach(function (link) {
    link.addEventListener("click", closeMenu);
});

/* scroll spy — only on home page */
if (isHomePage) {
    var sections = document.querySelectorAll("section[id]");

    function setActiveNav() {
        var current = "";
        var scrollPos = window.scrollY + window.innerHeight / 3;

        sections.forEach(function (section) {
            var top = section.offsetTop;
            var height = section.offsetHeight;
            if (scrollPos >= top && scrollPos < top + height) {
                current = section.getAttribute("id");
            }
        });

        navLinks.forEach(function (link) {
            link.classList.remove("is-active");
            var href = link.getAttribute("href");
            if (href === "#" + current || (current === "home" && href === "index.html")) {
                link.classList.add("is-active");
            }
        });
    }

    window.addEventListener("scroll", setActiveNav, { passive: true });
}

/* ---- ripple effect on hero image ---- */
var turb = document.getElementById("ripple-turb");
var disp = document.getElementById("ripple-disp");

if (turb && disp) {
    var rippleScale = 0;
    var rippleTargetScale = 0;
    var rippleTime = 0;
    var rippleFadeTimer = null;

    var heroWrap = document.querySelector(".hero-image-wrap");

    if (heroWrap) {
        heroWrap.addEventListener("mouseenter", function () {
            rippleTargetScale = 22;
            resetRippleTimer();
        });

        heroWrap.addEventListener("mousemove", function (e) {
            if (rippleTargetScale <= 0) return;
            var rect = heroWrap.getBoundingClientRect();
            var x = (e.clientX - rect.left) / rect.width;
            var y = (e.clientY - rect.top) / rect.height;
            disp.setAttribute("xChannelSelector", x < 0.5 ? "R" : "G");
            disp.setAttribute("yChannelSelector", y < 0.5 ? "G" : "B");
            resetRippleTimer();
        });

        heroWrap.addEventListener("mouseleave", function () {
            rippleTargetScale = 0;
            clearTimeout(rippleFadeTimer);
        });
    }

    function resetRippleTimer() {
        clearTimeout(rippleFadeTimer);
        rippleTargetScale = 22;
        rippleFadeTimer = setTimeout(function () {
            rippleTargetScale = 0;
        }, 500);
    }

    function rippleLoop(ts) {
        rippleTime += 0.016;

        var lerp = 0.08;
        rippleScale += (rippleTargetScale - rippleScale) * lerp;

        if (Math.abs(rippleScale - rippleTargetScale) < 0.1) {
            rippleScale = rippleTargetScale;
        }

        if (rippleScale < 0.05 && rippleTargetScale === 0) {
            rippleScale = 0;
        }

        var breath = 1 + Math.sin(rippleTime * 1.8) * 0.3;
        var bf = (0.012 + (rippleScale / 22) * 0.006) * breath;

        turb.setAttribute("baseFrequency", bf.toFixed(4) + " " + (bf * 0.7).toFixed(4));
        disp.setAttribute("scale", String(Math.round(rippleScale)));

        requestAnimationFrame(rippleLoop);
    }

    requestAnimationFrame(rippleLoop);
}

/* ---- dynamic gallery builder ---- */
function buildGallery(containerId, imagesArray) {
    var container = document.getElementById(containerId);
    if (!container || !imagesArray || !imagesArray.length) return;

    var gallerySrc = imagesArray.slice();
    var items = [];
    var loaded = 0;
    var PORTRAIT  = { max: 0.85 };
    var LANDSCAPE = { min: 1.2 };

    function classify(ratio) {
        if (ratio < PORTRAIT.max)  return "portrait";
        if (ratio > LANDSCAPE.min) return "landscape";
        return "square";
    }

    function build() {
        var portrait = [], square = [], landscape = [];
        items.forEach(function (i) {
            if (i.type === "portrait")  portrait.push(i);
            else if (i.type === "landscape") landscape.push(i);
            else square.push(i);
        });

        var ordered = [];
        var pi = 0, si = 0, li = 0;

        while (li < landscape.length || pi < portrait.length || si < square.length) {
            if (li < landscape.length)      { ordered.push(landscape[li]); li++; continue; }
            if (si < square.length)         { ordered.push(square[si]);    si++; continue; }
            if (pi < portrait.length)       { ordered.push(portrait[pi]);  pi++; continue; }
            break;
        }

        render(ordered);
        initReveal();
    }

    function render(list) {
        var html = "";
        list.forEach(function (item) {
            html +=
                '<a href="#" class="gallery-link reveal" data-src="' + item.src + '" data-title="' + (item.title || "") + '">' +
                '<img src="' + item.src + '" alt="' + (item.alt || "") + '" loading="lazy" decoding="async">' +
                '<div class="gallery-overlay"><span class="gallery-view">View</span></div>' +
                '</a>';
        });
        container.innerHTML = html;
    }

    function initReveal() {
        var els = container.querySelectorAll(".reveal");
        if (!els.length) return;
        var obs = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (e.isIntersecting) { e.target.classList.add("is-visible"); obs.unobserve(e.target); }
            });
        }, { rootMargin: "0px 0px -48px 0px", threshold: 0.1 });
        els.forEach(function (el) { obs.observe(el); });
    }

    gallerySrc.forEach(function (entry) {
        var src = typeof entry === "string" ? entry : entry.src;
        var img = new Image();
        img.onload = function () {
            var ratio = img.naturalWidth / img.naturalHeight;
            items.push({ src: src, ratio: ratio, type: classify(ratio), alt: entry.alt || "", title: entry.title || "" });
            loaded++;
            if (loaded === gallerySrc.length) build();
        };
        img.onerror = function () {
            loaded++;
            if (loaded === gallerySrc.length) build();
        };
        img.src = src;
    });
}

(function () {
    if (window.PHOTOGRAPHY_IMAGES) {
        buildGallery("photography-gallery", window.PHOTOGRAPHY_IMAGES);
    }
    if (window.VISUAL_ART_IMAGES) {
        buildGallery("visual-art-gallery", window.VISUAL_ART_IMAGES);
    }
})();

/* ---- lightbox ---- */
(function () {
    var lb = document.getElementById("lightbox");
    if (!lb) return;

    var lbImg = lb.querySelector(".lightbox-img");
    var lbClose = lb.querySelector(".lightbox-close");
    function open(src) {
        if (!src) return;
        lbImg.src = src;
        lb.classList.add("is-open");
        lb.setAttribute("aria-hidden", "false");
        body.style.overflow = "hidden";
    }

    function close() {
        lb.classList.remove("is-open");
        lb.setAttribute("aria-hidden", "true");
        body.style.overflow = "";
        setTimeout(function () { lbImg.src = ""; }, 300);
    }

    document.addEventListener("click", function (e) {
        var link = e.target.closest(".gallery-link");
        if (!link) return;
        e.preventDefault();
        open(link.getAttribute("data-src"));
    });

    lbClose.addEventListener("click", close);

    lb.addEventListener("click", function (e) {
        if (e.target === lb) close();
    });

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape" && lb.classList.contains("is-open")) close();
    });
})();

var revealElements = document.querySelectorAll(".reveal");

if (revealElements.length) {
    var revealObserver = new IntersectionObserver(
        function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add("is-visible");
                    revealObserver.unobserve(entry.target);
                }
            });
        },
        {
            rootMargin: "0px 0px -48px 0px",
            threshold: 0.1
        }
    );

    revealElements.forEach(function (el) { revealObserver.observe(el); });
}

/* ---- hero title runner (video-based shadow through text via SVG clip-path) ---- */
(function () {
    var h1 = document.querySelector(".hero-title");
    var video = h1 ? h1.querySelector(".runner-video") : null;
    if (!h1 || !video) return;

    // Browser'larin autoplay engellerine karsi guvence: Yuklendiginde oynamiyorsa tetikle
    document.addEventListener("DOMContentLoaded", function() {
        if (video.paused) {
            video.play().catch(function() {});
        }
    });
})();
