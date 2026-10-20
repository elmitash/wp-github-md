<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 플러그인 메인 코어 클래스
 */
final class WP_GitHub_MD_Core
{
    private static ?WP_GitHub_MD_Core $instance = null;

    /**
     * 싱글톤 인스턴스 획득
     */
    public static function get_instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 생성자: 훅 등록
     */
    private function __construct()
    {
        // 관리자 에디터 제어 초기화
        WP_GitHub_MD_Editor::init();

        // 미디어 라이브러리 업로드 핸들러 초기화
        WP_GitHub_MD_Media::init();

        // 포스트 데이터 DB 삽입 전 가로채기 (마크다운 원문 보존 + HTML 파싱)
        add_filter('wp_insert_post_data', [$this, 'filter_insert_post_data'], 10, 2);

        // 프론트엔드 에셋 및 본문 래퍼 처리
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_filter('the_content', [$this, 'wrap_markdown_content'], 12);
    }

    /**
     * 포스트 저장 시 원본 마크다운과 파싱된 HTML 분리 저장
     *
     * @param array<string, mixed> $data    정제된 포스트 데이터
     * @param array<string, mixed> $postarr 전송된 원시 포스트 데이터
     * @return array<string, mixed>
     */
    public function filter_insert_post_data(array $data, array $postarr): array
    {
        // 자동 임시저장(Autosave) 또는 휴지통 이동 등은 제외
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $data;
        }

        // 대상 포스트 타입 검증
        $post_type = $data['post_type'] ?? '';
        if (!in_array($post_type, WP_GitHub_MD_Editor::get_supported_post_types(), true)) {
            return $data;
        }

        // 에디터로부터 전송된 마크다운 원문 추출
        $raw_markdown = $postarr['content'] ?? '';

        if (!empty($raw_markdown)) {
            // 1. post_content_filtered 컬럼에 마크다운 원본 보존
            $data['post_content_filtered'] = $raw_markdown;

            // 2. 서버 사이드에서 마크다운을 안전한 HTML로 파싱하여 post_content에 저장
            $parsed_html = WP_GitHub_MD_Parser::parse($raw_markdown, true);
            $data['post_content'] = $parsed_html;
        }

        return $data;
    }

    /**
     * 프론트엔드 에셋 로드 (GitHub Markdown CSS + highlight.js + 복사 버튼 스크립트)
     */
    public function enqueue_frontend_assets(): void
    {
        if (!is_singular() && !is_preview()) {
            return;
        }

        global $post;
        if (!$post || !in_array($post->post_type, WP_GitHub_MD_Editor::get_supported_post_types(), true)) {
            return;
        }

        // 마크다운 원본(post_content_filtered)이 존재하는 글에만 최적화하여 로드
        if (empty($post->post_content_filtered)) {
            return;
        }

        // 1. GitHub 공식 마크다운 CSS
        wp_enqueue_style(
            'wp-github-md-frontend-css',
            'https://cdn.jsdelivr.net/npm/github-markdown-css@5.5.1/github-markdown.min.css',
            [],
            '5.5.1'
        );

        // 2. highlight.js 스타일 (GitHub 공식 라이트 테마)
        wp_enqueue_style(
            'wp-github-md-highlight-css',
            'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.10.0/styles/github.min.css',
            [],
            '11.10.0'
        );

        // 3. 플러그인 커스텀 프론트엔드 CSS (복사 버튼 및 테마 격리 스타일)
        wp_enqueue_style(
            'wp-github-md-custom-css',
            WP_GITHUB_MD_URL . 'assets/css/frontend.css',
            ['wp-github-md-frontend-css', 'wp-github-md-highlight-css'],
            WP_GITHUB_MD_VERSION
        );

        // 4. highlight.js 코어 스크립트
        wp_enqueue_script(
            'wp-github-md-highlight-js',
            'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.10.0/highlight.min.js',
            [],
            '11.10.0',
            true
        );

        // 5. 플러그인 프론트엔드 JS (하이라이팅 트리거 및 원클릭 복사 버튼 생성)
        wp_enqueue_script(
            'wp-github-md-frontend-js',
            WP_GITHUB_MD_URL . 'assets/js/frontend.js',
            ['wp-github-md-highlight-js'],
            WP_GITHUB_MD_VERSION,
            true
        );

        // 6. 스크립트 번역 및 세팅 전달
        wp_localize_script('wp-github-md-frontend-js', 'wpGitHubMdFrontendSettings', [
            'copyText'   => __('Copy', 'wp-github-md'),
            'copiedText' => __('Copied!', 'wp-github-md'),
        ]);
    }

    /**
     * 프론트엔드 본문을 .markdown-body 래퍼로 감싸 테마 간섭 차단
     *
     * @param string $content 포스트 본문 HTML
     * @return string
     */
    public function wrap_markdown_content(string $content): string
    {
        if (is_singular() || is_preview()) {
            global $post;
            // post_content_filtered(마크다운 원본)가 존재하는 글인 경우에만 격리 래퍼 적용
            if ($post && !empty($post->post_content_filtered)) {
                return '<div class="markdown-body wp-github-md-container">' . $content . '</div>';
            }
        }
        return $content;
    }
}
