# Channel3 for Magento

Connect your Magento / Adobe Commerce store to [Channel3](https://trychannel3.com) for product analytics and AI shopping attribution.

## What it does

- **Automatic catalog sync** — Creates an API integration so Channel3 can read your product catalog
- **Page view tracking** — Tracks product page views with server-side product ID injection (no DOM scraping)
- **Checkout tracking** — Records order completions for conversion attribution
- **One-click connect** — Enter your Channel3 merchant ID and the module handles the rest

## Installation

```bash
composer require channel3/analytics
bin/magento module:enable Channel3_Analytics
bin/magento setup:upgrade
bin/magento cache:flush
```

## Setup

1. Go to **Channel3** in the Magento admin sidebar
2. Enter your 4-character merchant ID from your [Channel3 dashboard](https://trychannel3.com/dashboard)
3. Click **Connect to Channel3**

That's it. The module automatically creates the API integration, sends the credentials to Channel3, and starts tracking page views and checkouts on your storefront.

## Requirements

- Magento 2.4 or later / Adobe Commerce
- PHP 8.1+
- A [Channel3](https://trychannel3.com) account

## How it works

When you click Connect, the module:

1. Creates a Magento Integration with the required API permissions (Catalog, Stores)
2. Generates OAuth credentials and securely sends them to Channel3
3. Injects a lightweight tracking script on your storefront pages
4. On product pages, the product ID is injected server-side (not scraped from the DOM)
5. On the checkout success page, order details are sent for conversion attribution

All tracking uses a persistent client ID stored in the shopper's browser to link product views to purchases.

## Support

- [Channel3 Dashboard](https://trychannel3.com/dashboard)
- [Channel3 Documentation](https://docs.trychannel3.com)
- Email: support@trychannel3.com

## License

Proprietary — see [LICENSE](LICENSE) for details.
