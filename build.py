#!/usr/bin/env python3
"""
WP GitHub MD - WordPress Plugin Packaging Script
워드프레스 공식 설치 표준 규격의 초경량 zip 패키지를 생성합니다.
"""
import os
import zipfile

def build_zip():
    base_dir = os.path.dirname(os.path.abspath(__file__))
    zip_filename = os.path.join(base_dir, 'wp-github-md.zip')
    exclude_names = {'.git', '.gitignore', 'wp-github-md.zip', '__pycache__', '.DS_Store', 'build.py'}

    print(f"📦 워드프레스 플러그인 패키징 시작: {zip_filename}")
    
    file_count = 0
    with zipfile.ZipFile(zip_filename, 'w', zipfile.ZIP_DEFLATED) as zf:
        for root, dirs, files in os.walk(base_dir):
            dirs[:] = [d for d in dirs if d not in exclude_names]
            for f in files:
                if f in exclude_names:
                    continue
                full_path = os.path.join(root, f)
                rel_path = os.path.relpath(full_path, base_dir)
                arcname = os.path.join('wp-github-md', rel_path)
                zf.write(full_path, arcname)
                file_count += 1

    file_size_kb = os.path.getsize(zip_filename) / 1024
    print(f"✅ 패키징 완료! (총 {file_count}개 파일, 크기: {file_size_kb:.1f} KB)")
    print(f"👉 워드프레스 관리자 패널(플러그인 > 새로 추가 > 플러그인 업로드)에서 바로 설치할 수 있습니다.")

if __name__ == '__main__':
    build_zip()
