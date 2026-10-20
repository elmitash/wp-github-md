<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 워드프레스 순정 미디어 라이브러리 연동 이미지 업로더
 * 구형 플러그인의 파일 업로드 취약점(CVE-2023-47846)을 원천 차단
 */
final class WP_GitHub_MD_Media
{
    /**
     * 훅 등록
     */
    public static function init(): void
    {
        add_action('wp_ajax_wp_github_md_upload_image', [self::class, 'handle_upload']);
    }

    /**
     * Ajax 이미지 업로드 처리
     */
    public static function handle_upload(): void
    {
        // 1. CSRF 보안 토큰 검증
        check_ajax_referer('wp_github_md_upload_nonce', 'nonce');

        // 2. 파일 업로드 권한 검증
        if (!current_user_can('upload_files')) {
            wp_send_json_error([
                'message' => __('이미지를 업로드할 권한이 없습니다.', 'wp-github-md'),
            ], 403);
        }

        // 3. 파일 수신 여부 확인
        if (empty($_FILES['image']) && empty($_FILES['file'])) {
            wp_send_json_error([
                'message' => __('업로드할 이미지 파일이 전송되지 않았습니다.', 'wp-github-md'),
            ], 400);
        }

        $file = $_FILES['image'] ?? $_FILES['file'];

        // 4. 업로드 에러 검사
        if (!empty($file['error'])) {
            wp_send_json_error([
                'message' => sprintf(__('파일 업로드 오류: %s', 'wp-github-md'), (string) $file['error']),
            ], 400);
        }

        // 5. 허용된 이미지 MIME 타입 엄격 검증
        $allowed_mimes = [
            'jpg|jpeg|jpe' => 'image/jpeg',
            'gif'          => 'image/gif',
            'png'          => 'image/png',
            'bmp'          => 'image/bmp',
            'webp'         => 'image/webp',
            'avif'         => 'image/avif',
            'svg'          => 'image/svg+xml',
        ];

        $file_info = wp_check_filetype_and_ext($file['tmp_name'], $file['name'], $allowed_mimes);
        if (!$file_info['ext'] || !$file_info['type']) {
            wp_send_json_error([
                'message' => __('허용되지 않는 파일 형식이거나 손상된 이미지 파일입니다.', 'wp-github-md'),
            ], 400);
        }

        // 워드프레스 미디어 관리 코어 파일 로드
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        // 6. 워드프레스 순정 API로 업로드 처리
        $upload_overrides = [
            'test_form' => false,
            'mimes'     => $allowed_mimes,
        ];

        $upload = wp_handle_upload($file, $upload_overrides);

        if (isset($upload['error']) && $upload['error']) {
            wp_send_json_error([
                'message' => $upload['error'],
            ], 500);
        }

        // 7. 워드프레스 미디어 라이브러리(Attachment)에 정식 등록
        $filename   = $upload['file'];
        $attachment = [
            'post_mime_type' => $upload['type'],
            'post_title'     => sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)),
            'post_content'   => '',
            'post_status'    => 'inherit',
        ];

        $attachment_id = wp_insert_attachment($attachment, $filename);

        if (!is_wp_error($attachment_id)) {
            $metadata = wp_generate_attachment_metadata($attachment_id, $filename);
            wp_update_attachment_metadata($attachment_id, $metadata);
        }

        // 8. 마크다운 에디터용 이미지 URL 반환
        wp_send_json_success([
            'url'           => $upload['url'],
            'attachment_id' => $attachment_id,
        ]);
    }
}
