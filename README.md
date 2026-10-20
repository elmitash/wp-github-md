# WP GitHub MD

워드프레스용 최신 초경량 순정 GitHub 마크다운 에디터 & 뷰어 플러그인

구형 플러그인(`WP Githuber MD` 등)의 레거시 부채와 보안 취약점(CVE-2023-47846 임의 파일 업로드, CVE-2024-52422 저장형 XSS)을 완전히 해결하고, PHP 8.2+ 및 최신 워드프레스 코어 API를 준수하여 설계된 모던 플러그인입니다.

---

## ✨ 주요 기능 및 특징

1. **테마 간섭 100% 차단 GitHub 뷰어 (`github-markdown-css`)**
   - 워드프레스 테마가 무엇이든 본문 영역은 공식 GitHub 마크다운 스타일과 동일하게 격리 렌더링됩니다.
   - 모바일 퍼스트 반응형 레이아웃 및 다크 모드 감응(`prefers-color-scheme: dark`)을 지원합니다.
2. **실시간 좌우 분할 뷰 에디터 (EasyMDE)**
   - 글 작성 화면에서 좌측 마크다운 입력 / 우측 실시간 GitHub 렌더링 프리뷰를 지원합니다.
   - 스크롤 동기화 및 편리한 GFM 단축키, 마크다운 툴바를 제공합니다.
3. **워드프레스 순정 미디어 라이브러리 연동 클립보드 이미지 업로드**
   - 스크린샷 붙여넣기(`Ctrl + V`) 또는 파일 드래그 시 워드프레스 순정 `wp_handle_upload()` API를 통해 미디어 라이브러리에 안전하게 등록됩니다.
   - 업로드 완료 즉시 `![filename](url)` 형태로 마크다운에 자동 삽입됩니다.
4. **코드 블록 문법 강조 (`highlight.js`) & 원클릭 복사 버튼**
   - 모든 언어별 코드 블록에 문법 강조가 적용됩니다.
   - 코드 블록 우측 상단에 GitHub Octicons 스타일의 원클릭 [Copy / Copied!] 복사 버튼이 자동 생성됩니다.
5. **무손실 마크다운 저장 구조**
   - 원본 마크다운은 워드프레스 코어 표준 필드인 `post_content_filtered`에 보존됩니다.
   - `post_content`에는 `wp_kses_post()`로 XSS를 정제한 순수 HTML을 저장하므로 프론트엔드 로딩 속도가 극도로 빠릅니다.

---

## 🛠 기술 스택

- **Backend**: PHP 8.2+, WordPress Plugin API, Parsedown (GFM 파서)
- **Editor**: EasyMDE (CodeMirror 기반 마크다운 에디터)
- **Styling**: `github-markdown-css` v5+
- **Syntax Highlighting**: `highlight.js` (GitHub Theme)
- **Security**: Nonce 검증, `current_user_can('upload_files')`, `wp_check_filetype_and_ext()`, `wp_kses_post()`

---

## 🚀 설치 및 활성화

1. 워드프레스 관리자 패널 ➡️ `플러그인` ➡️ `새로 추가` ➡️ `플러그인 업로드`
2. `wp-github-md.zip` 업로드 후 활성화
3. 또는 워드프레스 서버의 `wp-content/plugins/` 디렉터리에 `wp-github-md` 폴더를 복사한 후 플러그인 메뉴에서 활성화
