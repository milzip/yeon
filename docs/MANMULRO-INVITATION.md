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

**목표**: "모든 만남을 위한 초대장"

지원 기능:
- 초대장 제작 / 저장 / 수정 / 복제
- 링크 공유 / QR 공유 / 카카오톡 공유 / 인쇄 (A4, A5, 엽서)
- RSVP 관리 (참석/불참/미정, 인원, 마감일, 필터, CSV 다운로드)
- 방명록 관리 (ON/OFF, 스팸 방지, 작성/숨김/삭제)
- 사진앨범 관리 (대표 이미지 1장 + 갤러리 최대 10장, 순서 변경, Lightbox)
- 장소 및 네이버 지도 / 길찾기 / 주소 복사
- D-Day 및 내 일정에 저장 (ICS / 캘린더 연동)
- 공개 범위 (링크를 아는 사람 / 비밀번호 보호 / 비공개), 공개기간 설정, NOINDEX 기본 적용

---

## 2. 핵심 개발 원칙

공통 엔진:
`Template + Flexible Fields / Blocks + Event Data + Design + Sharing + RSVP`

Event Data와 Template Design을 완전히 분리하여, 사용자가 템플릿(Simple, Classic, Modern, Flower, Nature)을 변경해도 입력한 내용은 그대로 유지된다.

---

## 3. 플러그인 구조

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
