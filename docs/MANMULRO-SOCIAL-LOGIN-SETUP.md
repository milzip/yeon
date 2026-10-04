# 만물로 소셜 로그인 설정 안내서

- **대상 플러그인:** `manmulro-social-login`
- **설정할 로그인:** 네이버 · 카카오 · Google
- **기준:** WordPress 웹사이트에 플러그인을 설치하고, 각 사업자 개발자 콘솔에서 OAuth 앱을 등록하는 방법

> 개발자 콘솔의 메뉴 이름이나 위치는 사업자 업데이트에 따라 바뀔 수 있습니다. 화면 구성이 다르면 아래 공식 문서 링크를 함께 확인하세요. 이 안내서는 만물로 플러그인의 현재 콜백 주소와 필요한 사용자 정보에 맞춰 작성했습니다.

---

## 1. 설정 전에 준비할 것

- 운영 중인 WordPress 사이트의 **대표 도메인**을 정합니다. 예: `https://example.com` 또는 `https://www.example.com`
- 운영 사이트는 HTTPS(SSL)를 사용합니다. WordPress **일반 설정의 사이트 주소(Site Address)**가 사용자가 접속하는 대표 URL이어야 합니다. 플러그인은 이 사이트 주소를 기준으로 기본 콜백을 생성하므로, WordPress 주소(설치 경로)가 별도로 설정된 경우에도 관리자 화면에 표시되는 콜백 주소를 복사하세요.
- `www` 포함 여부, HTTPS, 도메인, 콜백 주소의 경로와 끝의 `/`는 OAuth 사업자 콘솔에 등록한 값과 실제 요청 값이 정확히 같아야 합니다.
- WordPress 관리자 **플러그인 → 새 플러그인 추가 → 플러그인 업로드**에서 `manmulro-social-login.zip`을 업로드해 설치한 뒤 활성화합니다. 활성화 시 기본 로그인 페이지가 자동 생성됩니다. 해당 페이지에 `[manmulro_login]`이 들어 있으면 `/login/`에서 소셜 로그인 버튼을 확인할 수 있습니다. 기존에 `/login/` 페이지가 이미 있었다면 플러그인이 내용을 덮어쓰지 않으므로, 페이지에 `[manmulro_login]` 숏코드가 있는지 확인합니다.

### 콜백(리디렉션) 주소 정하기 — 가장 중요한 단계

플러그인은 기본적으로 아래 주소를 사용합니다. `example.com`을 **실제 WordPress 대표 도메인**으로 바꾸세요.

| 제공자 | 기본 콜백 주소 |
|---|---|
| 네이버 | `https://example.com/?mm_sl_action=callback&provider=naver` |
| 카카오 | `https://example.com/?mm_sl_action=callback&provider=kakao` |
| Google | `https://example.com/?mm_sl_action=callback&provider=google` |

WordPress 관리자 **설정 → 만물로 로그인** 페이지에서 각 제공자의 `Callback / Redirect URI` 입력란을 비워 두면 위 기본 주소가 사용됩니다. 해당 입력란에 이미 값이 저장돼 있다면 그 값이 기본 주소 대신 사용되므로, 콘솔에도 그 주소를 그대로 등록해야 합니다. WordPress가 하위 경로에 설치됐거나 주소 설정이 다른 경우에는 예시를 그대로 쓰지 말고, 관리자 화면에 표시되는 기본 콜백 주소를 복사하세요.

> **주소를 임의로 만들거나 조금 다르게 입력하지 마세요.** 로그인 플러그인 설정과 제공자 콘솔의 주소가 한 글자라도 다르면 로그인에 실패할 수 있습니다. 콘솔 입력란에 `code`나 `state`는 직접 붙이지 않습니다.

#### 선택 사항: 보기 쉬운 경로 사용

쿼리 문자열이 없는 주소를 쓰고 싶다면 아래 경로를 이용할 수 있습니다.

- `https://example.com/mm-auth/naver/callback`
- `https://example.com/mm-auth/kakao/callback`
- `https://example.com/mm-auth/google/callback`

이 방식을 선택할 때는 **WordPress 관리자 → 설정 → 만물로 로그인**의 해당 `Callback / Redirect URI` 입력란에 같은 주소를 저장한 뒤, 그 주소를 제공자 콘솔에도 등록합니다. 경로 형식의 퍼머링크가 정상 동작해야 합니다. 처음 설정했거나 콜백 경로에서 404가 나면 **설정 → 고유주소**로 이동해 저장 버튼을 한 번 눌러 규칙을 새로고침하세요. 처음 설정하는 경우에는 우선 표의 기본 주소를 사용하는 것이 간단합니다.

