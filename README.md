# MANMULRO WordPress Plugins V1 (`manmulro-social-login`, `manmulro-invitation`, `manmulro-menu`)

구글 드라이브 명세서 폴더(`MANMULRO SOCIAL LOGIN`, `MANMULRO INVITATION`, `만물로 메뉴판 만들기 V1 기능 명세서`)를 기반으로 제작된 3종의 WordPress 플러그인입니다.

---

## 📦 포함된 플러그인 목록

### 1. `manmulro-social-login/` (`manmulro-social-login.zip`) — 만물로 통합 소셜 로그인 V1
- **Plugin Slug**: `manmulro-social-login`
- **핵심 원칙 (명세서 24개 섹션 전수 반영)**:
  - WordPress 기본 `wp_users` / `wp_usermeta`를 단일 회원 기준으로 사용 (`is_user_logged_in()`, `get_current_user_id()`, `wp_get_current_user()`)
  - 다른 서비스 플러그인에 종속되지 않는 독립 아키텍처 + `MM_SL_Provider_Interface` 기반 확장 구조
  - **지원 로그인**: Kakao, Naver, Google, WordPress Email/Password
  - **소셜 계정 DB**: `wp_manmulro_social_accounts` (`UNIQUE(provider, provider_user_id)`)
  - **중복 계정 자동 통합 방지**: 이메일만 같다는 이유로 계정을 자동 통합하지 않으며, 로그인 후 마이페이지에서 직접 연결하도록 보호
  - **마지막 로그인 수단 연결 해제 보호**: 마지막 남은 로그인 수단 해제 시 `"다른 로그인 방법을 먼저 연결해주세요."` 차단
  - **Open Redirect 방지**: `wp_validate_redirect()` 및 내부 호스트 검증을 통과한 URL만 `?redirect=` 복귀 허용
  - **제공 숏코드**: `[manmulro_login]`, `[manmulro_signup]`, `[manmulro_my_account]`

---

### 2. `manmulro-invitation/` (`manmulro-invitation.zip`) — 만물로 범용 초대장 플랫폼 V1
- **Plugin Slug**: `manmulro-invitation`
- **목표**: *"세상의 모든 만남을 위한 범용 초대장 플랫폼"* (`Template + Flexible Fields + Event Data + Design + Sharing + RSVP`)
- **핵심 구현 사항 (명세서 50개 섹션 및 45개 V1 핵심 기능 전수 반영)**:
  - **Custom Post Type & Taxonomy**: `mm_invitation`, `invitation_category` (결혼, 생일, 돌·백일, 회갑·칠순·팔순, 동창회, 친목모임, 등산, 골프, 사이클, 러닝, 회사행사, 학교행사, 개업, 집들이, 전시·공연, 종교행사, 여행, 기타 등 18개 초기 카테고리 + 코드 수정 없는 신규 카테고리 추천 항목 확장)
  - **데이터와 디자인 분리**: 5종 기본 템플릿(`Simple`, `Classic`, `Modern`, `Flower`, `Nature` + `FREE`/`PREMIUM` 구분)을 변경해도 입력한 데이터와 자유 항목이 그대로 유지됨
  - **자유 항목 시스템 (Flexible Fields)**: 제목, 한 줄 텍스트, 여러 줄 설명, 날짜, 시간, 장소, 주소, 전화번호(`전화하기`/`문자 보내기`), 링크, 사진, 사진앨범, 구분선, 참석여부, 사용자 정의 항목 추가·수정·삭제·표시/숨김·Drag & Drop 순서 변경
  - **미디어 관리**: 대표 이미지 1장 + 사진앨범 최대 10장 업로드/순서 변경/Lightbox 확대보기
  - **초대장 편집기**: PC 좌측 편집 / 우측 모바일 실시간 미리보기, 모바일 `[편집]` / `[미리보기]` 탭 전환
  - **고유 단축 URL & 공개 정책**: `/i/{code}`, `DRAFT` / `PUBLISHED` / `ARCHIVED` / `PRIVATE`, 공개 범위/기간, 기본 `NOINDEX`
  - **공유 · QR · 인쇄 · 지도 · 일정 · RSVP · 방명록**: 카카오톡 공유, 링크 복사, 자체 SVG QR 코드, A4/A5/엽서 인쇄, 네이버 지도/길찾기, D-Day, `.ics` 일정 저장, `wp_mm_invitation_rsvp` (UTF-8 BOM CSV 다운로드), `wp_mm_invitation_guestbook`
  - **제공 숏코드**: `[manmulro_invitation_editor]`, `[manmulro_my_invitations]`

---

