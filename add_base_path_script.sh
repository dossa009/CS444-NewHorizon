#!/bin/bash
# Add base-path.js as the FIRST script in all HTML files

echo "🔧 Adding base-path.js to all HTML files..."

for file in frontend/index.html frontend/pages/*.html; do
    if [ -f "$file" ] && [ "$file" != *"_template.html"* ]; then
        # Check if base-path.js is already included
        if grep -q "base-path.js" "$file"; then
            echo "  ⏭️  $file already has base-path.js"
            continue
        fi

        # Add base-path.js as FIRST script after <head>
        if [[ "$OSTYPE" == "darwin"* ]]; then
            sed -i '' '/<head>/a\
  <script src="/frontend/js/base-path.js"></script>
' "$file"
        else
            sed -i '/<head>/a\  <script src="/frontend/js/base-path.js"></script>' "$file"
        fi

        echo "  ✅ Added base-path.js to $file"
    fi
done

echo ""
echo "✅ All HTML files updated!"
echo ""
echo "📋 Next steps:"
echo "1. Commit and push changes to GitHub"
echo "2. The site should now work correctly on https://dossa009.github.io/CS444-NewHorizon/"
