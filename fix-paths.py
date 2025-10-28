#!/usr/bin/env python3
"""
Script to automatically inject base path configuration code
into all HTML files for proper local and GitHub Pages deployment
"""
import os
import re

# Code to inject into <head>
BASE_PATH_SCRIPT = '''  <script>
    // Automatic base path configuration (local vs GitHub Pages)
    (function() {
      const isLocal = window.location.hostname === 'localhost' ||
                      window.location.hostname === '127.0.0.1' ||
                      window.location.hostname === '';
      const basePath = isLocal ? '/' : '/CS444-NewHorizon/';
      const base = document.createElement('base');
      base.href = basePath;
      document.head.insertBefore(base, document.head.firstChild);
    })();
  </script>
'''

def inject_base_path_script(file_path):
    """Inject base path script into an HTML file"""
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # Check if script is already present
    if 'Automatic base path configuration' in content:
        print(f"  ✓ {file_path} - Script already present")
        return False

    # Find <head> tag and inject script right after
    pattern = r'(<head[^>]*>)'
    replacement = r'\1\n' + BASE_PATH_SCRIPT

    new_content = re.sub(pattern, replacement, content, count=1)

    if new_content != content:
        with open(file_path, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f"  ✓ {file_path} - Script injected")
        return True
    else:
        print(f"  ✗ {file_path} - <head> tag not found")
        return False

def main():
    """Process all HTML files in frontend/"""
    frontend_dir = 'frontend'
    modified_count = 0

    print("Injecting base path configuration script...\n")

    # Process all HTML files
    for root, _, files in os.walk(frontend_dir):
        for file in files:
            if file.endswith('.html'):
                file_path = os.path.join(root, file)
                if inject_base_path_script(file_path):
                    modified_count += 1

    print(f"\n✓ Done! {modified_count} file(s) modified")
    print("\nYou can now run the local server with:")
    print("  python3 serve.py")

if __name__ == '__main__':
    main()
