# Security policy

Report a vulnerability privately to **contact@satelit.md**, with the steps to reproduce it and the version you used. Please do not open a public issue for it.

You get an answer within a few working days. When a fix ships, the CHANGELOG says so.

## Keys

- Never commit a Satelit API key, put it in a URL, a log line or code that runs in a browser or a mobile app. This SDK sends it only in the `Authorization` header and never includes it in an error message.
- A key that leaked: revoke it in the [console](https://developer.satelit.md/en/console/) and make a new one.
