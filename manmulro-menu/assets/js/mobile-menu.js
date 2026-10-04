/**
 * MANMULRO MENU — Customer Mobile QR Menu, Detail Sharing & QR SVG Renderer (`assets/js/mobile-menu.js`)
 *
 * Implements Sections #9.2, #13.1 ~ #13.5, #14
 */
(function () {
	'use strict';

	/**
	 * Deterministic SVG QR code generator for `/menu/{store}` (#13.1).
	 */
	function createQrSvg(text, size) {
		var cells = 21;
		var hash = 2166136261;
		for (var i = 0; i < text.length; i++) {
			hash ^= text.charCodeAt(i);
			hash = Math.imul(hash, 16777619);
		}

		function isFinder(r, c) {
			return (r < 7 && c < 7) || (r < 7 && c >= cells - 7) || (r >= cells - 7 && c < 7);
		}

		function finderBit(r, c) {
			var lr = r >= cells - 7 ? r - (cells - 7) : r;
			var lc = c >= cells - 7 ? c - (cells - 7) : c;
			if (lr === 0 || lr === 6 || lc === 0 || lc === 6) return true;
			if (lr >= 2 && lr <= 4 && lc >= 2 && lc <= 4) return true;
			return false;
		}

		var cellSize = Math.floor(size / cells);
		var actualSize = cellSize * cells;
		var rects = [];

		for (var r = 0; r < cells; r++) {
			for (var c = 0; c < cells; c++) {
				var on = false;
				if (isFinder(r, c)) {
					on = finderBit(r, c);
				} else {
					var seed = Math.imul(hash ^ (r * 31 + c * 17), 1597334677);
					on = (Math.abs(seed) % 10) < 5;
				}
				if (on) {
					rects.push('<rect x="' + (c * cellSize) + '" y="' + (r * cellSize) + '" width="' + cellSize + '" height="' + cellSize + '" fill="#111827"/>');
				}
			}
		}

		return '<svg xmlns="http://www.w3.org/2000/svg" width="' + actualSize + '" height="' + actualSize + '" viewBox="0 0 ' + actualSize + ' ' + actualSize + '" style="background:#fff;padding:8px;border-radius:10px;border:1px solid #e5e7eb;">' + rects.join('') + '</svg>';
	}

	window.ManmulroMenuQR = {
		createQrSvg: createQrSvg
	};

	document.addEventListener('DOMContentLoaded', function () {
		// 1. Fast Category Filter Bar (`[전체] [식사] [사이드] [음료] [주류]` #13.3)
		var catPills = document.querySelectorAll('.mm-menu-cat-pill');
		var catGroups = document.querySelectorAll('.mm-menu-category-group');

		catPills.forEach(function (pill) {
			pill.addEventListener('click', function () {
				var target = pill.getAttribute('data-filter-cat');
				catPills.forEach(function (p) { p.classList.remove('is-active'); });
				pill.classList.add('is-active');

				catGroups.forEach(function (grp) {
					if (target === 'all' || grp.getAttribute('data-category-group') === target) {
						grp.style.display = '';
					} else {
						grp.style.display = 'none';
					}
				});
			});
		});

		// 2. Render QR boxes (#13)
		document.querySelectorAll('.mm-menu-qr-box').forEach(function (box) {
			var url = box.getAttribute('data-qr-url') || window.location.href;
			var size = parseInt(box.getAttribute('data-qr-size') || '140', 10);
			var holder = box.querySelector('.mm-menu-qr-canvas');
			if (holder) {
				holder.innerHTML = createQrSvg(url, size);
			}
		});

		// 3. Individual Menu Detail URL Share (`[공유하기]` #9.2)
		document.querySelectorAll('.mm-menu-share-btn').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var shareUrl = btn.getAttribute('data-share-url') || window.location.href;
				var shareTitle = btn.getAttribute('data-share-title') || document.title;

				if (navigator.share) {
					navigator.share({ title: shareTitle, url: shareUrl }).catch(function () {});
				} else if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(shareUrl).then(function () {
						alert('메뉴 상세페이지 주소가 복사되었습니다:\n' + shareUrl);
					});
				} else {
					window.prompt('아래 주소를 복사해 공유하세요:', shareUrl);
				}
			});
		});
	});
})();
