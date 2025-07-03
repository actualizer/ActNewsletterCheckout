# ActNewsletterCheckout - Shopware Plugin

A Shopware 6 plugin that adds a newsletter subscription checkbox to the checkout confirmation page, allowing customers to subscribe to the newsletter during the order process.

## Features

- ✅ Newsletter subscription checkbox on checkout confirmation page
- ✅ Checks if customer is already subscribed (won't show checkbox if already subscribed)
- ✅ Newsletter subscription is only processed when order is completed
- ✅ Integrates with Shopware's native newsletter system and double opt-in flow
- ✅ Admin configuration to enable/disable the feature
- ✅ Multi-language support (German & English)
- ✅ Compatible with Shopware 6.6.10 - 6.7.x

## Requirements

- Shopware 6.6.10 or higher (up to 6.7.x)
- PHP 8.3 or higher

## Installation

1. Download or clone this plugin into your `custom/plugins/` directory
2. Install and activate the plugin via CLI:
   ```bash
   bin/console plugin:refresh
   bin/console plugin:install --activate ActNewsletterCheckout
   bin/console cache:clear
   ```

## Configuration

1. Go to Admin Panel → Settings → System → Plugins
2. Find "Actualize: Newsletter subscription in checkout" and click on the three dots
3. Click "Config" to access plugin settings
4. Enable/disable the newsletter checkbox feature

## How it works

1. **Checkout Display**: When a logged-in customer reaches the checkout confirmation page, the plugin checks if they are already subscribed to the newsletter
2. **Checkbox Visibility**: If the customer is not subscribed, a newsletter subscription checkbox appears below the terms and conditions
3. **Order Processing**: When the customer completes their order with the checkbox checked, the plugin subscribes them to the newsletter
4. **Newsletter Integration**: The subscription uses Shopware's native newsletter system, respecting double opt-in settings if configured

## Technical Details

### Events Used
- `CheckoutConfirmPageLoadedEvent` - To add the newsletter checkbox to the page
- `CartConvertedEvent` - To store the newsletter subscription choice in the order
- `CheckoutOrderPlacedEvent` - To process the newsletter subscription after order completion

### Template Extensions
The plugin extends the checkout confirmation template (`index.html.twig`) to add the newsletter subscription section.

### Newsletter Integration
Uses Shopware's `NewsletterSubscribeRoute` to handle subscriptions, ensuring compatibility with:
- Double opt-in settings
- Newsletter recipient management
- Email templates and confirmation flows

## Translations

The plugin includes translations for:
- **German (de-DE)**: Newsletter-Anmeldung
- **English (en-GB)**: Newsletter subscription

Translation keys:
- `checkout.confirmNewsletterHeader`
- `checkout.confirmNewsletterSubscribe`

## File Structure

```
ActNewsletterCheckout/
├── composer.json
├── README.md
├── src/
│   ├── ActNewsletterCheckout.php
│   ├── Resources/
│   │   ├── config/
│   │   │   ├── config.xml
│   │   │   └── services.xml
│   │   ├── snippet/
│   │   │   ├── de_DE/
│   │   │   │   └── storefront.de-DE.json
│   │   │   └── en_GB/
│   │   │       └── storefront.en-GB.json
│   │   └── views/
│   │       └── storefront/
│   │           └── page/
│   │               └── checkout/
│   │                   └── confirm/
│   │                       └── index.html.twig
│   └── Subscriber/
│       └── CheckoutConfirmSubscriber.php
```

## Development

### Building/Testing
After making changes to templates or translations:
```bash
bin/console cache:clear
bin/console theme:compile
```

### Debugging
The plugin respects Shopware's logging configuration. Check your log files for any newsletter subscription errors.

## Compatibility

- **Shopware Version**: 6.6.10 - 6.7.x
- **PHP Version**: 8.3+
- **Template Compatibility**: Uses Shopware 6.6+ template structure

## Support

For issues and feature requests, please use the GitHub issue tracker.

## License

This plugin is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Credits

Developed by Actualize

---

Made with ❤️ for the Shopware Community
