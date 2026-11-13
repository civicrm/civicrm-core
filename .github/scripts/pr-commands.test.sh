#!/usr/bin/env bash
# Tests pr-commands.sh against throwaway bare git repos with a stubbed gh.
# Needs only bash and git; no network and no GitHub. Run it directly:
#   .github/scripts/pr-commands.test.sh
set -euo pipefail

SCRIPT_DIR=$(cd "$(dirname "$0")" && pwd)
SCRIPT="$SCRIPT_DIR/pr-commands.sh"
ROOT=$(mktemp -d)
trap 'rm -rf "$ROOT"' EXIT

export GIT_CONFIG_GLOBAL="$ROOT/gitconfig" GIT_CONFIG_NOSYSTEM=1
git config --global init.defaultBranch master
git config --global user.name tester
git config --global user.email tester@example.com
git config --global advice.detachedHead false
git config --global advice.mergeConflict false
# Map the fork's https remote (built by the script from a token) to a local bare repo
git config --global url."$ROOT/fork.git".insteadOf "https://x-access-token:token@github.com/fork-owner/civicrm-core.git"

# gh stub: logs every call, answers the few reads the script makes.
mkdir -p "$ROOT/bin"
cat > "$ROOT/bin/gh" <<'STUB'
#!/usr/bin/env bash
{ printf 'gh'; printf ' %q' "$@"; echo; } >> "$GH_LOG"
case "$1 $2" in
  "pr view") echo "Some title" ;;
  "pr list") echo "" ;;
  "pr create")
    prev="" base=""
    for a in "$@"; do [ "$prev" = "--base" ] && base=$a; prev=$a; done
    echo "https://github.com/civicrm/civicrm-core/pull/9${base//./}" ;;
esac
STUB
chmod +x "$ROOT/bin/gh"
export PATH="$ROOT/bin:$PATH"

PASS=0 FAIL=0

# run_case <STATE> <BASE_REF> <COMMENT> [conflict]
# Builds a fresh origin/fork, runs the script, and fills the globals below:
#   RC          exit code
#   ENV_OUT     contents the script wrote to $GITHUB_ENV
#   GH_CALLS    one gh invocation per line
#   ORIGIN_REFS branch names in the upstream repo
run_case() {
  local state=$1 base=$2 comment=$3 conflict=${4:-}
  local w="$ROOT/work"
  rm -rf "$ROOT/seed" "$ROOT/origin.git" "$ROOT/fork.git" "$w"

  git init -q "$ROOT/seed"
  (
    cd "$ROOT/seed"
    echo a > a.txt && git add . && git commit -qm "root"
    for b in 6.18 6.19 6.20; do
      git checkout -q -b "$b" master
      echo "$b" > "shared.txt" && git add . && git commit -qm "line for $b"
    done
    git checkout -q master
    echo master > shared.txt && git add . && git commit -qm "line for master"
    git checkout -q -b feat "$base"
    if [ -n "$conflict" ]; then
      echo feature > shared.txt
    else
      echo feature > feat.txt
    fi
    git add . && git commit -qm "the feature"
  )

  git clone -q --bare "$ROOT/seed" "$ROOT/origin.git"
  git clone -q --bare "$ROOT/seed" "$ROOT/fork.git"
  git -C "$ROOT/origin.git" symbolic-ref HEAD refs/heads/master
  git -C "$ROOT/origin.git" branch -D feat >/dev/null 2>&1 || true
  git -C "$ROOT/origin.git" update-ref refs/pull/42/head "$(git -C "$ROOT/seed" rev-parse feat)"

  local merge_commit=""
  if [ "$state" = MERGED ]; then
    git -C "$ROOT/seed" checkout -q "$base"
    git -C "$ROOT/seed" merge -q --no-ff feat -m "merge feat"
    merge_commit=$(git -C "$ROOT/seed" rev-parse HEAD)
    git -C "$ROOT/seed" push -q "$ROOT/origin.git" "$base"
  fi

  git clone -q "$ROOT/origin.git" "$w"

  export GITHUB_ENV="$ROOT/github_env" GH_LOG="$ROOT/gh.log"
  export GITHUB_WORKSPACE="$w" RUNNER_TEMP="$ROOT"
  export GH_TOKEN=token PR_COMMANDS_PAT=""
  export PR_NUMBER=42 REPO=civicrm/civicrm-core STATE="$state"
  export BASE_REF="$base" HEAD_REF=feat HEAD_OWNER=fork-owner HEAD_REPO=civicrm-core
  export MERGE_COMMIT="$merge_commit" COMMENT_BODY="$comment"
  : > "$GITHUB_ENV"
  : > "$GH_LOG"

  RC=0
  ( cd "$w" && bash "$SCRIPT" ) >/dev/null 2>&1 || RC=$?
  ENV_OUT=$(cat "$GITHUB_ENV")
  GH_CALLS=$(cat "$GH_LOG")
  ORIGIN_REFS=$(git -C "$ROOT/origin.git" for-each-ref --format='%(refname:short)' refs/heads)
}

