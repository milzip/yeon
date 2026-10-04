# MANMULRO INVITATION

## 만물로 범용 초대장 플랫폼 V1 개발 명세서

- **Version**: 1.0
- **Platform**: WordPress
- **Project**: MANMULRO
- **Plugin Slug**: `manmulro-invitation`

---

## 1. 프로젝트 목적

만물로 WordPress 사이트 안에서 세상의 다양한 초대장을 만들 수 있는 범용 초대장 플랫폼을 구축한다.
특정 결혼 청첩장 서비스로 개발하지 않는다.

- **목표**: `"모든 만남을 위한 초대장"`
- **사용자 기능**:
  - 초대장 제작 / 저장 / 수정 / 복제
  - 링크 공유 / QR 공유 / 카카오톡 공유 / 인쇄
  - RSVP 관리 / 사진앨범 관리
- AI는 V1에서 사용하지 않는다. WordPress 기능을 중심으로 구현한다.

---

## 2. 핵심 개발 원칙

초대장을 종류별로 각각 프로그램하지 않는다.

**공통 엔진**:
`Template + Flexible Fields / Blocks + Event Data + Design + Sharing + RSVP`

이를 통해 새로운 초대장 종류를 코드 수정 없이 추가할 수 있도록 한다.

---

## 3. 회원 시스템

초대장 플러그인은 자체 회원 시스템을 만들지 않는다.
Manmulro Social Login 또는 WordPress 기본 인증을 사용한다.
- 지원 로그인: Kakao, Naver, Google, Email
- 초대장 플러그인은 로그인 방법과 관계없이 **WordPress User ID**만 사용한다.

---

## 4. 사용자 정책

- **초대장을 만드는 사람**: 회원가입 / 로그인 필요
- **초대받은 사람**: 회원가입 필요 없음
  - 비회원 상태에서 초대장 열람, 사진 보기, 지도/길찾기, RSVP, 방명록, 일정 저장 사용 가능

---

## 5. Custom Post Type (`mm_invitation`)

초대장 하나를 하나의 Custom Post(`mm_invitation`)로 관리한다.
- 기본 정보: `ID`, `Author`, `Title`, `Category`, `Template`, `Status`, `Event Date`, `Created Date`, `Updated Date`

---

## 6. 초대장 카테고리 (`invitation_category`)

초기 18개 카테고리:
- 결혼, 생일, 돌·백일, 회갑·칠순·팔순, 동창회, 친목모임, 등산, 골프, 사이클, 러닝, 회사행사, 학교행사, 개업, 집들이, 전시·공연, 종교행사, 여행, 기타
- 관리자는 WordPress 관리자에서 카테고리를 추가/수정/삭제할 수 있다.

---

## 7. 초대장 제작 흐름

새 초대장 만들기 → 초대장 종류 선택 → 템플릿 선택 → 기본 내용 입력 → 항목 추가/삭제 → 항목 순서 변경 → 사진 업로드 → 미리보기 → 임시저장 → 공개 → 고유 URL 생성 → 공유 / QR / 인쇄 → RSVP 관리

---

## 8. 자유 항목 시스템 (`[ + 항목 추가 ]`)

지원 항목 (14종):
- 제목 (`title`), 한 줄 텍스트 (`text`), 여러 줄 설명 (`textarea`), 날짜 (`date`), 시간 (`time`), 장소 (`location`), 주소 (`address`), 전화번호 (`phone`), 링크 (`link`), 사진 (`photo`), 사진앨범 (`gallery`), 구분선 (`divider`), 참석여부 (`rsvp`), 사용자 정의 항목 (`custom`)

---

## 9. 사용자 정의 항목

- 예 1: 항목명 `카트비` / 내용 `25,000원`
- 예 2: 항목명 `산행코스` / 내용 `범어사 → 북문 → 고당봉`

---

## 10. 항목 관리

각 항목은 **수정 / 삭제 / 표시 / 숨김 / Drag & Drop 순서 변경** 기능을 가진다.

---

## 11. 데이터와 디자인 분리

Event Data와 Template Design을 분리한다. 사용자가 디자인(예: `Nature Template` → `Modern Template`)을 변경해도 날짜, 시간, 장소, 초대말, 사진 등 입력한 내용은 그대로 유지된다.

---

## 12~14. 미디어 (대표 이미지 1장 · 사진앨범 최대 10장 · 이미지 최적화)

- 대표 이미지 1장: 초대장 Cover, 공유 썸네일, 내 초대장 목록 카드에 사용
- 사진앨범 최대 10장: 업로드, 삭제, 순서 변경(Drag & Drop), 썸네일, 확대보기(Lightbox), `5 / 10` 카운터 표시
- 이미지 최적화: WordPress Image Size(`mm_inv_cover`, `mm_inv_gallery_thumb`, `mm_inv_gallery_large`), Lazy Loading, WebP 지원

---

## 15. 초대장 편집기

- **PC**: 왼쪽 초대장 편집 / 오른쪽 모바일 실시간 미리보기
- **모바일**: `[편집]` / `[미리보기]` 탭 방식

---

