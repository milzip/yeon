# Manmulro Invitation (`manmulro-invitation`)

**만물로 범용 초대장 플랫폼 V1 WordPress 플러그인**

- **Version**: `1.0.0`
- **Plugin Slug**: `manmulro-invitation`
- **Shortcodes**:
  - `[manmulro_invitation_editor]` — 초대장 제작/수정 편집기 (PC 좌측 편집 / 우측 모바일 실시간 미리보기, 모바일 탭 전환)
  - `[manmulro_my_invitations]` — 내 초대장 관리 대시보드 (진행 예정 / 지난 행사 / 임시저장 / 보관 탭, 카드 액션, RSVP·방명록 관리, CSV 다운로드)
- **Public Short URL**:
  - `/i/{code}` (예: `/i/a7Fk32`) — DB Post ID를 노출하지 않는 고유 초대장 URL
  - `/i/{code}?print=1&paper=a4|a5|postcard` — QR 코드가 포함된 A4 / A5 / 엽서 인쇄 및 PDF 저장 화면
