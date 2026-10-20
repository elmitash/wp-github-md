/**
 * WP Clean Markdown - Admin Editor Script (EasyMDE)
 */
(function ($) {
    'use strict';

    $(document).ready(function () {
        const contentTextarea = document.getElementById('content');
        if (!contentTextarea) {
            return;
        }

        const settings = window.wpGitHubMdSettings || {};

        // 워드프레스 기본 비주얼/텍스트 탭 및 미디어 버튼 숨김 처리
        $('#wp-content-wrap, #wp-content-editor-tools').hide();

        // EasyMDE 인스턴스 초기화
        const easyMDE = new EasyMDE({
            element: contentTextarea,
            autoDownloadFontAwesome: false, // FontAwesome은 Enqueue로 별도 로드
            spellChecker: false,
            autosave: {
                enabled: false // 워드프레스 고유의 리비전/임시저장 기능 존중
            },
            status: ['lines', 'words', 'cursor', 'upload-image'],
            sideBySideFullscreen: false,
            syncSideBySidePreviewScroll: true,
            previewClass: settings.previewClass 
                ? ['editor-preview', settings.previewClass] 
                : ['editor-preview', 'markdown-body'],
            placeholder: settings.placeholder || '마크다운으로 글을 작성하세요...',
            uploadImage: true,
            imageUploadFunction: function (file, onSuccess, onError) {
                if (!file || !file.type.match(/^image\//i)) {
                    onError('이미지 파일만 업로드할 수 있습니다.');
                    return;
                }

                const formData = new FormData();
                formData.append('action', 'wp_github_md_upload_image');
                formData.append('nonce', settings.uploadNonce || '');
                formData.append('file', file);

                fetch(settings.ajaxUrl || window.ajaxurl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(function (response) {
                    return response.json();
                })
                .then(function (result) {
                    if (result && result.success && result.data && result.data.url) {
                        onSuccess(result.data.url);
                    } else {
                        const errMsg = (result && result.data && result.data.message) 
                            ? result.data.message 
                            : (settings.uploadErrorText || '이미지 업로드에 실패했습니다.');
                        onError(errMsg);
                    }
                })
                .catch(function (err) {
                    console.error('Image upload failed:', err);
                    onError(settings.uploadErrorText || '서버 통신 오류가 발생했습니다.');
                });
            },
            imageTexts: {
                sbInit: '이미지를 여기에 붙여넣거나(Ctrl+V) 드래그하세요.',
                sbOnDragEnter: '이미지를 놓아 업로드하세요.',
                sbOnDrop: '이미지 업로드 중...',
                sbProgress: '업로드 중... #progress#%',
                sbOnUploaded: '업로드 완료!',
                sizeUnits: 'B,KB,MB'
            },
            toolbar: [
                'bold',
                'italic',
                'strikethrough',
                'heading',
                '|',
                'quote',
                'code',
                'unordered-list',
                'ordered-list',
                '|',
                'link',
                'upload-image',
                'table',
                'horizontal-rule',
                '|',
                'preview',
                'side-by-side',
                'fullscreen',
                '|',
                'guide'
            ],
            renderingConfig: {
                singleLineBreaks: false,
                codeSyntaxHighlighting: false
            }
        });

        // 클립보드 스크린샷 붙여넣기 보조 핸들러 (CodeMirror 영역 직접 캡처 보강)
        easyMDE.codemirror.on('paste', function (cm, event) {
            const clipboardData = event.clipboardData || window.clipboardData;
            if (!clipboardData || !clipboardData.items) {
                return;
            }

            for (let i = 0; i < clipboardData.items.length; i++) {
                const item = clipboardData.items[i];
                if (item.type.indexOf('image') !== -1) {
                    const blob = item.getAsFile();
                    if (blob) {
                        // EasyMDE 내장 업로더 호출
                        event.preventDefault();
                        const timestamp = new Date().toISOString().replace(/[-:T.]/g, '').slice(0, 14);
                        const file = new File([blob], 'screenshot-' + timestamp + '.png', { type: blob.type });
                        
                        // 임시 안내 텍스트 삽입
                        const placeholderText = '![이미지 업로드 중...]()';
                        cm.replaceSelection(placeholderText);
                        const cursor = cm.getCursor();

                        const formData = new FormData();
                        formData.append('action', 'wp_github_md_upload_image');
                        formData.append('nonce', settings.uploadNonce || '');
                        formData.append('file', file);

                        fetch(settings.ajaxUrl || window.ajaxurl, {
                            method: 'POST',
                            body: formData,
                            credentials: 'same-origin'
                        })
                        .then(function (res) { return res.json(); })
                        .then(function (result) {
                            const currentDoc = cm.getValue();
                            if (result && result.success && result.data && result.data.url) {
                                const newTag = '![' + file.name + '](' + result.data.url + ')';
                                cm.setValue(currentDoc.replace(placeholderText, newTag));
                            } else {
                                alert((result && result.data && result.data.message) || settings.uploadErrorText);
                                cm.setValue(currentDoc.replace(placeholderText, ''));
                            }
                        })
                        .catch(function () {
                            alert(settings.uploadErrorText || '업로드 오류가 발생했습니다.');
                            cm.setValue(cm.getValue().replace(placeholderText, ''));
                        });
                        break;
                    }
                }
            }
        });

        // 워드프레스 폼 전송(발행, 임시저장 등) 시 원본 textarea에 마크다운 내용 강제 동기화
        const syncContent = function () {
            if (easyMDE) {
                contentTextarea.value = easyMDE.value();
            }
        };

        // 폼 submit 이벤트 가로채기
        const $form = $('#post');
        if ($form.length) {
            $form.on('submit', syncContent);
        }

        // 발행/저장 버튼 클릭 시 사전 동기화
        $('#publish, #save-post').on('click', syncContent);

        // 기본적으로 편의를 위해 사이드 바이 사이드(Side-by-side) 모드 활성화 (화면 너비 1200px 이상일 때)
        if (window.innerWidth >= 1200 && !easyMDE.isSideBySideActive()) {
            EasyMDE.toggleSideBySide(easyMDE);
        }

        // 전역 객체에 등록
        window.wpCleanMarkdownEditor = easyMDE;
    });
})(jQuery);