---

## 2. 네이버 로그인 설정

### 2-1. 네이버 개발자센터에서 애플리케이션 만들기

1. [네이버 개발자센터](https://developers.naver.com/apps/)에 네이버 계정으로 로그인합니다.
2. **Application → 애플리케이션 등록**을 선택합니다.
3. 애플리케이션 이름에 실제 서비스/브랜드를 식별할 수 있는 이름을 입력합니다.
4. **사용 API**에서 **네이버 로그인**을 선택합니다.
5. 로그인 오픈 API의 **서비스 환경**에서 **PC 웹**을 선택합니다. 반응형 WordPress 사이트라면 보통 PC 웹에서 충분합니다. 콘솔에 모바일 웹 환경도 별도로 설정한다면 같은 사이트와 콜백 주소를 그 환경에도 등록합니다.
6. **서비스 URL**에는 사이트의 대표 주소만 입력합니다. 예: `https://example.com` (로그인 페이지 주소가 아니라 도메인의 홈 주소)
7. **네이버 로그인 Callback URL**에는 위 표의 네이버 콜백 주소를 그대로 입력합니다.
8. 제공 정보/권한에서 실제 필요한 항목만 선택합니다.
   - **이용자 식별자(ID):** 네이버가 제공하는 고유 ID로 로그인 계정 확인에 사용됩니다.
   - **별명(닉네임):** 로그인한 회원의 표시 이름으로 사용됩니다.
   - **이메일 주소:** WordPress 회원 이메일로 받을 필요가 있을 때 요청합니다.
   - **프로필 사진:** 프로필 사진을 표시하려는 경우에만 요청합니다.
9. 서비스 로고 등 앱 정보를 입력하고 저장합니다. 동의/추가 정보는 개인정보 처리방침과 실제 서비스 목적에 맞춰 최소한으로 선택하세요.
10. 저장 후 애플리케이션의 **Client ID**와 **Client Secret**을 확인합니다. Client Secret은 공개하지 마세요.

### 2-2. 네이버 로그인의 개발/운영 상태 확인

처음 등록한 네이버 로그인 앱은 보통 **개발 중** 상태입니다. 이 상태에서는 등록된 관리자/테스터 ID만 사용할 수 있습니다(테스터 등록 한도는 네이버 정책 및 콘솔 안내를 확인하세요). 개발자 계정으로 먼저 테스트하고, 일반 사용자에게 공개하기 전에 애플리케이션의 **네이버 로그인 검수 상태**에서 사전 검수를 신청해 승인을 받으세요. 검수 시 로그인 동작 과정과 각 추가 정보가 서비스에서 필요한 이유를 요청받을 수 있습니다.

### 2-3. WordPress에 네이버 정보 입력하기

WordPress 관리자에서 **설정 → 만물로 로그인**으로 이동합니다.

- **Naver Client ID:** 네이버 애플리케이션의 Client ID
- **Naver Client Secret:** 네이버 애플리케이션의 Client Secret
- **Callback / Redirect URI:** 기본 주소를 쓴다면 비워 둡니다. 사용자 지정 콜백을 쓴다면 네이버 콘솔에 등록한 주소와 동일하게 입력합니다.
- **Naver Login 사용(ON):** 체크합니다.
- 신규 회원이 소셜 로그인으로 가입해야 한다면 **신규 회원가입 허용**도 켭니다.

아래쪽의 **설정 저장**을 누릅니다.

---

## 3. 카카오 로그인 설정

### 3-1. 카카오디벨로퍼스에서 앱 만들기

1. [카카오디벨로퍼스](https://developers.kakao.com/)에 카카오 계정으로 로그인합니다.
2. **내 애플리케이션 → 애플리케이션 추가하기**를 선택해 서비스 이름과 사업자 정보를 입력합니다.
3. 생성한 앱에서 **앱 → 플랫폼 키**(콘솔에 따라 **앱 키**로 표시)로 이동해 **REST API 키**를 확인합니다.
   - 만물로 WordPress 플러그인의 Client ID에는 **REST API 키**를 사용합니다.
   - JavaScript 키, 네이티브 앱 키, Admin 키를 입력하지 마세요. 이 플러그인은 서버에서 처리하는 REST API OAuth 방식입니다.
4. **제품 설정 → 카카오 로그인**으로 이동해 카카오 로그인을 **ON/활성화**합니다.
5. 앱 키 설정의 **REST API 키 → Redirect URI** 항목(또는 카카오 로그인 설정의 Redirect URI 메뉴)에 위 표의 카카오 콜백 주소를 등록하고 저장합니다.
6. 카카오 로그인 **동의항목**에서 필요한 정보만 설정합니다.
   - **닉네임:** 회원 표시 이름에 사용합니다.
   - **프로필 사진:** 표시하려는 경우 설정합니다.
   - **카카오계정(이메일):** WordPress 회원 이메일로 받을 필요가 있으면 설정합니다. 이용자의 동의와 카카오 정책에 따라 이메일이 제공되지 않을 수 있습니다.
   - 플러그인은 친구 목록, 메시지 전송 등 소셜 로그인과 무관한 권한을 요청하지 않습니다. 추가 권한은 설정하지 마세요.
7. REST API 키의 **Client Secret** 설정을 사용(ON)으로 켰다면 생성된 Secret 코드를 복사합니다. Client Secret을 사용하지 않도록 둔 경우 WordPress의 Client Secret 입력란은 비워 둘 수 있습니다. 콘솔에서 Secret 사용을 켰다면 WordPress에도 같은 코드를 반드시 입력해야 합니다.
8. 앱 정보, 서비스 도메인/사업자 정보 및 필요한 개인정보 처리방침 정보를 입력하고 저장합니다.

> 카카오 콘솔의 메뉴 이름은 개편에 따라 달라질 수 있습니다. 현재 공식 설정 문서의 **앱 키/리다이렉트 URI**와 **카카오 로그인 설정**을 확인하세요.

### 3-2. WordPress에 카카오 정보 입력하기

**설정 → 만물로 로그인**에서 다음과 같이 입력합니다.

- **Kakao Client ID:** 카카오 앱의 **REST API 키**
- **Kakao Client Secret:** 콘솔에서 Client Secret 기능을 켰다면 해당 코드. 기능을 사용하지 않으면 비워 둡니다.
- **Callback / Redirect URI:** 기본 주소를 쓰면 비워 둡니다. 사용자 지정 콜백은 카카오 콘솔 주소와 똑같이 입력합니다.
- **Kakao Login 사용(ON):** 체크합니다.
- 신규 회원 가입도 허용하려면 **신규 회원가입 허용**을 켭니다.

**설정 저장**을 누릅니다.

---

## 4. Google 로그인 설정

### 4-1. Google Cloud에서 OAuth 앱 만들기

1. [Google Cloud Console](https://console.cloud.google.com/)에 로그인하고 프로젝트를 새로 만들거나 기존 프로젝트를 선택합니다.
2. **Google Auth Platform**에서 앱 정보를 설정합니다. 콘솔 화면이 구버전이면 **API 및 서비스 → OAuth 동의 화면** 메뉴를 찾습니다.
   - **앱 이름**, **사용자 지원 이메일**, 개발자 연락처를 입력합니다.
   - 홈페이지와 개인정보처리방침 URL을 입력하고, Google이 요청하면 도메인을 인증/승인 도메인에 등록합니다.
3. **Audience(대상)**에서 사용자 유형을 선택합니다.
   - 일반 고객을 포함한 공개 서비스: 보통 **External(외부)**
   - 특정 Google Workspace 조직 내부에서만 쓰는 서비스: 해당 조직에서 허용되는 경우 **Internal(내부)**
4. 개발 단계에서 테스트 사용자 등록을 요구하면 **Audience → Test users**에 로그인 테스트에 사용할 Google 계정을 추가합니다.
5. **Data Access(데이터 액세스)**에는 로그인에 필요한 기본 사용자 정보만 둡니다. 만물로 플러그인은 `openid`, `email`, `profile` 범위로 로그인하며 Gmail, Drive 등 추가 Google API 권한은 요청하지 않습니다. 추가 범위를 불필요하게 추가하지 마세요.
6. **Clients(클라이언트) → Create client(클라이언트 만들기)**를 선택합니다.
7. 애플리케이션 유형에서 **Web application(웹 애플리케이션)**을 선택하고 이름을 입력합니다.
8. **Authorized redirect URIs(승인된 리디렉션 URI)**에 위 표의 Google 콜백 주소를 그대로 추가합니다.
   - 콜백은 프로토콜(`https`), 도메인, 경로, 쿼리 문자열, 끝의 `/`까지 일치해야 합니다.
   - **Authorized JavaScript origins**는 이 플러그인의 서버 처리 방식에서 필수 항목이 아닙니다. 콘솔에서 별도로 요구하거나 다른 프론트엔드 로그인을 함께 구현하는 경우에만 `https://example.com`처럼 사이트 origin을 추가합니다.
9. 만들기를 완료하고 **Client ID**와 **Client Secret**을 확인합니다. 둘 다 WordPress 설정에 입력하며, 특히 Client Secret은 외부에 공개하지 마세요.
10. 앱이 테스트 상태이면 콘솔에 표시되는 접근/테스트 사용자 규칙을 따릅니다. 공개 서비스로 전환할 때는 Audience/Publishing status를 확인하고, Google이 요구하는 브랜드 검증 또는 앱 게시 절차를 완료합니다. 이 플러그인은 기본 로그인 정보만 요청하므로 민감한 Google API 권한을 추가하지 않는 것이 중요합니다.

> Google 로그인만을 위해 Gmail/Drive API를 켤 필요는 없습니다. 다른 Google API를 추가하지 않는 한 `openid`, `email`, `profile`만 사용합니다.

### 4-2. WordPress에 Google 정보 입력하기

**설정 → 만물로 로그인**에서 다음과 같이 입력합니다.

- **Google Client ID:** Google Cloud에서 만든 웹 애플리케이션 OAuth Client ID
- **Google Client Secret:** 같은 OAuth Client의 Client Secret
- **Callback / Redirect URI:** 기본 주소를 쓰면 비워 둡니다. 사용자 지정 콜백을 쓴다면 Google Cloud의 승인된 리디렉션 URI와 동일하게 입력합니다.
- **Google Login 사용(ON):** 체크합니다.
- 신규 회원 가입도 허용하려면 **신규 회원가입 허용**을 켭니다.

**설정 저장**을 누릅니다.

---

## 5. WordPress에서 공통 설정 저장 및 로그인 테스트

1. 관리자 **설정 → 만물로 로그인**의 각 제공자 섹션에서 Client ID, 필요한 Client Secret, 콜백 주소를 확인합니다.
2. 사용할 제공자의 `사용(ON)`을 체크합니다.
3. 새 이용자가 처음 소셜 로그인할 수 있도록 **신규 회원가입 허용**을 켭니다. 이 설정이 꺼져 있으면 새 회원을 만들 수 없습니다.
4. **설정 저장**을 누릅니다.
5. 로그아웃한 상태 또는 시크릿 창에서 `https://example.com/login/`을 엽니다. 각 소셜 로그인 버튼을 눌러 실제 로그인을 확인합니다.
6. 네이버 앱이 개발 중이라면 관리자/테스터 계정으로 테스트합니다. 일반 이용자 공개는 네이버 사전 검수 승인 이후 확인합니다.

### 이메일 정보 제공 여부

네이버/카카오 계정에서 이메일 제공을 허용하지 않거나 해당 항목을 사용할 수 없어도 소셜 고유 ID로 로그인은 가능합니다. 이 경우 플러그인은 WordPress 회원 생성에 필요한 내부용 자리표시 이메일을 만들며, 해당 주소는 실제 회원 이메일이 아니므로 메일 수신에 사용할 수 없습니다. 이메일 연락이 서비스에 꼭 필요하다면 제공자 동의항목과 개인정보 처리방침에 그 목적을 명확히 하고, 실제 이메일이 제공되는지 테스트하세요.

### Client Secret을 WordPress에 안전하게 보관하기

운영 사이트에서는 `wp-config.php` 상수 또는 서버 환경변수를 사용하는 방법을 권장합니다. 상수/환경변수 값은 관리자 화면에 입력한 값보다 우선 적용됩니다. 파일을 공개 저장소에 올리거나 채팅/메일에 Secret을 복사하지 마세요.

```php
// wp-config.php 예시: 실제 발급값으로 교체하고 이 파일을 외부에 공개하지 마세요.
define( 'MM_SL_NAVER_CLIENT_ID', '네이버_CLIENT_ID' );
define( 'MM_SL_NAVER_CLIENT_SECRET', '네이버_CLIENT_SECRET' );

define( 'MM_SL_KAKAO_CLIENT_ID', '카카오_REST_API_KEY' );
define( 'MM_SL_KAKAO_CLIENT_SECRET', '카카오_CLIENT_SECRET' );

define( 'MM_SL_GOOGLE_CLIENT_ID', 'Google_CLIENT_ID' );
define( 'MM_SL_GOOGLE_CLIENT_SECRET', 'Google_CLIENT_SECRET' );
```

콜백 주소도 상수로 고정할 수 있습니다. 사용할 때는 제공자 콘솔과 WordPress 사이트에 등록된 주소를 동일하게 맞춥니다.

```php
define( 'MM_SL_NAVER_CALLBACK_URL', 'https://example.com/?mm_sl_action=callback&provider=naver' );
define( 'MM_SL_KAKAO_CALLBACK_URL', 'https://example.com/?mm_sl_action=callback&provider=kakao' );
define( 'MM_SL_GOOGLE_CALLBACK_URL', 'https://example.com/?mm_sl_action=callback&provider=google' );
```

상수나 환경변수에서 Client ID/Secret을 읽으면 해당 입력란은 관리자 설정에서 수정할 수 없도록 표시될 수 있습니다. 이는 정상 동작입니다.

---

## 6. 자주 발생하는 오류와 확인 방법

| 증상/오류 | 확인할 내용 |
|---|---|
| `redirect_uri_mismatch`, 콜백 URL 오류 | WordPress 설정의 콜백과 네이버/카카오/Google 콘솔의 주소를 다시 비교합니다. `https`, 도메인(`www` 포함 여부), 경로, 쿼리 문자열, 끝의 `/`까지 확인합니다. |
| 카카오 `KOE006` | 카카오 앱의 REST API 키에 등록한 Redirect URI와 플러그인이 보내는 주소가 같은지 확인합니다. |
| 카카오 `KOE101` 또는 Client 인증 오류 | JavaScript 키가 아닌 REST API 키를 Client ID에 넣었는지, Client Secret 사용 설정과 WordPress 입력값이 맞는지 확인합니다. |
| 네이버는 개발자만 되고 다른 사람은 로그인 불가 | 애플리케이션이 개발 중인지 확인합니다. 테스터를 추가하거나 일반 공개 전 사전 검수를 신청합니다. |
| 로그인 버튼을 눌러도 설정 오류가 나옴 | Client ID를 저장했는지, 해당 제공자 사용(ON)과 신규 회원가입 허용 여부를 확인합니다. |
| Google에서 앱 테스트/미확인 안내가 표시됨 | Google Auth Platform의 Audience 및 게시 상태를 확인하고, 테스트 계정 또는 Google에서 요구하는 게시/검증 절차를 확인합니다. |
| 이미 사용 중인 이메일이라는 안내 | 보안상 같은 이메일만으로 WordPress 계정을 자동 통합하지 않습니다. 기존 계정으로 로그인한 뒤 **마이페이지 → 로그인 관리**에서 소셜 계정을 연결합니다. |
| 콜백 페이지가 404 | 기본 콜백 URL을 사용하거나, 사용자 지정 `/mm-auth/.../callback`을 쓰는 경우 WordPress **설정 → 고유주소 → 저장**으로 rewrite 규칙을 갱신하고 서버가 WordPress 퍼머링크를 지원하는지 확인합니다. |

---

## 7. 공식 도움말

### 네이버
- [네이버 개발자센터 애플리케이션](https://developers.naver.com/apps/)
- [애플리케이션 등록 가이드](https://naver.github.io/naver-openapi-guide/appregister.html)
- [네이버 로그인 검수 가이드](https://developers.naver.com/docs/login/verify/verify.md)

### 카카오
- [카카오디벨로퍼스 콘솔](https://developers.kakao.com/console/app)
- [카카오 로그인 설정하기](https://developers.kakao.com/docs/ko/kakaologin/prerequisite)
- [앱 키와 리다이렉트 URI](https://developers.kakao.com/docs/ko/app-setting/app)
- [카카오 로그인 REST API](https://developers.kakao.com/docs/ko/kakaologin/rest-api)

### Google
- [Google Auth Platform 클라이언트 설정](https://console.cloud.google.com/auth/clients)
- [Google OAuth 웹 서버 애플리케이션 가이드](https://developers.google.com/identity/protocols/oauth2/web-server)
- [Google 로그인용 OAuth Client ID 설정](https://developers.google.com/identity/gsi/web/guides/get-google-api-clientid)
- [OAuth 앱 상태 및 검증 안내](https://developers.google.com/identity/protocols/oauth2/production-readiness/overview)
