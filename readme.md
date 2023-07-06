# Remote Page Inject

Fetches raw html from remote URL and injects into current page.

This plugin was created with intentions of pulling in landing pages created with [ClickFunnels](https://www.clickfunnels.com/) into WordPress pages. By combining Remote Page Inject's shortcode with [Elementor Canvas](https://elementor.com/help/using-elementors-canvas-page-template/) and [WP Landing Kit](https://themeisle.com/plugins/wp-landing-kit/), a single WordPress installation can house many unique landing pages, each with a custom domain, with content managed within ClickFunnels.

## Shortcode Usage

On any page or post add a shortcode like this:

```
[remote_page_inject url="https://account.clickfunnels.com/example-page"]
```

Each day WordPress will reach out to the remote site and cache the HTML response. The `<body>` of the HTML response will be injected where the shortcode is placed. The `<head>` of the HTML response will be injected via WordPress' `wp_head` hook.