git-footnote is a tool intended for use in git's "commit-msg" hook. It appends footnotes based on inline references.

For example, one can put this in a project's .git/hooks/commit-msg:

```bash
#!/bin/bash
set -e

FILE="$1"
TMPFILE="$1.jira"
cp -p "$FILE" "$TMPFILE"
git-footnote MYAPP http://issues.example.org/jira < "$TMPFILE" > "$FILE"
rm -f "$TMPFILE"
```
