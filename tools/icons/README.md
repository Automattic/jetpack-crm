# CRM's own icons

Icons that `@wordpress/icons` and `social-logos` don't have. `tools/wp-icons.mjs` lists them under `customIcons`, and `npm run build:icons` builds them with the rest.

Each one is a single SVG on the 24px grid (`viewBox="0 0 24 24"`), like `@wordpress/icons`.

| File | What | Source |
| --- | --- | --- |
| `phone.svg` | Telephone handset. `@wordpress/icons` and Gridicons only have a mobile phone. | Drawn for CRM in the `@wordpress/icons` style: 1.5px lines, artwork inside the 24px grid's padding. |
| `logout.svg` | Log out. `@wordpress/icons` only has `login`. | Drawn for CRM, same style. |
| `paypal.svg` | PayPal mark, shown in PayPal blue `#002991`. | [Simple Icons](https://simpleicons.org) 16.32.0 (CC0-1.0), scaled to 18px inside the grid. |
| `stripe.svg` | Stripe mark, shown in Stripe purple `#635BFF`. | Simple Icons 16.32.0 (CC0-1.0), scaled the same way. |
| `envato.svg` | Envato mark, shown in Envato green `#87E64B`. CRM used Font Awesome's Envira leaf as a look-alike. | Simple Icons 16.32.0 (CC0-1.0), scaled the same way. |

The brand marks are the companies' trademarks. CRM shows them only to say where a record came from, as the Simple Icons disclaimer describes.