## 16~19. 저장 상태 · 고유 URL · 공유 · QR 코드

- **저장 상태**: `DRAFT`(임시저장), `PUBLISHED`(공개), `ARCHIVED`(보관), `PRIVATE`(비공개)
- **고유 URL**: `/i/a7Fk32` (DB Post ID 노출 방지)
- **공유**: 카카오톡 공유, 링크 복사, QR 코드, 인쇄
- **QR 코드**: 온라인 초대장 ↔ 종이 초대장 연결, 인쇄물에도 QR 포함

---

## 20~22. 장소 · 네이버 지도/길찾기 · 전화/문자

- 장소명 + 주소 표시 및 `[네이버 지도]`, `[길찾기]`, `[주소 복사]` 제공
- 연락처 항목에 `[전화하기]`(`tel:`), `[문자 보내기]`(`sms:`) 제공

---

## 23~27. RSVP (참석 여부 · 응답 마감 · DB · 참석 현황 · 목록 및 CSV)

- 제작자가 RSVP ON/OFF 및 응답 마감일(`2026-10-10`) 설정 가능 (마감 후 `"참석 여부 응답이 마감되었습니다."` 표시)
- 별도 테이블: `wp_mm_invitation_rsvp` (`id`, `invitation_id`, `name`, `status`, `guest_count`, `message`, `created_at`, `updated_at`)
- 참석 현황 집계: 전체 응답, 참석, 불참, 미정, 예상 참석 인원
- 참석자 목록: 검색, 참석/불참/미정 필터, **CSV 다운로드**

---

## 28~29. 방명록 (`wp_mm_invitation_guestbook`)

- 제작자가 방명록 ON/OFF 및 메시지 관리(숨김/삭제) 가능, 스팸 방지 적용
- 별도 테이블: `wp_mm_invitation_guestbook` (`id`, `invitation_id`, `name`, `message`, `status`, `created_at`)

---

## 30~34. D-Day · 일정 저장 · 공개 범위 · NOINDEX · 공개기간

- **D-Day**: 행사 전 `D-15`, 당일 `D-DAY`, 행사 후 `행사가 종료되었습니다.`
- **일정 저장**: `[내 일정에 저장]` (`.ics` 다운로드 및 캘린더 연동)
- **공개 범위**: 링크를 아는 사람만(기본 권장), 비밀번호 보호, 비공개
- **검색엔진 노출**: 개인정보 보호를 위해 기본값 `NOINDEX` 적용
- **공개기간**: 계속 공개, 행사 후 30일, 행사 후 90일, 직접 설정 (기간 종료 후 삭제하지 않고 공개만 종료하여 계정에 보관)

---

## 35~38. 내 초대장 · 초대장 카드 · 복제 · 인쇄

- **내 초대장 탭**: 진행 예정, 지난 행사, 임시저장, 보관
- **초대장 카드**: 대표사진, 제목, 행사일, 상태, RSVP 현황 및 9개 기능(보기, 관리, 공유, 수정, 복제, 인쇄, QR, 보관, 삭제)
- **복제**: `{제목} - 복사본`으로 디자인·항목·사진·기본 설정 복제 (RSVP·방명록은 복제 제외)
- **인쇄**: Print CSS 기반 `A4`, `A5`, `엽서(Postcard)` 인쇄 및 PDF 저장 지원 (QR 포함)

---

## 39~44. Template (FREE/PREMIUM) · 관리자 · 대시보드 · 권한 · 개인정보/보안

- **초기 Template 5종**: `Simple`, `Classic`, `Modern`, `Flower`, `Nature` (`FREE` / `PREMIUM` 구분 지원)
- **WordPress 관리자 메뉴**: `초대장` → 대시보드, 전체 초대장, 카테고리, 템플릿, RSVP, 방명록, 설정
- **보안 필수 적용**: 사용자 소유권 확인, Capability 검증, Nonce, Sanitization, Escaping, CSRF/XSS/SQLi 방지, 파일 업로드 검증, RSVP/Guestbook 스팸 방지

---

## 47. 플러그인 구조

```
manmulro-invitation/
├── manmulro-invitation.php
├── includes/
│   ├── post-types.php
│   ├── taxonomies.php
│   ├── invitation.php
│   ├── fields.php
│   ├── gallery.php
│   ├── rsvp.php
│   ├── guestbook.php
│   ├── sharing.php
│   ├── qr.php
│   ├── print.php
│   └── security.php
├── templates/
│   ├── invitation-single.php
│   ├── invitation-editor.php
│   ├── my-invitations.php
│   ├── print.php
│   └── themes/
│       ├── simple/
│       ├── classic/
│       ├── modern/
│       ├── flower/
│       └── nature/
├── admin/
└── assets/
    ├── css/
    ├── js/
    └── images/
```

---

## 50. 최종 제품 철학

`Category + Template + Flexible Fields` 조합만으로 코드 수정 없이 세상의 모든 만남(예: 낚시모임 — 출조일, 집결시간, 집결장소, 선박, 출조비, 준비물)을 위한 초대장을 만들 수 있는 범용 플랫폼이다.
