#!/usr/bin/env bash
#
# Reads backend/secret.env (local-only, gitignored — see secret.env.example)
# and uploads it as encrypted GitHub Actions secrets via the `gh` CLI. This
# is the ONLY thing that ever reads secret.env; the deploy workflow itself
# reads exclusively from `secrets.*` and never sees this file.
#
# Usage: from inside backend/, run `bash scripts/push-secrets.sh`.
#
# Written for bash 3.2 (macOS's stock /bin/bash) as well as modern bash —
# no associative arrays, no `mapfile`.

set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

SECRET_FILE="secret.env"

if [[ ! -f "$SECRET_FILE" ]]; then
  echo "error: $SECRET_FILE not found. Copy secret.env.example to secret.env and fill in real values first." >&2
  exit 1
fi

if ! command -v gh >/dev/null 2>&1; then
  echo "error: GitHub CLI (gh) is required. Install it and run 'gh auth login' first." >&2
  exit 1
fi

deploy_ssh_host=""
deploy_ssh_port=""
deploy_ssh_user=""
deploy_ssh_key_path=""
deploy_path=""
deploy_composer_bin=""
dotenv_content=""

while IFS= read -r raw_line || [[ -n "$raw_line" ]]; do
  line="${raw_line%$'\r'}" # tolerate CRLF line endings

  # Skip blank lines and comments.
  if [[ -z "${line//[[:space:]]/}" || "$line" =~ ^[[:space:]]*# ]]; then
    continue
  fi

  key="${line%%=*}"
  value="${line#*=}"

  case "$key" in
    DEPLOY_SSH_HOST) deploy_ssh_host="$value" ;;
    DEPLOY_SSH_PORT) deploy_ssh_port="$value" ;;
    DEPLOY_SSH_USER) deploy_ssh_user="$value" ;;
    DEPLOY_SSH_KEY_PATH) deploy_ssh_key_path="$value" ;;
    DEPLOY_PATH) deploy_path="$value" ;;
    DEPLOY_COMPOSER_BIN) deploy_composer_bin="$value" ;;
    *) dotenv_content="${dotenv_content}${line}"$'\n' ;;
  esac
done < "$SECRET_FILE"

set_secret() {
  local name="$1" value="$2"
  printf '%s' "$value" | gh secret set "$name" >/dev/null
  echo "  set $name"
}

echo "Uploading deploy-connection secrets..."
[[ -n "$deploy_ssh_host" ]] && set_secret SSH_HOST "$deploy_ssh_host"
[[ -n "$deploy_ssh_port" ]] && set_secret SSH_PORT "$deploy_ssh_port"
[[ -n "$deploy_ssh_user" ]] && set_secret SSH_USER "$deploy_ssh_user"
[[ -n "$deploy_path" ]] && set_secret DEPLOY_PATH "$deploy_path"
[[ -n "$deploy_composer_bin" ]] && set_secret COMPOSER_BIN "$deploy_composer_bin"

if [[ -z "$deploy_ssh_key_path" ]]; then
  echo "error: DEPLOY_SSH_KEY_PATH is required in $SECRET_FILE" >&2
  exit 1
fi
if [[ ! -f "$deploy_ssh_key_path" ]]; then
  echo "error: SSH private key not found at $deploy_ssh_key_path" >&2
  exit 1
fi
echo "Uploading SSH private key from $deploy_ssh_key_path..."
gh secret set SSH_PRIVATE_KEY < "$deploy_ssh_key_path" >/dev/null
echo "  set SSH_PRIVATE_KEY"

if [[ -z "$dotenv_content" ]]; then
  echo "error: no application .env lines found in $SECRET_FILE (everything not prefixed DEPLOY_)" >&2
  exit 1
fi

echo "Uploading application .env as PROD_DOTENV..."
printf '%s' "$dotenv_content" | gh secret set PROD_DOTENV >/dev/null
echo "  set PROD_DOTENV"

echo
echo "Done. Verify with: gh secret list"
