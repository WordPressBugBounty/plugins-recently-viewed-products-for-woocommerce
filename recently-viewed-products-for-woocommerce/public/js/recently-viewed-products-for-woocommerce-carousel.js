/**
 * Lightweight, dependency-free carousel for Recently Viewed Products.
 *
 * Uses native horizontal scroll + scroll-snap for robust touch behaviour and
 * adds arrows, pagination dots, and optional autoplay on top.
 *
 * @package RVPW_Recently_Viewed_Products_For_Woocommerce
 */
(function () {
	'use strict';

	function init(root) {
		var viewport = root.querySelector('.rvpw-carousel__viewport');
		var track = root.querySelector('.rvpw-carousel__track');

		if (!viewport || !track) {
			return;
		}

		var slides = Array.prototype.slice.call(track.children);
		if (slides.length === 0) {
			return;
		}

		var prevBtn = root.querySelector('.rvpw-carousel__arrow--prev');
		var nextBtn = root.querySelector('.rvpw-carousel__arrow--next');
		var dotsWrap = root.querySelector('.rvpw-carousel__dots');

		var autoplay = root.getAttribute('data-autoplay') === '1';
		var loop = root.getAttribute('data-loop') === '1';
		var speed = parseInt(root.getAttribute('data-speed'), 10) || 4000;

		var autoplayTimer = null;
		var resizeTimer = null;

		function pageWidth() {
			return viewport.clientWidth;
		}

		function pageCount() {
			if (viewport.clientWidth === 0) {
				return 1;
			}
			return Math.max(1, Math.ceil((track.scrollWidth - 1) / viewport.clientWidth));
		}

		function currentPage() {
			return Math.round(viewport.scrollLeft / pageWidth());
		}

		function atEnd() {
			return viewport.scrollLeft + viewport.clientWidth >= track.scrollWidth - 2;
		}

		function goTo(page) {
			viewport.scrollTo({ left: page * pageWidth(), behavior: 'smooth' });
		}

		function next() {
			if (atEnd()) {
				if (loop) {
					goTo(0);
				}
				return;
			}
			goTo(currentPage() + 1);
		}

		function prev() {
			if (viewport.scrollLeft <= 2) {
				if (loop) {
					goTo(pageCount() - 1);
				}
				return;
			}
			goTo(currentPage() - 1);
		}

		function buildDots() {
			if (!dotsWrap) {
				return;
			}

			dotsWrap.innerHTML = '';
			var total = pageCount();

			if (total <= 1) {
				dotsWrap.setAttribute('hidden', 'hidden');
				return;
			}

			dotsWrap.removeAttribute('hidden');

			for (var i = 0; i < total; i++) {
				var dot = document.createElement('button');
				dot.type = 'button';
				dot.className = 'rvpw-carousel__dot';
				dot.setAttribute('role', 'tab');
				dot.setAttribute('aria-label', 'Go to slide ' + (i + 1));
				dot.dataset.page = String(i);
				dot.addEventListener('click', (function (page) {
					return function () {
						goTo(page);
					};
				})(i));
				dotsWrap.appendChild(dot);
			}

			updateUi();
		}

		function updateUi() {
			var page = currentPage();

			if (dotsWrap) {
				var dots = dotsWrap.querySelectorAll('.rvpw-carousel__dot');
				Array.prototype.forEach.call(dots, function (dot, index) {
					var active = index === page;
					dot.classList.toggle('is-active', active);
					dot.setAttribute('aria-selected', active ? 'true' : 'false');
				});
			}

			if (!loop) {
				if (prevBtn) {
					prevBtn.disabled = viewport.scrollLeft <= 2;
				}
				if (nextBtn) {
					nextBtn.disabled = atEnd();
				}
			}
		}

		function startAutoplay() {
			if (!autoplay || autoplayTimer) {
				return;
			}
			autoplayTimer = window.setInterval(next, speed);
		}

		function stopAutoplay() {
			if (autoplayTimer) {
				window.clearInterval(autoplayTimer);
				autoplayTimer = null;
			}
		}

		if (prevBtn) {
			prevBtn.addEventListener('click', prev);
		}
		if (nextBtn) {
			nextBtn.addEventListener('click', next);
		}

		viewport.addEventListener('scroll', function () {
			window.requestAnimationFrame(updateUi);
		}, { passive: true });

		window.addEventListener('resize', function () {
			window.clearTimeout(resizeTimer);
			resizeTimer = window.setTimeout(buildDots, 200);
		});

		// Pause autoplay on interaction.
		['mouseenter', 'focusin', 'touchstart'].forEach(function (evt) {
			root.addEventListener(evt, stopAutoplay, { passive: true });
		});
		['mouseleave', 'focusout'].forEach(function (evt) {
			root.addEventListener(evt, startAutoplay);
		});
		document.addEventListener('visibilitychange', function () {
			if (document.hidden) {
				stopAutoplay();
			} else {
				startAutoplay();
			}
		});

		buildDots();
		updateUi();
		startAutoplay();
	}

	function boot() {
		var carousels = document.querySelectorAll('[data-rvpw-carousel]');
		Array.prototype.forEach.call(carousels, init);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
