<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 마크다운 파서 서비스 클래스
 */
final class WP_GitHub_MD_Parser
{
    private static ?Parsedown $parsedown = null;

    /**
     * Parsedown 인스턴스 획득 (Lazy Loading)
     */
    private static function get_parsedown(): Parsedown
    {
        if (self::$parsedown === null) {
            self::$parsedown = new Parsedown();
            // GitHub 마크다운 표준 줄바꿈 규칙
            self::$parsedown->setBreaksEnabled(true);
            // 워드프레스 표준 wp_kses_post()를 통해 정교한 XSS 정제 수행
            self::$parsedown->setSafeMode(false);
        }
        return self::$parsedown;
    }

    /**
     * 마크다운 텍스트를 안전한 HTML로 파싱
     *
     * @param string $markdown 원본 마크다운 문자열
     * @param bool   $sanitize wp_kses_post() 필터링 적용 여부
     * @return string 파싱된 HTML 문자열
     */
    public static function parse(string $markdown, bool $sanitize = true): string
    {
        if (empty(trim($markdown))) {
            return '';
        }

        $parser = self::get_parsedown();
        $html = $parser->text($markdown);

        // GitHub 스타일 체크박스 태스크 리스트 변환 (- [ ] / - [x])
        $html = self::parse_task_lists($html);

        // 플러그인 확장 필터 제공
        $html = (string) apply_filters('wp_github_md_parsed_html', $html, $markdown);

        if ($sanitize) {
            // 저장형 XSS 방지: 워드프레스 표준 포스트 태그 정제 (input 체크박스 허용)
            $allowed_html = wp_kses_allowed_html('post');
            $allowed_html['input'] = [
                'type' => true,
                'class' => true,
                'checked' => true,
                'disabled' => true,
            ];
            $html = wp_kses($html, $allowed_html);
        }

        return $html;
    }

    /**
     * GFM 체크박스 태스크 리스트 파싱
     *
     * @param string $html
     * @return string
     */
    private static function parse_task_lists(string $html): string
    {
        $patterns = [
            '/(<li>\s*)\[ \]/i' => '$1<input type="checkbox" class="task-list-item-checkbox" disabled="disabled">',
            '/(<li>\s*)\[x\]/i' => '$1<input type="checkbox" class="task-list-item-checkbox" checked="checked" disabled="disabled">',
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $html) ?? $html;
    }
}
