# MANMULRO WordPress Plugins V1 (`manmulro-social-login` & `manmulro-invitation`)

구글 드라이브 명세서(`MANMULRO SOCIAL LOGIN`, `MANMULRO INVITATION`)를 기반으로 제작된 2종의 WordPress 플러그인입니다.

---

## 📦 포함된 플러그인 목록

### 1. `manmulro-social-login/` (만물로 통합 소셜 로그인 V1)
- **Plugin Slug**: `manmulro-social-login`
- **핵심 원칙**:
  - WordPress 기본 `wp_users` / `wp_usermeta`를 단일 회원 기준으로 사용 (`is_user_logged_in()`, `get_current_user_id()`, `wp_get_current_user()`)
  - 초대장 플러그인에 종속되지 않는 독립 아키텍처 + `MM_SL_Provider_Interface` 기반 확장 구조
  - **지원 로그인**: Kakao, Naver, Google, WordPress Email/Password
  - **소셜 계정 DB**: `wp_manmulro_social_accounts` (`UNIQUE(provider, provider_user_id)`)
  - **중복 계정 자동 통합 방지**: 이메일만 같다는 이유로 계정을 자동 통합하지 않으며, 로그인 후 마이페이지에서 직접 연결하도록 보호
  - **마지막 로그인 수단 연결 해제 보호**: 마지막 남은 로그인 수단 해제 시 `"다른 로그인 방법을 먼저 연결해주세요."` 차단
  - **Open Redirect 방지**: `wp_validate_redirect()` 및 내부 호스트 검증을 통과한 URL만 `?redirect=` 복귀 허용
  - **제공 숏코드**:
    - `[manmulro_login]` (`/login/`)
    - `[manmulro_signup]` (`/signup/`)
    - `[manmulro_my_account]` (`/my-account/`)

---

### 2. `manmulro-invitation/` (만물로 범용 초대장 플랫폼 V1)
- **Plugin Slug**: `manmulro-invitation`
- **목표**: *"세상의 모든 만남을 위한 범용 초대장 플랫폼"* (`Template + Flexible Fields + Event Data + Design + Sharing + RSVP`)
- **핵심 구현 사항 (명세서 45개 V1 핵심 기능 전수 반영)**:
  - **Custom Post Type & Taxonomy**: `mm_invitation`, `invitation_category` (결혼, 생일, 돌·백일, 회갑·칠순·팔순, 동창회, 친목모임, 등산, 골프, 사이클, 러닝, 회사행사, 학교행사, 개업, 집들이, 전시·공연, 종교행사, 여행, 기타 등 18개 초기 카테고리 + 카테고리별 기본 추천 항목 설정)
  - **데이터와 디자인 분리**: 5종 기본 템플릿(`Simple`, `Classic`, `Modern`, `Flower`, `Nature` + `FREE`/`PREMIUM` 구분)을 자유롭게 변경해도 입력한 데이터와 자유 항목이 그대로 유지됨
  - **자유 항목 시스템 (Flexible Fields)**: 제목, 한 줄 텍스트, 여러 줄 설명, 날짜, 시간, 장소, 주소, 전화번호(`전화하기`/`문자 보내기`), 링크, 사진, 사진앨범, 구분선, 참석여부, 사용자 정의 항목(예: `카트비: 25,000원`, `산행코스: 범어사 → 북문 → 고당봉`) 추가·수정·삭제·표시/숨김·Drag & Drop 순서 변경
  - **미디어 관리**: 대표 이미지 1장 + 사진앨범 최대 10장 업로드/순서 변경/Lightbox 확대보기 및 반응형 이미지 크기 최적화
  - **초대장 편집기**: PC 좌측 편집 / 우측 모바일 실시간 미리보기, 모바일 `[편집]` / `[미리보기]` 탭 전환
  - **고유 단축 URL & 공개 정책**: `/i/{code}` (DB Post ID 비노출), `DRAFT` / `PUBLISHED` / `ARCHIVED` / `PRIVATE` 상태, 공개 범위(링크를 아는 사람 / 비밀번호 보호 / 비공개), 공개기간(계속 공개 / 행사 후 30일 / 90일 / 직접 설정 — 만료 시 삭제하지 않고 보관), 기본 `NOINDEX` 적용
  - **공유 · QR · 인쇄 · 지도 · 일정**: 카카오톡 공유, 링크 복사, 자체 SVG QR 코드 생성, A4 / A5 / 엽서(Postcard) Print CSS 인쇄 및 PDF 저장, 네이버 지도/길찾기/주소 복사, D-Day(`D-15`, `D-DAY`, `행사가 종료되었습니다.`), 내 일정에 저장(`.ics` 및 Google 캘린더)
  - **RSVP & 방명록**: 별도 DB 테이블(`wp_mm_invitation_rsvp`, `wp_mm_invitation_guestbook`), 참석/불참/미정 및 인원 집계, 응답 마감일 설정, 상태 필터/검색 및 UTF-8 BOM **CSV 다운로드**, 방명록 ON/OFF 및 스팸 방지
  - **제공 숏코드**:
    - `[manmulro_invitation_editor]` (`/invitation-editor/`)
    - `[manmulro_my_invitations]` (`/my-invitations/`)

---

## 🛠️ WordPress 설치 방법

1. `manmulro-social-login` 폴더와 `manmulro-invitation` 폴더(또는 생성된 `.zip` 파일)를 WordPress의 `wp-content/plugins/` 경로에 업로드합니다.
2. WordPress 관리자 → **플러그인** 메뉴에서 **Manmulro Social Login**과 **Manmulro Invitation**을 활성화합니다.
3. **설정 → 만물로 로그인**에서 Kakao / Naver / Google OAuth Client ID 및 Secret을 설정합니다 (또는 `wp-config.php`에 `MM_SL_KAKAO_CLIENT_ID` 등의 상수를 정의합니다).
4. **초대장 → 대시보드 / 템플릿 / 카테고리 / RSVP / 방명록 / 설정**에서 플랫폼 전체를 관리할 수 있습니다.