### 3. `manmulro-menu/` (`manmulro-menu.zip`) — 만물로 메뉴판 만들기 V1
- **Plugin Slug**: `manmulro-menu`
- **핵심 슬로건**: *"메뉴는 한 번만 입력하세요. 기존 메뉴판이 있으면 사진으로, 없으면 직접 만들어보세요."*
- **핵심 구현 사항 (명세서 29개 섹션 전수 반영)**:
  - **공통 메뉴 데이터 구조 (#2.1)**: `[사진으로 시작하기 (OCR)]`와 `[직접 만들기]`가 별도 시스템이 아닌 **하나의 공통 메뉴 데이터(`CATEGORY` + `MENU_ITEM`)**로 통합되어 동작
  - **사용자 확인 중심 OCR 원칙 (#2.2, #5, #19)**:
    - 생성형 AI에 의존하지 않는 교체형(Pluggable) OCR 아키텍처 (`OCR ENGINE -> OCR ADAPTER -> NORMALIZED OCR RESULT -> MENU IMPORTER -> COMMON MENU DATA`)
    - 이미지 전처리(회전, 확대, 대비 보정) + 추출 텍스트·가격·Bounding Box 좌표(`x, y, width, height`)·신뢰도(`confidence`) 추출
    - 신뢰도 낮은 항목은 `"확인 필요"`로 표시하며, 절대 자동 확정하지 않고 사용자가 **`[확인하고 메뉴로 가져오기]`**를 클릭할 때만 공통 메뉴 데이터로 저장
    - PC 좌/우 분할, 모바일 상/하 분할 **원본 비교 화면** 제공 (원본 영역 클릭 ↔ 추출 항목 양방향 하이라이트)
  - **원본 메뉴판 보관 및 이미지 역할 분리 (#2.5, #10, #11, #28.4)**:
    - `SOURCE_IMAGE` (원본 메뉴판 사진 — OCR 및 비교 확인용, 고객 메뉴 이미지로 자동 사용 금지)와 `MENU_IMAGE` (실제 음식 사진 — 고객 메뉴판 노출용)를 엄격히 분리
    - 언제든 **`[원본 메뉴판 보기]`** 및 개별 메뉴의 **`[원본에서 보기]`**를 통해 원본 이미지 내 위치를 다시 확인 가능
  - **메뉴 구성 및 일괄 관리 (#6, #7, #16)**:
    - 업종 선택 (`음식점`, `카페`, `주점`, `베이커리`, `미용/뷰티`, `서비스 가격표`, `기타`) 및 기본 카테고리 (`식사`, `사이드`, `음료`, `주류`)
    - 메뉴 추가·수정·삭제·**메뉴 복제**(`아메리카노 HOT` → `아메리카노 ICE`)·순서 변경·상태(`ACTIVE` 판매중 / `SOLD_OUT` 품절 / `HIDDEN` 숨김)·태그(`대표 메뉴`, `인기 메뉴`, `추천 메뉴`, `신메뉴`, `매운 메뉴`)
    - **일괄 관리 (#16)**: 다중 선택 후 가격 일괄 조정(`+500원`, `+1,000원`, `-500원`), 일괄 상태 변경, 일괄 카테고리 이동
  - **개별 메뉴 상세정보 및 고유 상세페이지 (#8, #9, #28.6, #28.7)**:
    - 필요한 메뉴에만 선택 입력: 상세 설명(`이 음식은 어떤 음식인가요?`), 주요 재료 및 재료 설명, 맛 특징(`매운맛`, `단맛`, `짠맛`, `신맛`, `고소함`, `담백함` → `●●●○○`), 추천 대상, 알레르기 정보(`우유`, `계란`, `대두`, `밀`, `땅콩`, `견과류`, `갑각류`, `생선`, `기타`), 원산지 정보(`돼지고기: 국내산 한돈`)
    - **알레르기 및 원산지 안전 원칙 (#28.7)**: 시스템이 절대 추측하지 않으며 사용자가 직접 체크·입력한 정보만 표시
    - 각 메뉴마다 고유 URL **`/menu/{store}/{menu-item}`** 생성 및 직접 공유 지원
  - **8종 디자인 템플릿 & 실시간 미리보기 (#12)**:
    - `한식`, `카페`, `고급 레스토랑`, `심플`, `모던`, `전통`, `베이커리`, `주점` + 색상/글꼴/가격 스타일/메뉴 사진 표시 여부 설정
  - **영구 QR 모바일 메뉴판 & A4/A3 인쇄용 메뉴판 (#13, #14, #28.5)**:
    - 전용 모바일 주소 **`/menu/{store}`** — 메뉴나 가격을 수정해도 QR 코드와 URL은 절대 변경되지 않음
    - 빠른 카테고리 이동 바 (`[전체] [식사] [사이드] [음료] [주류]`)
    - 동일한 공통 메뉴 데이터로 **A4 / A3 (세로·가로)** 인쇄용 메뉴판 및 PDF/이미지 출력 지원
  - **제공 숏코드**: `[manmulro_menu_builder]` (`/menu-builder/`)