# ok / bad record the result of the check just evaluated by the caller.
ok() { PASS=$((PASS + 1)); }
bad() {
  FAIL=$((FAIL + 1))
  echo "FAIL: $1"
  echo "  exit=$RC"
  [ -n "$ENV_OUT" ] && echo "  github_env: $(echo "$ENV_OUT" | tr '\n' ' ')"
  [ -n "$GH_CALLS" ] && echo "  gh: $(echo "$GH_CALLS" | tr '\n' '|')"
}

# Predicates over the globals filled by run_case.
has_call() { echo "$GH_CALLS" | grep -q -- "$1"; }
env_has() { echo "$ENV_OUT" | grep -q -- "$1"; }
origin_has_branch() { echo "$ORIGIN_REFS" | grep -qx -- "$1"; }
succeeded() { [ "$RC" -eq 0 ]; }
failed() { [ "$RC" -ne 0 ]; }
blocked_with() { failed && env_has "$1"; }

### /squash: a single-commit branch is a no-op that succeeds
run_case OPEN master "/squash"
if succeeded && ! has_call "pr edit" && ! has_call "pr create"
then ok; else bad "squash with one commit succeeds and does nothing"; fi

### /rebase onto the same base: up to date, no retarget
run_case OPEN master "/rebase"
if succeeded && ! has_call "pr edit"
then ok; else bad "rebase onto same base succeeds without retargeting"; fi

### /rebase to an older non-stable base: retargets the PR
run_case OPEN master "/rebase 6.18"
if succeeded && has_call "pr edit 42 --base 6.18"
then ok; else bad "rebase 6.18 retargets the PR"; fi

### /rebase rc alias resolves to the highest X.Y branch
run_case OPEN master "/rebase rc"
if succeeded && has_call "pr edit 42 --base 6.20"
then ok; else bad "rebase rc retargets to 6.20"; fi

### /rebase to stable from master is blocked, names /backport, offers force
run_case OPEN master "/rebase stable"
if blocked_with "stable branch" && env_has "/backport stable" && env_has "/rebase 6.19 force" && ! has_call "pr edit"
then ok; else bad "rebase stable is blocked and explains the alternatives"; fi

### /rebase stable force runs anyway
run_case OPEN master "/rebase 6.19 force"
if succeeded && has_call "pr edit 42 --base 6.19"
then ok; else bad "rebase stable force retargets to 6.19"; fi

### /port to the RC on an open master PR is blocked, names /backport, offers force
run_case OPEN master "/port rc"
if blocked_with "RC branch" && env_has "/backport rc" && env_has "/port 6.20 force" && ! origin_has_branch "port-42-6.20"
then ok; else bad "port rc on open master PR is blocked and explains the alternatives"; fi

### /port to stable on an open master PR is allowed
run_case OPEN master "/port stable"
if succeeded && has_call "pr create --repo civicrm/civicrm-core --base 6.19" && origin_has_branch "port-42-6.19"
then ok; else bad "port stable creates the port PR and branch"; fi

### /port on a merged PR stays allowed, even to the RC
run_case MERGED master "/port 6.20"
if succeeded && has_call "pr create --repo civicrm/civicrm-core --base 6.20"
then ok; else bad "port 6.20 on merged PR creates the port"; fi

### /backport stable on an open master PR: port to stable, then retarget to the RC
run_case OPEN master "/backport stable"
if succeeded && has_call "pr create --repo civicrm/civicrm-core --base 6.19" && has_call "pr edit 42 --base 6.20"
then ok; else bad "backport stable ports to stable and retargets the PR to the RC"; fi

### /backport takes the version number of stable as well
run_case OPEN master "/backport 6.19"
if succeeded && has_call "pr create --repo civicrm/civicrm-core --base 6.19" && has_call "pr edit 42 --base 6.20"
then ok; else bad "backport 6.19 behaves like backport stable"; fi

### /backport rc on an open master PR: just retarget to the RC
run_case OPEN master "/backport rc"
if succeeded && has_call "pr edit 42 --base 6.20" && ! has_call "pr create"
then ok; else bad "backport rc retargets to the RC and ports nothing"; fi

### /backport master on an open master PR: nothing to do
run_case OPEN master "/backport master"
if succeeded && ! has_call "pr edit" && ! has_call "pr create"
then ok; else bad "backport master on a master PR is a no-op"; fi

### /backport stable on a merged master PR: port to both stable and the RC
run_case MERGED master "/backport stable"
if succeeded && has_call "pr create --repo civicrm/civicrm-core --base 6.19" && has_call "pr create --repo civicrm/civicrm-core --base 6.20"
then ok; else bad "backport stable on merged PR ports to both stable and the RC"; fi

### /backport to an unknown branch is refused
run_case OPEN master "/backport 6.18"
if blocked_with "Unknown Backport Target"
then ok; else bad "backport to a plain branch is refused"; fi

### A non-port command on a merged PR is refused
run_case MERGED master "/rebase 6.20"
if failed
then ok; else bad "rebase on a merged PR is refused"; fi

### An unknown command fails
run_case OPEN master "/frobnicate"
if failed
then ok; else bad "unknown command fails"; fi

### A port that conflicts is reported and makes no PR
run_case OPEN master "/backport stable" conflict
if failed && env_has "Port Failed due to Conflicts" && ! has_call "pr create"
then ok; else bad "a conflicting port is reported, not pushed"; fi

echo "----------------------------------------"
echo "passed: $PASS, failed: $FAIL"
[ "$FAIL" -eq 0 ]
