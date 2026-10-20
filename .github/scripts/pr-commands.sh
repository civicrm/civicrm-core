#!/usr/bin/env bash
# Runs a PR command comment (/squash, /rebase, /port, /lint) for .github/workflows/pr-commands.yml.
# Expects the upstream repo as `origin` in the current directory; inputs come from the environment.
set -e

: "${PR_NUMBER:?}" "${REPO:?}" "${STATE:?}" "${BASE_REF:?}" "${HEAD_REF:?}" "${COMMENT_BODY:?}" "${GITHUB_ENV:?}"

# Sets the comment the workflow posts on failure, then stops. The message can hold PR file names, hence a random delimiter.
fail() {
  local delimiter
  delimiter="EOF_$(od -An -N16 -tx1 /dev/urandom | tr -d ' \n')"
  {
    echo "FAILURE_MSG<<$delimiter"
    echo "$1"
    echo "$delimiter"
  } >> "$GITHUB_ENV"
  exit 1
}

# Replays the PR's commits onto origin/$1, leaving the result on a detached HEAD.
replay_onto() {
  local target=$1 conflicts instructions
  if ! git rebase --onto "origin/$target" "$UPSTREAM" "$PR_HEAD"; then
    conflicts=$(git diff --name-only --diff-filter=U)
    git rebase --abort
    if [ "$target" = "$BASE_REF" ]; then
      instructions="git rebase origin/$target"
    else
      instructions="git rebase --onto origin/$target origin/$BASE_REF"
    fi
    fail "$(printf "### ❌ Rebase Failed due to Conflicts\n\nThe rebase onto \`%s\` failed due to merge conflicts. The following files have conflicts:\n\`\`\`\n%s\n\`\`\`\n\nPlease resolve these conflicts locally and force-push your changes:\n\`\`\`bash\n# Fetch latest branches\ngit fetch origin\n# Rebase locally\n%s\n# Resolve conflicts in your editor, then run:\ngit add <conflicted-files>\ngit rebase --continue\n# Force push to your branch\ngit push --force-with-lease\n\`\`\`" "$target" "$conflicts" "$instructions")"
  fi
}

# The highest X.Y branch is the RC, which is forward-merged to master; the one below is stable, which is not.
find_release_branches() {
  local branches
  branches=$(git ls-remote --heads origin | sed 's#.*refs/heads/##' | grep -E '^[0-9]+\.[0-9]+$' | sort -V)
  RC_BRANCH=$(echo "$branches" | tail -n 1)
  STABLE_BRANCH=$(echo "$branches" | tail -n 2 | head -n 1)
}

# Stops on a /rebase or /port that is most likely a mix-up of the RC and stable branch, unless forced.
check_target() {
  local action=$1 target=$2 force=$3 reason=""
  if [ "$action" = "port" ] && [ "$STATE" = "OPEN" ] && [ "$BASE_REF" = "master" ] && [ "$target" = "$RC_BRANCH" ]; then
    printf -v reason "\`%s\` is the RC branch and is forward-merged to \`master\`, so a port would duplicate this PR there. To land this change in \`%s\` and \`master\`, run \`/rebase %s\` instead." "$RC_BRANCH" "$RC_BRANCH" "$RC_BRANCH"
  elif [ "$action" = "rebase" ] && [ "$target" = "$STABLE_BRANCH" ] && [ "$BASE_REF" != "$STABLE_BRANCH" ]; then
    printf -v reason "\`%s\` is the stable branch and is not forward-merged, so after this rebase the change would never reach \`%s\` or \`master\`. To land it in all three, run \`/rebase %s\` and \`/port %s\`." "$STABLE_BRANCH" "$RC_BRANCH" "$RC_BRANCH" "$STABLE_BRANCH"
  fi
  if [ -n "$reason" ] && [ -z "$force" ]; then
    fail "$(printf "### ⚠️ Command Not Run\n\n%s\n\nIf you really mean it, run \`/%s %s force\`." "$reason" "$action" "$target")"
  fi
}

rebase_pr() {
  local target=$1
  if [ "$target" = "$BASE_REF" ] && git merge-base --is-ancestor "origin/$BASE_REF" "$PR_HEAD"; then
    echo "Branch is already up to date with $BASE_REF."
    return
  fi
  replay_onto "$target"
  git push --force-with-lease head_repo "HEAD:$HEAD_REF"
  if [ "$target" != "$BASE_REF" ]; then
    gh pr edit "$PR_NUMBER" --base "$target" --repo "$REPO"
  fi
}

port_pr() {
  local target=$1 title port_branch existing_pr new_pr_url body
  replay_onto "$target"
  title=$(gh pr view "$PR_NUMBER" --repo "$REPO" --json title --jq '.title')
  port_branch="port-$PR_NUMBER-$target"
  git push --force origin "HEAD:refs/heads/$port_branch"

  existing_pr=$(gh pr list --repo "$REPO" --head "$port_branch" --base "$target" --json url --jq '.[0].url')
  if [ -n "$existing_pr" ]; then
    body="\`$target\` port $existing_pr has been updated to match the latest changes in this PR."
  else
    new_pr_url=$(gh pr create \
      --repo "$REPO" \
      --base "$target" \
      --head "$port_branch" \
      --title "Port: \"$title\" to $target" \
      --body "Port of #$PR_NUMBER to $target." \
      --label "port")
    printf -v body "\`%s\` port: %s\n\nIf this PR changes, you can update the port by running \`/port %s\` again." "$target" "$new_pr_url" "$target"
  fi
  gh pr comment "$PR_NUMBER" --repo "$REPO" --body "$body"
}

