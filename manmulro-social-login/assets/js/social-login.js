/**
 * Manmulro Social Login Frontend Script
 * Handles Unlink Protection (#14) and Withdrawal confirmation (#21).
 */
(function () {
	'use strict';

	document.addEventListener('click', function (e) {
		var unlinkBtn = e.target.closest('.mm-sl-unlink-btn');
		if (unlinkBtn) {
			var canUnlink = unlinkBtn.getAttribute('data-can-unlink');
			if (canUnlink === '0') {
				e.preventDefault();
				var msg = (window.mmSocialLogin && window.mmSocialLogin.i18n && window.mmSocialLogin.i18n.lastMethodErr)
					? window.mmSocialLogin.i18n.lastMethodErr
					: '다른 로그인 방법을 먼저 연결해주세요.';
				window.alert(msg);
				return;
			}

			var confirmMsg = (window.mmSocialLogin && window.mmSocialLogin.i18n && window.mmSocialLogin.i18n.confirmUnlink)
				? window.mmSocialLogin.i18n.confirmUnlink
				: '해당 소셜 계정 연결을 해제하시겠습니까?';
			if (!window.confirm(confirmMsg)) {
				e.preventDefault();
			}
		}
	});

	document.addEventListener('submit', function (e) {
		var withdrawForm = e.target.closest('.mm-sl-withdraw-form');
		if (withdrawForm) {
			var confirmWithdraw = (window.mmSocialLogin && window.mmSocialLogin.i18n && window.mmSocialLogin.i18n.confirmWithdraw)
				? window.mmSocialLogin.i18n.confirmWithdraw
				: '정말로 회원 탈퇴를 요청하시겠습니까?';
			if (!window.confirm(confirmWithdraw)) {
				e.preventDefault();
			}
		}
	});
})();
