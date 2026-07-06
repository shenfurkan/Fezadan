(function () {
        var storageKey = 'fezadan-reader-size';
        var allowedSizes = ['small', 'medium', 'large'];
        var buttons = document.querySelectorAll('[data-reader-size]');
        var printButton = document.getElementById('print-article-btn');
        var previousArticleUrl = (window.FezadanArticleData ? window.FezadanArticleData.previousArticleUrl : null);
        var nextArticleUrl = (window.FezadanArticleData ? window.FezadanArticleData.nextArticleUrl : null);

        function isEditableTarget(target) {
            if (!target) return false;
            var tagName = (target.tagName || '').toLowerCase();
            return target.isContentEditable || tagName === 'input' || tagName === 'textarea' || tagName === 'select';
        }

        function setReaderSize(size) {
            if (allowedSizes.indexOf(size) === -1) size = 'medium';
            document.documentElement.setAttribute('data-reader-size', size);
            try { localStorage.setItem(storageKey, size); } catch (error) {}

            buttons.forEach(function (button) {
                var isActive = button.getAttribute('data-reader-size') === size;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', String(isActive));
            });
        }

        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                setReaderSize(button.getAttribute('data-reader-size'));
            });
        });

        if (printButton) {
            printButton.addEventListener('click', function () {
                window.print();
            });
        }

        try {
            setReaderSize(localStorage.getItem(storageKey) || 'medium');
        } catch (error) {
            setReaderSize('medium');
        }

        document.addEventListener('keydown', function (event) {
            if (event.defaultPrevented || event.ctrlKey || event.metaKey || event.altKey || event.shiftKey || isEditableTarget(event.target)) {
                return;
            }

            if (event.key === 'ArrowLeft' && previousArticleUrl) {
                window.location.href = previousArticleUrl;
            }

            if (event.key === 'ArrowRight' && nextArticleUrl) {
                window.location.href = nextArticleUrl;
            }
        });
    })();



    (function () {
        const articleTitle = document.querySelector('#article-top');
        const contentDiv = document.querySelector('.journal-text');
        const tocList = document.getElementById('toc-list');
        const tocTitle = document.getElementById('toc-title');
        const tocProgress = document.getElementById('toc-progress');
        const tocMobileList = document.getElementById('toc-mobile-list');
        const tocMobileTitle = document.getElementById('toc-mobile-title');
        const tocMobileProgress = document.getElementById('toc-mobile-progress');
        const mobileDrawer = document.getElementById('toc-mobile-drawer');
        const mobileOverlay = document.getElementById('toc-mobile-overlay');
        const drawerCloseBtn = document.getElementById('toc-drawer-close-btn');
        const scrollTopBtn = document.getElementById('scrollTopBtn');

        let headings = [];
        let headingTops = [];
        let tocItems = [];
        let tocMobileItems = [];
        let activeIndex = -1;
        let docScrollHeight = 0;
        let docClientHeight = 0;

        function buildToc() {
            if (!contentDiv || !tocList) return;

            const headingEls = contentDiv.querySelectorAll('h2, h3');
            if (headingEls.length === 0) return;

            const titleText = articleTitle ? articleTitle.textContent.trim() : '';

            if (tocTitle) tocTitle.style.display = 'none';
            if (tocMobileTitle) tocMobileTitle.style.display = 'none';

            const addItem = (level, text, targetId, isMobile) => {
                const isH1 = level === 1;
                const isRefs = targetId === 'refs-section';

                const li = document.createElement('li');
                li.className = isMobile ? 'toc-mobile-item' : 'toc-item';
                if (isRefs) li.classList.add('toc-refs-item');
                li.setAttribute('data-level', level);

                const a = document.createElement('a');
                a.className = isMobile ? 'toc-mobile-link' : 'toc-link';
                a.href = '#' + targetId;
                a.textContent = text;

                a.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (isMobile) closeMobileDrawer();

                    const scrollAction = () => {
                        if (isH1) {
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                        } else {
                            const target = document.getElementById(targetId);
                            if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    };
                    isMobile ? setTimeout(scrollAction, 100) : scrollAction();
                });

                const dot = document.createElement('span');
                dot.className = isMobile ? 'toc-mobile-dot' : 'toc-dot';

                if (isMobile) {
                    li.appendChild(dot);
                    li.appendChild(a);
                    tocMobileList.appendChild(li);
                    tocMobileItems.push(li);
                } else {
                    li.appendChild(a);
                    li.appendChild(dot);
                    tocList.appendChild(li);
                    tocItems.push(li);
                }
            };

            if (titleText) {
                if (articleTitle) headings.push({ el: articleTitle, id: 'article-top', level: 1, text: titleText });
                addItem(1, titleText, 'article-top', false);
                addItem(1, titleText, 'article-top', true);
            }

            headingEls.forEach((el, i) => {
                if (!el.id) el.id = 'heading-' + i;
                const level = parseInt(el.tagName.charAt(1));
                const text = el.textContent.trim();

                headings.push({ el: el, id: el.id, level: level, text: text });
                addItem(level, text, el.id, false);
                addItem(level, text, el.id, true);
            });

            const refsSection = document.getElementById('refs-section');
            if (refsSection) {
                const refsText = 'Kaynakça ve Notlar';
                headings.push({ el: refsSection, id: 'refs-section', level: 2, text: refsText });
                addItem(2, refsText, 'refs-section', false);
                addItem(2, refsText, 'refs-section', true);
            }
        }


        function recalcHeadingTops() {
            headingTops = headings.map(h => h.el.offsetTop);
            docScrollHeight = document.documentElement.scrollHeight;
            docClientHeight = document.documentElement.clientHeight;
        }

        function updateScrollSpy() {
            if (headings.length === 0) return;

            const scrollY = window.scrollY;
            const offset = 120;
            let currentIdx = -1;

            if ((window.innerHeight + Math.ceil(scrollY)) >= docScrollHeight - 10) {
                currentIdx = headings.length - 1;
            } else {
                for (let i = headings.length - 2; i >= 0; i--) {
                    if (scrollY >= headingTops[i] - offset) {
                        currentIdx = i;
                        break;
                    }
                }
            }

            if (currentIdx === activeIndex) return;
            activeIndex = currentIdx;

            [tocItems, tocMobileItems].forEach(list => {
                list.forEach((item, i) => {
                    item.classList.remove('toc-active', 'toc-passed');
                    if (i === activeIndex) item.classList.add('toc-active');
                    else if (i < activeIndex) item.classList.add('toc-passed');
                });
            });

            updateProgressLine();
        }

        function updateProgressLine() {
            if (headings.length === 0 || activeIndex < 0) {
                if (tocProgress) tocProgress.style.height = '0';
                if (tocMobileProgress) tocMobileProgress.style.height = '0';
                return;
            }

            const updateTrack = (itemsArr, progressNode) => {
                if (!progressNode || itemsArr.length === 0) return;
                const activeItem = itemsArr[activeIndex];
                if (!activeItem) return;

                const dotCenter = activeItem.offsetTop + activeItem.offsetHeight / 2;
                const firstItem = itemsArr[0];
                const startTop = firstItem.offsetTop + firstItem.offsetHeight / 2;
                const height = Math.max(0, dotCenter - startTop);

                progressNode.style.top = startTop + 'px';
                progressNode.style.height = height + 'px';
            };

            updateTrack(tocItems, tocProgress);
            updateTrack(tocMobileItems, tocMobileProgress);
        }

        function openMobileDrawer() {
            if (mobileDrawer) mobileDrawer.classList.add('toc-drawer-open');
            if (mobileOverlay) mobileOverlay.classList.add('toc-drawer-open');
            document.documentElement.style.overflow = 'hidden';
            document.body.style.overflow = 'hidden';
            setTimeout(updateProgressLine, 50);
        }

        function closeMobileDrawer() {
            if (mobileDrawer) mobileDrawer.classList.remove('toc-drawer-open');
            if (mobileOverlay) mobileOverlay.classList.remove('toc-drawer-open');
            document.documentElement.style.overflow = '';
            document.body.style.overflow = '';
        }

        if (scrollTopBtn) {
            scrollTopBtn.addEventListener('click', function () {
                const isDesktop = window.innerWidth >= 1280;
                if (isDesktop || scrollTopBtn.getAttribute('data-no-toc') === 'true') {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    if (mobileDrawer && mobileDrawer.classList.contains('toc-drawer-open')) {
                        closeMobileDrawer();
                    } else {
                        openMobileDrawer();
                    }
                }
            });
        }

        if (mobileOverlay) {
            mobileOverlay.addEventListener('click', closeMobileDrawer);
        }

        if (drawerCloseBtn) {
            drawerCloseBtn.addEventListener('click', closeMobileDrawer);
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeMobileDrawer();
            }
        });

        let isScrolling = false;
        window.addEventListener('scroll', function () {
            if (!isScrolling) {
                window.requestAnimationFrame(function () {
                    const scrollY = window.scrollY;
                    const scrollMax = docScrollHeight - docClientHeight;
                    const scrolled = scrollMax > 0 ? scrollY / scrollMax : 0;
                    const progressBar = document.getElementById("progress-bar");
                    if (progressBar) progressBar.style.transform = 'scaleX(' + scrolled + ')';

                    if (scrollTopBtn) {
                        if (scrollY > 300) {
                            scrollTopBtn.classList.remove('opacity-0', 'pointer-events-none');
                            scrollTopBtn.classList.add('opacity-100', 'translate-y-0');
                        } else {
                            scrollTopBtn.classList.add('opacity-0', 'pointer-events-none');
                            scrollTopBtn.classList.remove('opacity-100', 'translate-y-0');
                        }
                    }

                    updateScrollSpy();

                    isScrolling = false;
                });
                isScrolling = true;
            }
        }, { passive: true });

        document.addEventListener("DOMContentLoaded", function () {
            const refsContent = document.getElementById('refs-content');
            const toggleBtn = document.getElementById('toggle-refs');
            const refIcon = document.getElementById('ref-icon');

            if (contentDiv && refsContent) {
                document.querySelectorAll('.reference-sup a').forEach(link => {
                    link.addEventListener('click', function (e) {
                        const refId = this.innerText.replace(/\[|\]/g, '');
                        const targetItem = document.getElementById('ref-item-' + refId);

                        if (refsContent.classList.contains('hidden')) {
                            toggleReferences(true);
                        }

                        if (targetItem) {
                            targetItem.classList.add('reference-highlight');
                            setTimeout(() => {
                                targetItem.classList.remove('reference-highlight');
                            }, 2000);
                        }
                    });
                });

                document.querySelectorAll('.ref-link').forEach(link => {
                    link.addEventListener('click', function (e) {
                        e.preventDefault();
                        const refId = this.getAttribute('data-id');
                        const targetItem = document.getElementById('ref-item-' + refId);

                        if (refsContent.classList.contains('hidden')) {
                            toggleReferences(true);
                        }

                        if (targetItem) {
                            targetItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            targetItem.classList.add('reference-highlight');
                            setTimeout(() => {
                                targetItem.classList.remove('reference-highlight');
                            }, 2000);
                        }
                    });
                });

                function toggleReferences(forceOpen = false) {
                    const isHidden = refsContent.classList.contains('hidden');

                    if (forceOpen || isHidden) {
                        refsContent.classList.remove('hidden');
                        refIcon.innerText = "-";
                    } else {
                        refsContent.classList.add('hidden');
                        refIcon.innerText = "+";
                    }
                }

                if (toggleBtn) {
                    toggleBtn.addEventListener('click', () => toggleReferences());
                }
            }

            if (toggleBtn) {
                var refsWrapper = toggleBtn.closest('div');
                if (refsWrapper) {
                    refsWrapper.setAttribute('id', 'refs-section');
                    refsWrapper.style.scrollMarginTop = '100px';
                }
            }

            buildToc();
            recalcHeadingTops();

            if (headings.length === 0 && scrollTopBtn) {
                var tocIcon = scrollTopBtn.querySelector('.toc-toggle-icon');
                var scrollIcon = scrollTopBtn.querySelector('.scroll-top-icon');
                if (tocIcon) tocIcon.style.display = 'none';
                if (scrollIcon) scrollIcon.style.display = 'block';
                scrollTopBtn.setAttribute('data-no-toc', 'true');
            }

            requestAnimationFrame(function () {
                var desktopTrack = document.querySelector('.toc-track');
                if (desktopTrack && tocItems.length > 0 && tocList) {
                    var firstItem = tocItems[0];
                    var startTop = firstItem.offsetTop + firstItem.offsetHeight / 2;

                    var lastItem = tocItems[tocItems.length - 1];
                    var endTop = lastItem.offsetTop + lastItem.offsetHeight / 2;

                    var offset = 0;
                    if (lastItem.classList.contains('toc-refs-item')) {
                        offset = 5;
                    }

                    desktopTrack.style.top = startTop + 'px';
                    desktopTrack.style.height = Math.max(0, endTop - startTop - offset) + 'px';
                }

                var mobileTrack = document.querySelector('.toc-mobile-track');
                if (mobileTrack && tocMobileItems.length > 0 && tocMobileList) {
                    var origOpen = openMobileDrawer;
                    openMobileDrawer = function () {
                        origOpen();
                        setTimeout(function () {
                            requestAnimationFrame(function () {
                                var mFirstItem = tocMobileItems[0];
                                var mStartTop = mFirstItem.offsetTop + mFirstItem.offsetHeight / 2;

                                var mLastItem = tocMobileItems[tocMobileItems.length - 1];
                                var mEndTop = mLastItem.offsetTop + mLastItem.offsetHeight / 2;

                                var mOffset = 0;
                                if (mLastItem.classList.contains('toc-refs-item')) {
                                    mOffset = 5;
                                }

                                mobileTrack.style.top = mStartTop + 'px';
                                mobileTrack.style.height = Math.max(0, mEndTop - mStartTop - mOffset) + 'px';
                            });
                        }, 100);
                    };
                }
            });

            updateScrollSpy();
        });

        let resizeTimer;
        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                recalcHeadingTops();
            }, 150);
        });
    })();



    (function() {
        var shareBtn = document.getElementById('native-share-btn');
        var copyBtn = document.getElementById('copy-link-btn');
        var shareUrl = (window.FezadanArticleData ? window.FezadanArticleData.shareUrl : "");
        var shareTitle = (window.FezadanArticleData ? window.FezadanArticleData.shareTitle : "");

        if (shareBtn && navigator.share) {
            shareBtn.style.display = '';
            shareBtn.addEventListener('click', function() {
                navigator.share({ title: shareTitle, url: shareUrl }).catch(function() {});
            });
        }

        if (copyBtn) {
            copyBtn.addEventListener('click', function() {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(shareUrl).then(function() {
                        var ci = copyBtn.querySelector('.copy-icon');
                        var ck = copyBtn.querySelector('.check-icon');
                        if (ci) ci.style.display = 'none';
                        if (ck) ck.style.display = '';
                        setTimeout(function() {
                            if (ci) ci.style.display = '';
                            if (ck) ck.style.display = 'none';
                        }, 2000);
                    });
                } else {
                    var ta = document.createElement('textarea');
                    ta.value = shareUrl;
                    ta.style.position = 'fixed';
                    ta.style.opacity = '0';
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                }
            });
        }
    })();



    (function() {
        const articleId = (window.FezadanArticleData ? window.FezadanArticleData.articleId : 0);
        let maxScroll = 0;
        let secondsSpent = 0;
        let timer = null;
        const reportedMilestones = new Set();

        timer = setInterval(() => {
            secondsSpent++;
            if (secondsSpent % 15 === 0) {
                sendAnalytics();
            }
        }, 1000);

        function getScrollPercent() {
            const scrollMax = docScrollHeight - docClientHeight;
            if (scrollMax <= 0) return 0;
            const scrollY = window.scrollY || document.documentElement.scrollTop;
            return (scrollY / scrollMax) * 100;
        }

        window.addEventListener('scroll', function() {
            const pct = getScrollPercent();
            let currentMilestone = 0;
            if (pct >= 95) currentMilestone = 100;
            else if (pct >= 75) currentMilestone = 75;
            else if (pct >= 50) currentMilestone = 50;
            else if (pct >= 25) currentMilestone = 25;

            if (currentMilestone > maxScroll) {
                maxScroll = currentMilestone;
                if (!reportedMilestones.has(currentMilestone)) {
                    reportedMilestones.add(currentMilestone);
                    sendAnalytics();
                }
            }
        }, { passive: true });

        function sendAnalytics(isSync = false) {
            const url = '/tr/analytics/track';
            const formData = new FormData();
            formData.append('article_id', articleId);
            formData.append('scroll_depth', maxScroll);
            formData.append('seconds_spent', secondsSpent);

            if (isSync && typeof navigator.sendBeacon === 'function') {
                navigator.sendBeacon(url, formData);
            } else {
                fetch(url, {
                    method: 'POST',
                    body: formData,
                    keepalive: true
                }).catch(() => {});
            }
        }

        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'hidden') {
                sendAnalytics(true);
            }
        });
    })();
