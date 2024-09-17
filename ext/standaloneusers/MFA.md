# MFA

Browser:
- Submit username + password to new API endpoint User.login
- if success, redirect to URL in response

Server: User.login

- if credentials don't match: return nope.
- (credentials match)
- if no MFA required (config setting): **login, return /civicrm**
- (MFA is required, MfaTOTP, Mfa2)
- Future: if multiple configured, give user choice.
- (Single MFA)
- `$_SESSION['pendingLoginUserID'] = $userID`
- `$_SESSION['pendingLoginUserExpires'] = time()+120`
- `$result['nextUrl'] = MfaTOTP::getNextUrl()`
  * If set up for this user, return URL for the form: enter 6 digit code
  * If not set up for this user, return URL for the form to set it up

Browser, e.g. when it's set up:

- requests the URL, gets the form.
- The browser sends a request `User::login()->setMfa('otp')->setValue('123456')`

Server: User.login (with mfa)

- check: is the mfa configured? If not: reject hacker
- check: do we have `pendingLoginUserID` if not, reject
- if `$mfa->checkData(123456)`: **login as pendingLoginUserID, remove
  pendingLoginUserID, return /civicrm**
- reject: clear session send back to /civicrm/login


Store seed encrypted.
$encrypted = Civi::service('crypto.token')->encrypt('t0ps3cr37', 'CRED');
