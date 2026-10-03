/**
 * Manmulro Invitation - Standalone SVG QR Code Generator (`qr.js`)
 * Generates scannable ISO/IEC 18004 QR Code SVGs locally for `/i/{code}` URLs and Print Layouts (#19, #38).
 */
(function (window) {
	'use strict';

	// GF(256) log/exp tables for Reed-Solomon Error Correction (Level L)
	var EXP = new Array(256);
	var LOG = new Array(256);
	(function initGalois() {
		var x = 1;
		for (var i = 0; i < 255; i++) {
			EXP[i] = x;
			LOG[x] = i;
			x <<= 1;
			if (x & 0x100) {
				x ^= 0x11d;
			}
		}
		EXP[255] = EXP[0];
	})();

	function gfMul(a, b) {
		if (a === 0 || b === 0) return 0;
		return EXP[(LOG[a] + LOG[b]) % 255];
	}

	function rsGeneratorPoly(degree) {
		var poly = [1];
		for (var i = 0; i < degree; i++) {
			var next = new Array(poly.length + 1).fill(0);
			var root = EXP[i];
			for (var j = 0; j < poly.length; j++) {
				next[j] ^= poly[j];
				next[j + 1] ^= gfMul(poly[j], root);
			}
			poly = next;
		}
		return poly;
	}

	function rsEncode(data, ecLen) {
		var gen = rsGeneratorPoly(ecLen);
		var msg = data.concat(new Array(ecLen).fill(0));
		for (var i = 0; i < data.length; i++) {
			var coef = msg[i];
			if (coef !== 0) {
				for (var j = 0; j < gen.length; j++) {
					msg[i + j] ^= gfMul(gen[j], coef);
				}
			}
		}
		return msg.slice(data.length);
	}

	// Version 1..6 capacities for Byte Mode (Level L, single block)
	var VERSIONS = [
		null,
		{ size: 21, totalData: 19, ecLen: 7, align: [] },
		{ size: 25, totalData: 34, ecLen: 10, align: [6, 18] },
		{ size: 29, totalData: 55, ecLen: 15, align: [6, 22] },
		{ size: 33, totalData: 80, ecLen: 20, align: [6, 26] },
		{ size: 37, totalData: 108, ecLen: 26, align: [6, 30] }
	];

	function utf8Bytes(str) {
		var out = [];
		for (var i = 0; i < str.length; i++) {
			var c = str.charCodeAt(i);
			if (c < 0x80) {
				out.push(c);
			} else if (c < 0x800) {
				out.push(0xc0 | (c >> 6), 0x80 | (c & 0x3f));
			} else {
				out.push(0xe0 | (c >> 12), 0x80 | ((c >> 6) & 0x3f), 0x80 | (c & 0x3f));
			}
		}
		return out;
	}

	function buildMatrix(text) {
		var bytes = utf8Bytes(text || 'https://manmulro.com');
		var ver = 1;
		while (ver < 5 && bytes.length + 2 > VERSIONS[ver].totalData) {
			ver++;
		}
		if (bytes.length + 2 > VERSIONS[ver].totalData) {
			bytes = bytes.slice(0, VERSIONS[ver].totalData - 2);
		}
		var cfg = VERSIONS[ver];

		// Bitstream: 0100 (byte mode) + 8-bit length + bytes + terminator + pad
		var bits = [];
		function pushBits(val, len) {
			for (var i = len - 1; i >= 0; i--) {
				bits.push((val >> i) & 1);
			}
		}
		pushBits(4, 4);
		pushBits(bytes.length, 8);
		for (var b = 0; b < bytes.length; b++) {
			pushBits(bytes[b], 8);
		}
		var maxBits = cfg.totalData * 8;
		for (var t = 0; t < 4 && bits.length < maxBits; t++) {
			bits.push(0);
		}
		while (bits.length % 8 !== 0) {
			bits.push(0);
		}
		var dataBytes = [];
		for (var k = 0; k < bits.length; k += 8) {
			var byteVal = 0;
			for (var m = 0; m < 8; m++) {
				byteVal = (byteVal << 1) | bits[k + m];
			}
			dataBytes.push(byteVal);
		}
		var padToggle = 0;
		while (dataBytes.length < cfg.totalData) {
			dataBytes.push(padToggle % 2 === 0 ? 0xec : 0x11);
			padToggle++;
		}

		var ecBytes = rsEncode(dataBytes, cfg.ecLen);
		var allBytes = dataBytes.concat(ecBytes);

		var N = cfg.size;
		var modules = [];
		var reserved = [];
		for (var r = 0; r < N; r++) {
			modules[r] = new Array(N).fill(false);
			reserved[r] = new Array(N).fill(false);
		}

		function setFinder(row, col) {
			for (var dr = -1; dr <= 7; dr++) {
				for (var dc = -1; dc <= 7; dc++) {
					var rr = row + dr;
					var cc = col + dc;
					if (rr < 0 || rr >= N || cc < 0 || cc >= N) continue;
					var on =
						(dr >= 0 && dr <= 6 && (dc === 0 || dc === 6)) ||
						(dc >= 0 && dc <= 6 && (dr === 0 || dr === 6)) ||
						(dr >= 2 && dr <= 4 && dc >= 2 && dc <= 4);
					modules[rr][cc] = on;
					reserved[rr][cc] = true;
				}
			}
		}

		setFinder(0, 0);
		setFinder(0, N - 7);
		setFinder(N - 7, 0);

		// Timing patterns
		for (var i = 8; i < N - 8; i++) {
			modules[6][i] = i % 2 === 0;
			reserved[6][i] = true;
			modules[i][6] = i % 2 === 0;
			reserved[i][6] = true;
		}

		// Alignment patterns
		if (cfg.align.length > 0) {
			for (var ai = 0; ai < cfg.align.length; ai++) {
				for (var aj = 0; aj < cfg.align.length; aj++) {
					var ar = cfg.align[ai];
					var ac = cfg.align[aj];
					if (reserved[ar][ac]) continue;
					for (var dr = -2; dr <= 2; dr++) {
						for (var dc = -2; dc <= 2; dc++) {
							modules[ar + dr][ac + dc] =
								Math.max(Math.abs(dr), Math.abs(dc)) !== 1;
							reserved[ar + dr][ac + dc] = true;
						}
					}
				}
			}
		}

		// Reserve format info areas
		for (var f = 0; f < 9; f++) {
			if (f < N) {
				reserved[8][f] = true;
				reserved[f][8] = true;
			}
		}
		for (var f2 = 0; f2 < 8; f2++) {
			reserved[8][N - 1 - f2] = true;
			reserved[N - 1 - f2][8] = true;
		}
		modules[N - 8][8] = true;

		// Place data bits upward/downward in 2-col strips
		var bitIdx = 0;
		var totalBits = allBytes.length * 8;
		var upward = true;
		for (var right = N - 1; right >= 1; right -= 2) {
			if (right === 6) right = 5;
			for (var vert = 0; vert < N; vert++) {
				var rowIdx = upward ? N - 1 - vert : vert;
				for (var lr = 0; lr < 2; lr++) {
					var colIdx = right - lr;
					if (!reserved[rowIdx][colIdx]) {
						var bit = false;
						if (bitIdx < totalBits) {
							bit = ((allBytes[bitIdx >> 3] >> (7 - (bitIdx & 7))) & 1) !== 0;
							bitIdx++;
						}
						// Apply mask 0: (row + col) % 2 === 0
						if ((rowIdx + colIdx) % 2 === 0) {
							bit = !bit;
						}
						modules[rowIdx][colIdx] = bit;
					}
				}
			}
			upward = !upward;
		}

		// Precomputed Format Bits for Level L (01) + Mask 0 (000) -> 0x77c4
		var formatBits = 0x77c4;
		for (var fi = 0; fi < 15; fi++) {
			var fbit = ((formatBits >> fi) & 1) !== 0;
			if (fi < 6) {
				modules[fi][8] = fbit;
			} else if (fi < 8) {
				modules[fi + 1][8] = fbit;
			} else {
				modules[N - 15 + fi][8] = fbit;
			}

			if (fi < 8) {
				modules[8][N - fi - 1] = fbit;
			} else if (fi < 9) {
				modules[8][15 - fi] = fbit;
			} else {
				modules[8][15 - fi - 1] = fbit;
			}
		}

		return modules;
	}

	function createSvg(url, size) {
		var matrix = buildMatrix(url);
		var N = matrix.length;
		var quiet = 2;
		var viewSize = N + quiet * 2;
		var px = size || 160;
		var pathParts = [];

		for (var r = 0; r < N; r++) {
			for (var c = 0; c < N; c++) {
				if (matrix[r][c]) {
					pathParts.push('M' + (c + quiet) + ',' + (r + quiet) + 'h1v1h-1z');
				}
			}
		}

		return (
			'<svg xmlns="http://www.w3.org/2000/svg" width="' +
			px +
			'" height="' +
			px +
			'" viewBox="0 0 ' +
			viewSize +
			' ' +
			viewSize +
			'" shape-rendering="crispEdges" role="img" aria-label="QR Code">' +
			'<rect width="100%" height="100%" fill="#ffffff" rx="1.5"/>' +
			'<path d="' +
			pathParts.join('') +
			'" fill="#111827"/>' +
			'</svg>'
		);
	}

	function renderAll() {
		var boxes = document.querySelectorAll('.mm-inv-qr-box');
		boxes.forEach(function (box) {
			var url = box.getAttribute('data-qr-url') || window.location.href;
			var size = parseInt(box.getAttribute('data-qr-size') || '160', 10);
			var target = box.querySelector('.mm-inv-qr-canvas') || box;
			target.innerHTML = createSvg(url, size);
		});
	}

	window.MMInvQR = {
		createSvg: createSvg,
		renderAll: renderAll
	};

	document.addEventListener('DOMContentLoaded', renderAll);
})(window);
