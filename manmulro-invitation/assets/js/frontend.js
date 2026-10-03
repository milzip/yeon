/**
 * Manmulro Invitation - Public Frontend Interactions (`frontend.js`)
 *
 * Handles:
 * - Photo Gallery Lightbox (#13)
 * - Copy Link & Copy Address (#18, #20, #21)
 * - QR Code Drawer toggle (#19)
 * - KakaoTalk Share (#18)
 * - RSVP Form AJAX submission (#23)
 * - Guestbook Form AJAX submission (#28)
 */
(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		if (window.MMInvQR) {
			window.MMInvQR.renderAll();
		}

		// 1. Copy Link / Copy Address (#18, #20, #21)
		document.addEventListener('click', function (e) {
			var copyBtn = e.target.closest('[data-copy-text]');
			if (!copyBtn) return;

			var text = copyBtn.getAttribute('data-copy-text');
			if (!text) return;

			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(text).then(function () {
					showTempLabel(copyBtn, '✅ 복사 완료!');
				});
			} else {
				var ta = document.createElement('textarea');
				ta.value = text;
				document.body.appendChild(ta);
				ta.select();
				document.execCommand('copy');
				document.body.removeChild(ta);
				showTempLabel(copyBtn, '✅ 복사 완료!');
			}
		});

		function showTempLabel(btn, msg) {
			var orig = btn.textContent;
			btn.textContent = msg;
			setTimeout(function () {
				btn.textContent = orig;
			}, 1800);
		}

		// 2. Gallery Lightbox (#13)
		var lightbox = document.getElementById('mm-inv-lightbox');
		var lightboxImg = document.getElementById('mm-inv-lightbox-img');

		document.addEventListener('click', function (e) {
			var item = e.target.closest('.mm-inv-gallery-item');
			if (item && lightbox && lightboxImg) {
				var src = item.getAttribute('data-lightbox-src');
				if (src) {
					lightboxImg.src = src;
					lightbox.hidden = false;
				}
			}

			if (
				lightbox &&
				!lightbox.hidden &&
				(e.target === lightbox || e.target.closest('.mm-inv-lightbox__close'))
			) {
				lightbox.hidden = true;
			}
		});

		// 3. QR Drawer Toggle (#19)
		var qrToggleBtn = document.getElementById('mm-btn-toggle-qr');
		var qrDrawer = document.getElementById('mm-inv-qr-drawer');
		if (qrToggleBtn && qrDrawer) {
			qrToggleBtn.addEventListener('click', function () {
				qrDrawer.hidden = !qrDrawer.hidden;
				if (!qrDrawer.hidden && window.MMInvQR) {
					window.MMInvQR.renderAll();
				}
			});
		}

		// 4. KakaoTalk Share (#18)
		var kakaoBtn = document.getElementById('mm-btn-kakao-share');
		if (kakaoBtn) {
			kakaoBtn.addEventListener('click', function () {
				var url = kakaoBtn.getAttribute('data-url') || window.location.href;
				var title = kakaoBtn.getAttribute('data-title') || document.title;
				var desc = kakaoBtn.getAttribute('data-desc') || '만물로 초대장이 도착했습니다.';
				var image = kakaoBtn.getAttribute('data-image') || '';

				if (window.Kakao && window.mmInvPublic && window.mmInvPublic.kakaoJsKey) {
					if (!window.Kakao.isInitialized()) {
						window.Kakao.init(window.mmInvPublic.kakaoJsKey);
					}
					window.Kakao.Share.sendDefault({
						objectType: 'feed',
						content: {
							title: title,
							description: desc,
							imageUrl: image,
							link: { mobileWebUrl: url, webUrl: url }
						},
						buttons: [
							{
								title: '초대장 보기',
								link: { mobileWebUrl: url, webUrl: url }
							}
						]
					});
					return;
				}

				if (navigator.share) {
					navigator.share({ title: title, text: desc, url: url }).catch(function () {});
				} else if (navigator.clipboard) {
					navigator.clipboard.writeText(url).then(function () {
						window.alert('초대장 링크가 복사되었습니다. 카카오톡 대화창에 붙여넣어 공유하세요!\n' + url);
					});
				}
			});
		}

		// 5. RSVP Form Submission (#23)
		var rsvpForm = document.getElementById('mm-inv-rsvp-form');
		if (rsvpForm) {
			var countWrap = document.getElementById('mm_rsvp_count_wrap');
			rsvpForm.addEventListener('change', function (e) {
				if (e.target.name === 'status' && countWrap) {
					countWrap.style.display = e.target.value === 'declined' ? 'none' : '';
				}
			});

			rsvpForm.addEventListener('submit', function (e) {
				e.preventDefault();
				var feedback = document.getElementById('mm-rsvp-feedback');
				if (!window.mmInvPublic || !window.mmInvPublic.ajaxUrl) return;

				var fd = new FormData(rsvpForm);
				fd.append('action', 'mm_inv_submit_rsvp');
				fd.append('nonce', window.mmInvPublic.nonce);

				fetch(window.mmInvPublic.ajaxUrl, {
					method: 'POST',
					body: fd
				})
					.then(function (r) {
						return r.json();
					})
					.then(function (res) {
						if (feedback) {
							feedback.textContent =
								res.data && res.data.message
									? res.data.message
									: res.success
									? '응답이 완료되었습니다.'
									: '오류가 발생했습니다.';
							feedback.style.color = res.success ? '#059669' : '#dc2626';
						}
						if (res.success) {
							rsvpForm.reset();
						}
					});
			});
		}

		// 6. Guestbook Form Submission (#28)
		var gbForm = document.getElementById('mm-inv-guestbook-form');
		var gbList = document.getElementById('mm-guestbook-list');
		if (gbForm) {
			gbForm.addEventListener('submit', function (e) {
				e.preventDefault();
				var feedback = document.getElementById('mm-guestbook-feedback');
				if (!window.mmInvPublic || !window.mmInvPublic.ajaxUrl) return;

				var fd = new FormData(gbForm);
				fd.append('action', 'mm_inv_submit_guestbook');
				fd.append('nonce', window.mmInvPublic.nonce);

				fetch(window.mmInvPublic.ajaxUrl, {
					method: 'POST',
					body: fd
				})
					.then(function (r) {
						return r.json();
					})
					.then(function (res) {
						if (feedback) {
							feedback.textContent =
								res.data && res.data.message ? res.data.message : '';
							feedback.style.color = res.success ? '#059669' : '#dc2626';
						}
						if (res.success && res.data && res.data.entry && gbList) {
							var emptyItem = gbList.querySelector('.mm-inv-guestbook-empty');
							if (emptyItem) emptyItem.remove();

							var li = document.createElement('li');
							li.className = 'mm-inv-guestbook-item';
							li.innerHTML =
								'<div class="mm-inv-guestbook-item__head"><strong></strong><span></span></div><p class="mm-inv-guestbook-item__msg"></p>';
							li.querySelector('strong').textContent = res.data.entry.name;
							li.querySelector('span').textContent = res.data.entry.created_at;
							li.querySelector('p').textContent = res.data.entry.message;
							gbList.prepend(li);
							gbForm.reset();
						}
					});
			});
		}
	});
})();
