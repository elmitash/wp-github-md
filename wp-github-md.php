<?php
/**
 * Plugin Name:       WP GitHub MD
 * Plugin URI:        https://github.com/elmitash/wp-github-md
 * Description:       초경량, 고성능, 제로 취약점 워드프레스 순정 GitHub 마크다운 에디터 & 뷰어 플러그인
 * Version:           1.0.4
 * Requires at least: 6.0
 * Requires PHP:      8.2
 * Author:            elmitash
 * Text Domain:       wp-github-md
 * Domain Path:       /languages
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// 플러그인 전역 상수 정의
define('WP_GITHUB_MD_VERSION', '1.0.4');
define('WP_GITHUB_MD_FILE', __FILE__);
define('WP_GITHUB_MD_PATH', plugin_dir_path(__FILE__));
define('WP_GITHUB_MD_URL', plugin_dir_url(__FILE__));

// 핵심 클래스 파일 로드
require_once WP_GITHUB_MD_PATH . 'includes/lib/Parsedown.php';
require_once WP_GITHUB_MD_PATH . 'includes/class-parser.php';
require_once WP_GITHUB_MD_PATH . 'includes/class-media.php';
require_once WP_GITHUB_MD_PATH . 'includes/class-editor.php';
require_once WP_GITHUB_MD_PATH . 'includes/class-core.php';

/**
 * 플러그인 부트스트랩
 */
function wp_github_md_init(): void
{
    // PHP 버전 사전 검증
    if (version_compare(PHP_VERSION, '8.2.0', '<')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>' .
                esc_html__('WP GitHub MD 플러그인은 PHP 8.2 이상 버전이 필요합니다.', 'wp-github-md') .
                '</p></div>';
        });
        return;
    }

    // 싱글톤 인스턴스 실행
    WP_GitHub_MD_Core::get_instance();
}

add_action('plugins_loaded', 'wp_github_md_init');
