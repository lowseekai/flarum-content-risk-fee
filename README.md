# Content Risk Fee

Independent Flarum 2.x extension for `lowseekai`.

When a post matches a configured non-whitelisted external link or sensitive-content rule,
the user sees a Flarum modal before publishing. The user can pay the configured point fee
and publish the original content unchanged, or return to edit it.

The server repeats the inspection immediately before the post is saved and charges points
through `ramon/point-system` inside the post transaction. The browser is not trusted for
the rule decision or the fee amount.

This first implementation includes:

- external-link detection with root-domain and subdomain whitelist matching;
- sensitive-word and custom-regex detection;
- custom Flarum modal instead of browser alerts;
- atomic point deduction through `Ramon\PointSystem\Repository\PointsRepository`;
- exact duplicate-content detection within a configurable time window;
- risk and duplicate event tables for later moderation/reporting work.

Purify is not required. This extension does not replace or hide content.
