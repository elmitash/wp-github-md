<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 관리자 글 작성 화면 에디터 제어 클래스
 */
final class WP_GitHub_MD_Editor
{
    /**
     * 마크다운 에디터를 적용할 포스트 타입 목록
     *
     * @return array<string>
     */
    public static function get_supported_post_types(): array
    {
        return apply_filters('wp_github_md_supported_post_types', ['post', 'page']);
    }

    /**
     * 이벤트 훅 등록
     */
    public static function init(): void
    {
        // 1. 구텐베르크 블록 에디터 비활성화 (순정 textarea 노출)
        add_filter('use_block_editor_for_post', [self::class, 'disable_gutenberg'], 10, 2);

        // 2. 클래식 에디터 TinyMCE 비활성화
        add_filter('user_can_richedit', [self::class, 'disable_tinymce']);

        // 3. 글 편집 시 마크다운 원문(post_content_filtered)을 에디터 폼에 로드
        add_filter('edit_post_content', [self::class, 'load_markdown_content'], 10, 2);

        // 4. 에디터 에셋(EasyMDE, GitHub CSS, 스크립트) Enqueue
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin_assets']);
    }

    /**
     * 지원하는 포스트 타입에서 구텐베르크 블록 에디터 비활성화
     *
     * @param bool         $use_block_editor 현재 블록 에디터 사용 여부
     * @param \WP_Post|null $post            현재 포스트 객체
     * @return bool
     */
    public static function disable_gutenberg(bool $use_block_editor, ?\WP_Post $post): bool
    {
        if ($post && in_array($post->post_type, self::get_supported_post_types(), true)) {
            return false;
        }
        return $use_block_editor;
    }

    /**
     * 마크다운 작성 화면에서 TinyMCE 비활성화
     *
     * @param bool $wp_rich_edit
     * @return bool
     */
    public static function disable_tinymce(bool $wp_rich_edit): bool
    {
        global $post;
        if ($post && in_array($post->post_type, self::get_supported_post_types(), true)) {
            return false;
        }
        return $wp_rich_edit;
    }

    /**
     * 에디터 textarea에 마크다운 원문(post_content_filtered) 주입
     *
     * @param string $content 원본 content
     * @param int    $post_id 포스트 ID
     * @return string
     */
    public static function load_markdown_content(string $content, int $post_id): string
    {
        $post = get_post($post_id);
        if ($post && !empty($post->post_content_filtered)) {
            return $post->post_content_filtered;
        }
        return $content;
    }

    /**
     * 관리자 글 작성/수정 화면 전용 에셋 등록
     *
     * @param string $hook_suffix 현재 관리자 페이지 훅
     */
    public static function enqueue_admin_assets(string $hook_suffix): void
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $is_post_edit = in_array($hook_suffix, ['post.php', 'post-new.php'], true)
            || ($screen && in_array($screen->base, ['post', 'post-new'], true));

        if (!$is_post_edit) {
            return;
        }

        $post_type = $screen->post_type ?? (get_post_type() ?: 'post');
        if (!in_array($post_type, self::get_supported_post_types(), true)) {
            return;
        }

        // FontAwesome (EasyMDE 툴바 아이콘용)
        wp_enqueue_style(
            'wp-github-md-fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            [],
            '6.4.0'
        );

        // GitHub Markdown CSS (에디터 내 실시간 프리뷰 스타일링)
        wp_enqueue_style(
            'wp-github-md-github-css',
            'https://cdn.jsdelivr.net/npm/github-markdown-css@5.5.1/github-markdown.min.css',
            [],
            '5.5.1'
        );

        // EasyMDE Core CSS
        wp_enqueue_style(
            'wp-github-md-easymde-css',
            'https://cdn.jsdelivr.net/npm/easymde@2.18.0/dist/easymde.min.css',
            [],
            '2.18.0'
        );

        // 커스텀 관리자 에디터 CSS
        wp_enqueue_style(
            'wp-github-md-admin-css',
            WP_GITHUB_MD_URL . 'assets/css/admin-editor.css',
            ['wp-github-md-easymde-css', 'wp-github-md-github-css'],
            WP_GITHUB_MD_VERSION
        );

        // EasyMDE Core JS
        wp_enqueue_script(
            'wp-github-md-easymde-js',
            'https://cdn.jsdelivr.net/npm/easymde@2.18.0/dist/easymde.min.js',
            ['jquery'],
            '2.18.0',
            true
        );

        // 플러그인 전용 관리자 JS
        wp_enqueue_script(
            'wp-github-md-admin-js',
            WP_GITHUB_MD_URL . 'assets/js/admin-editor.js',
            ['jquery', 'wp-github-md-easymde-js'],
            WP_GITHUB_MD_VERSION,
            true
        );

        // JS 로컬라이제이션 및 설정 데이터 전달
        wp_localize_script('wp-github-md-admin-js', 'wpGitHubMdSettings', [
            'placeholder'        => __('마크다운으로 글을 작성하세요...', 'wp-github-md'),
            'previewClass'       => 'markdown-body',
            'ajaxUrl'            => admin_url('admin-ajax.php'),
            'uploadNonce'        => wp_create_nonce('wp_github_md_upload_nonce'),
            'uploadingText'      => __('이미지 업로드 중...', 'wp-github-md'),
            'uploadErrorText'    => __('이미지 업로드에 실패했습니다.', 'wp-github-md'),
        ]);
    }
}
