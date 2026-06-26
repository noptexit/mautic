#!/bin/bash
set -e

curl -fsSL https://ddev.com/install.sh | bash
ddev start -y --skip-hooks
ddev composer install --no-interaction
ddev poweroff
mkdir -p .vscode && echo '{"workbench.editorAssociations": {"*.md": "vscode.markdown.preview.editor"}}' > .vscode/settings.json
