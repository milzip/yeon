# MANMULRO SOCIAL LOGIN

## 만물로 통합 소셜 로그인 시스템 개발 명세서

- **Version**: 1.0
- **Platform**: WordPress
- **Project**: MANMULRO
- **Plugin Slug**: `manmulro-social-login`

---

## 1. 프로젝트 목적

Manmulro Social Login은 특정 서비스에 종속되지 않는 만물로 전체의 통합 회원 인증 시스템이다.
현재 첫 번째 적용 서비스는 '만물로 초대장'이지만, 향후 만물로에서 제공하는 모든 서비스가 동일한 회원 계정을 사용한다.

```
MANMULRO
├── 초대장
├── 커뮤니티
├── 모임
├── 쇼핑
├── 예약
└── 향후 서비스
```

모든 서비스는 WordPress User를 공통 회원으로 사용한다.

---

## 2. 핵심 개발 원칙

1. WordPress 기본 User 시스템을 회원의 기준으로 사용한다.
2. 별도의 독립 회원 DB를 만들지 않는다.
3. 소셜 로그인은 인증수단으로만 사용한다.
4. Kakao / Naver / Google을 지원한다.
5. 이메일/비밀번호 로그인도 유지한다.
6. 특정 서비스에 종속되지 않는다.
7. 초대장 플러그인과 독립적으로 작동한다.
8. Provider 확장이 가능한 구조로 개발한다.
9. 개인정보는 필요한 최소한만 저장한다.
10. WordPress 표준 인증 기능을 최대한 활용한다.

---

## 3. 지원 로그인

- **V1 공식 지원**: Kakao, Naver, Google, WordPress Email / Password
- **향후 추가 가능**: Apple, LINE, Facebook, 기타 OAuth / OIDC Provider

---

## 4. 플러그인 구조

```
manmulro-social-login/
├── manmulro-social-login.php
├── includes/
│   ├── class-auth.php
│   ├── class-provider-interface.php
│   ├── class-user.php
│   ├── class-account-linker.php
│   ├── class-security.php
│   ├── class-session.php
│   └── class-redirect.php
├── providers/
│   ├── kakao/
│   │   └── class-kakao-provider.php
│   ├── naver/
│   │   └── class-naver-provider.php
│   └── google/
│       └── class-google-provider.php
├── public/
│   ├── login.php
│   ├── signup.php
│   └── account.php
├── admin/
│   ├── settings.php
│   └── users.php
└── assets/
    ├── css/
    └── js/
```

---

## 5. 소셜 계정 DB (`wp_manmulro_social_accounts`)

- Fields: `id`, `user_id`, `provider`, `provider_user_id`, `created_at`, `updated_at`
- Constraint: `UNIQUE(provider, provider_user_id)`

---

## 6. 핵심 보안 및 정책

- **중복 계정 방지**: 이메일 주소만 같다는 이유로 자동으로 계정을 합치지 않는다. Provider 고유 User ID를 기준으로 식별하며, 로그인 상태에서 직접 계정을 연결할 수 있도록 한다.
- **연결 해제 보호**: 마지막 로그인 수단을 제거할 수 없도록 차단 (`"다른 로그인 방법을 먼저 연결해주세요."`).
- **Redirect 검증**: Open Redirect 방지를 위해 허용된 내부 URL만 사용한다.
- **최소 수집 및 토큰 비저장**: OAuth Access Token은 장기 저장하지 않으며 필요한 최소 정보만 사용한다.
