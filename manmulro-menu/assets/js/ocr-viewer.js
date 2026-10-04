/**
 * MANMULRO MENU — Interactive OCR Bounding Box Overlay & Image Preprocessing (`assets/js/ocr-viewer.js`)
 *
 * Implements Sections #5.2, #5.5, #5.6, #5.8, #11:
 * - Renders clickable bounding boxes (`x`, `y`, `width`, `height` on a 500x540 coordinate canvas)
 * - Clicking a bounding box on the source menu board highlights the corresponding OCR/menu item
 * - Clicking an OCR/menu item highlights its bounding box on the source menu board (`[원본에서 보기]`)
 * - Supports image rotation, zoom, and contrast preprocessing (#5.2)
 */
(function () {
	'use strict';

	window.ManmulroOCRViewer = {
		rotation: 0,
		scale: 1,
		highContrast: false,

		/**
		 * Render bounding boxes onto a layer container (#5.5, #5.6, #11).
		 *
		 * @param {HTMLElement} layerEl
		 * @param {Array}       ocrItems
		 * @param {Function}    onSelectBox
		 * @param {number|null} highlightedOcrId
		 */
		renderBoxes: function (layerEl, ocrItems, onSelectBox, highlightedOcrId) {
			if (!layerEl) return;
			layerEl.innerHTML = '';

			(ocrItems || []).forEach(function (item, idx) {
				var box = item.bounding_box || { x: 80, y: 130 + idx * 65, width: 310, height: 48 };
				var div = document.createElement('div');
				div.className = 'mm-ocr-bbox';
				if (item.needs_review || (typeof item.confidence === 'number' && item.confidence < 0.85)) {
					div.classList.add('is-low-confidence');
				}
				if (highlightedOcrId && Number(item.ocr_id) === Number(highlightedOcrId)) {
					div.classList.add('is-highlighted');
				}

				// Percentage coordinates relative to 500x540 reference board
				div.style.left = ((box.x / 500) * 100).toFixed(2) + '%';
				div.style.top = ((box.y / 540) * 100).toFixed(2) + '%';
				div.style.width = ((box.width / 500) * 100).toFixed(2) + '%';
				div.style.height = ((box.height / 540) * 100).toFixed(2) + '%';
				div.dataset.ocrIndex = String(idx);
				div.dataset.ocrId = String(item.ocr_id || idx + 1);
				div.title = (item.parsed_name || item.text || '') + ' (' + Math.round((item.confidence || 0.9) * 100) + '%)';

				div.addEventListener('click', function () {
					if (typeof onSelectBox === 'function') {
						onSelectBox(idx, item);
					}
				});

				layerEl.appendChild(div);
			});
		},

		/**
		 * Apply preprocessing transforms (rotate, zoom, contrast #5.2) to source image.
		 *
		 * @param {HTMLImageElement} imgEl
		 */
		applyPreprocess: function (imgEl) {
			if (!imgEl) return;
			imgEl.style.transform = 'rotate(' + this.rotation + 'deg) scale(' + this.scale + ')';
			imgEl.style.filter = this.highContrast ? 'contrast(1.35) brightness(1.06)' : 'none';
		}
	};
})();