squash_pr() {
  local merge_base first_commit commit_count
  git fetch origin "$BASE_REF"
  merge_base=$(git merge-base "origin/$BASE_REF" HEAD)
  first_commit=$(git log --reverse --format="%H" "origin/$BASE_REF..HEAD" | head -n 1)

  if [ -z "$first_commit" ]; then
    echo "No commits found to squash."
    exit 1
  fi

  commit_count=$(git log --oneline "origin/$BASE_REF..HEAD" | wc -l)
  if [ "$commit_count" -le 1 ]; then
    echo "Only one commit found. Nothing to squash."
    exit 0
  fi

  git reset --soft "$merge_base"
  git commit --reuse-message="$first_commit"
  git push --force-with-lease head_repo "HEAD:$HEAD_REF"
}

lint_pr() {
  local php_version buildkit_dir exec_cmd
  git fetch origin "$BASE_REF"

  # Switch PHP version to match composer.json if pre-installed
  php_version=$(jq -r '.config.platform.php // "8.1"' composer.json | cut -d. -f1-2)
  if [ -x "/usr/bin/php$php_version" ]; then
    echo "Switching PHP to version $php_version..."
    sudo update-alternatives --set php "/usr/bin/php$php_version"
  else
    echo "PHP version $php_version is not pre-installed. Using default: $(php -v | head -n 1)"
  fi

  # Setup buildkit/civilint
  echo "Cloning civicrm-buildkit..."
  buildkit_dir="$GITHUB_WORKSPACE/civicrm-buildkit"
  git clone -b master https://github.com/civicrm/civicrm-buildkit.git "$buildkit_dir"
  (
    cd "$buildkit_dir"
    # Prune composer.json to only keep phpcs and coder
    jq '.require = {"drupal/coder": .require["drupal/coder"], "squizlabs/php_codesniffer": .require["squizlabs/php_codesniffer"]}' composer.json > composer.json.tmp && mv composer.json.tmp composer.json
    composer install --no-interaction --no-plugins --no-scripts
    npm install jshint
  )

  exec_cmd="files=\$(git diff-tree --no-commit-id --name-only -r HEAD); if [ -n \"\$files\" ]; then echo \"\$files\" | $buildkit_dir/bin/civilint --fix -; fi; git diff --quiet || git commit -a --amend --no-edit"

  if ! git rebase --exec "$exec_cmd" "origin/$BASE_REF"; then
    git rebase --abort
    fail "### ❌ Lintroll Failed due to Conflicts"$'\n\n'"The automated formatting rebase failed due to merge conflicts. Please run \`civilint --fix\` and resolve any conflicts locally, then force-push your changes."
  fi

  git push --force-with-lease head_repo "HEAD:$HEAD_REF"
}

if [ -n "$PR_COMMANDS_PAT" ]; then
  git config user.name "civicrm-builder"
  git config user.email "civicrm-builder@users.noreply.github.com"
else
  git config user.name "github-actions[bot]"
  git config user.email "github-actions[bot]@users.noreply.github.com"
fi

if [ "$STATE" = "MERGED" ]; then
  git fetch origin "pull/$PR_NUMBER/head"
  git checkout -b pr-branch FETCH_HEAD
else
  git remote add head_repo "https://x-access-token:$GH_TOKEN@github.com/$HEAD_OWNER/$HEAD_REPO.git"
  git fetch head_repo "$HEAD_REF"
  git checkout -b pr-branch "head_repo/$HEAD_REF"
fi
PR_HEAD=$(git rev-parse HEAD)

CMD=$(echo "$COMMENT_BODY" | head -n 1 | xargs | tr '[:upper:]' '[:lower:]')

if [ "$STATE" = "MERGED" ] && [[ ! "$CMD" =~ ^/port ]]; then
  echo "Error: Only /port command is allowed on merged pull requests."
  exit 1
fi

if [[ "$CMD" =~ ^/squash ]]; then
  echo "Running squash..."
  squash_pr

elif [[ "$CMD" =~ ^/(rebase|port)([[:space:]]+([^[:space:]]+))?([[:space:]]+(force)([[:space:]]|$))? ]]; then
  ACTION="${BASH_REMATCH[1]}"
  NEW_BASE="${BASH_REMATCH[3]}"
  FORCE="${BASH_REMATCH[5]}"
  if [ "$NEW_BASE" = "force" ]; then
    NEW_BASE=""
    FORCE="force"
  fi

  if [ "$ACTION" = "port" ] && [ -z "$NEW_BASE" ]; then
    echo "Error: /port requires a target branch argument (e.g. /port 5.74 or /port stable)."
    exit 1
  fi
  NEW_BASE="${NEW_BASE:-$BASE_REF}"
  find_release_branches
  case "$NEW_BASE" in
    rc) NEW_BASE="$RC_BRANCH" ;;
    stable) NEW_BASE="$STABLE_BRANCH" ;;
  esac
  check_target "$ACTION" "$NEW_BASE" "$FORCE"
  echo "Running $ACTION to $NEW_BASE..."

  git fetch origin "$BASE_REF"
  if [ "$NEW_BASE" != "$BASE_REF" ]; then
    git fetch origin "$NEW_BASE"
  fi

  # The commit the PR's own commits start after
  if [ "$STATE" = "MERGED" ]; then
    # Check if the PR head is already an ancestor of the base branch (standard merge)
    if git merge-base --is-ancestor HEAD "origin/$BASE_REF"; then
      UPSTREAM="$MERGE_COMMIT^1"
    else
      UPSTREAM=$(git merge-base "origin/$BASE_REF" HEAD)
    fi
  else
    UPSTREAM="origin/$BASE_REF"
  fi

  "${ACTION}_pr" "$NEW_BASE"

elif [[ "$CMD" =~ ^/(lintroll|lint) ]]; then
  echo "Running lintroll..."
  lint_pr

else
  echo "Unknown command: $CMD"
  exit 1
fi
