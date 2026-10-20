/**
 * WP GitHub MD - Frontend Code Highlighting & One-Click Copy
 */
(function () {
    'use strict';

    // SVG 아이콘 정의 (GitHub Octicons 호환)
    const COPY_ICON_SVG = '<svg width="14" height="14" viewBox="0 0 16 16" version="1.1" aria-hidden="true"><path d="M0 6.75C0 5.784.784 5 1.75 5h1.5a.75.75 0 0 1 0 1.5h-1.5a.25.25 0 0 0-.25.25v7.5c0 .138.112.25.25.25h7.5a.25.25 0 0 0 .25-.25v-1.5a.75.75 0 0 1 1.5 0v1.5A1.75 1.75 0 0 1 9.25 16h-7.5A1.75 1.75 0 0 1 0 14.25Z"></path><path d="M5 1.75C5 .784 5.784 0 6.75 0h7.5C15.216 0 16 .784 16 1.75v7.5A1.75 1.75 0 0 1 14.25 11h-7.5A1.75 1.75 0 0 1 5 9.25Zm1.75-.25a.25.25 0 0 0-.25.25v7.5c0 .138.112.25.25.25h7.5a.25.25 0 0 0 .25-.25v-7.5a.25.25 0 0 0-.25-.25Z"></path></svg>';
    const CHECK_ICON_SVG = '<svg width="14" height="14" viewBox="0 0 16 16" version="1.1" aria-hidden="true"><path d="M13.78 4.22a.75.75 0 0 1 0 1.06l-7.25 7.25a.75.75 0 0 1-1.06 0L2.22 9.28a.751.751 0 0 1 .018-1.042.751.751 0 0 1 1.042-.018L6 10.94l6.72-6.72a.75.75 0 0 1 1.06 0Z"></path></svg>';

    function initCodeBlocks() {
        const containers = document.querySelectorAll('.wp-github-md-container');
        if (!containers.length) {
            return;
        }

        const settings = window.wpGitHubMdFrontendSettings || {
            copyText: 'Copy',
            copiedText: 'Copied!'
        };

        containers.forEach(function (container) {
            const preBlocks = container.querySelectorAll('pre');

            preBlocks.forEach(function (pre) {
                const code = pre.querySelector('code');
                if (!code) {
                    return;
                }

                // 1. highlight.js 문법 강조 적용 (외부 라이브러리 로드 시)
                if (typeof window.hljs !== 'undefined') {
                    window.hljs.highlightElement(code);
                }

                // 2. 이미 복사 버튼이 붙어 있다면 건너뜀
                if (pre.querySelector('.wp-ghmd-copy-btn')) {
                    return;
                }

                // 3. 복사 버튼 생성 및 마운트
                const copyBtn = document.createElement('button');
                copyBtn.className = 'wp-ghmd-copy-btn';
                copyBtn.type = 'button';
                copyBtn.setAttribute('aria-label', settings.copyText);
                copyBtn.innerHTML = COPY_ICON_SVG + ' <span>' + settings.copyText + '</span>';

                copyBtn.addEventListener('click', function () {
                    const textToCopy = code.innerText || code.textContent;

                    copyToClipboard(textToCopy, function () {
                        copyBtn.classList.add('copied');
                        copyBtn.innerHTML = CHECK_ICON_SVG + ' <span>' + settings.copiedText + '</span>';

                        setTimeout(function () {
                            copyBtn.classList.remove('copied');
                            copyBtn.innerHTML = COPY_ICON_SVG + ' <span>' + settings.copyText + '</span>';
                        }, 2000);
                    });
                });

                pre.appendChild(copyBtn);
            });
        });
    }

    /**
     * 클립보드 복사 유틸리티 (HTTPS 및 로컬 fallback 지원)
     */
    function copyToClipboard(text, callback) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(callback).catch(function () {
                fallbackCopy(text, callback);
            });
        } else {
            fallbackCopy(text, callback);
        }
    }

    function fallbackCopy(text, callback) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            document.execCommand('copy');
            callback();
        } catch (err) {
            console.error('클립보드 복사 실패:', err);
        }

        document.body.removeChild(textArea);
    }

    // DOM 로드 완료 시 초기화
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCodeBlocks);
    } else {
        initCodeBlocks();
    }
})();
