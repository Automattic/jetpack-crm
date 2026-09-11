# Extension logos

The five marks shown in the setup wizard's Entrepreneur Bundle banner
(step 2, "Getting Contacts into your CRM"). They replaced a single composite
PNG, `entrepreneur-bundle.png`, whose logos had gone out of date.

These are third-party trademarks, reproduced to identify the services the CRM
integrates with. Each remains the property of its owner. This file records
where each one came from so the claim is checkable rather than asserted.

## Files

| File | Retrieved | sha256 |
| --- | --- | --- |
| `woo-logo.svg` | 2026-09-11 | `3ec4eb7f7ba69184d499f83a93b5935be46c4ae23c41a38f62c8ae51b5de1dc0` |
| `twilio-logo.svg` | 2026-09-11 | `eb88fab70c8c1c26f5e3e798c8caebdb31ff9f6ecc8cd9062a27e88fd50a6e67` |
| `stripe-logo.svg` | 2026-09-11 | `4448c4b4f954285d2b2aeb6d92391c85fdc290e008c2679d2c006d6d72ae1ae9` |
| `gravity-forms-logo.svg` | 2026-09-11 | `19744835f6ef4bb5025ffdd86f9b3e58457738f16f2f22a74c853f1c259688e8` |
| `paypal-logo@2x.png` | 2026-09-11 | `7b97b6e60de33cc64d971be53edbe0ddeccc12929a76425fbc975f8b4109c02f` |

## Sources

### woo-logo.svg — verified

- Guidelines: <https://woocommerce.com/brand-and-logo-guidelines/>
- Archive: <https://woocommerce.com/wp-content/uploads/2025/01/woo-logos.zip>
  (sha256 `a59ff90335d8ca3d13b53b93630445a8aba3e187869245adad80c3ac52628842`)
- Byte-identical to `Woo Logos/Woo_logo_color.svg` inside that archive.

### twilio-logo.svg — verified

- Served directly by <https://www.twilio.com/content/dam/twilio-com/core-assets/customer-logos/t-z/twilio.svg>
- Byte-identical to what that URL serves.

### stripe-logo.svg — verified

- Newsroom: <https://stripe.com/newsroom/information>
- Archive: <https://assets.stripeassets.com/fzn2n1nzq965/7q0dJGs6fRS1LRmMpChoAF/87def4edfbb7fd5aef4ab9baf904b2db/Stripe_logo_kit.zip>
  (sha256 `30f4308c21a8f6c3fe603354ce69222fd498afeb80f54fda193fb7291208906f`)
- Byte-identical to `asset-wordmark/Stripe wordmark - Blurple.svg` inside it.

### gravity-forms-logo.svg — not independently verified

- Brand assets: <https://www.gravityforms.com/brand-assets/>, which hands off to
  a Lingo workspace at
  <https://www.lingoapp.com/70269/k/Gravity-Forms-Brand-Guidelines-RJ91Nz>
- Lingo does not expose a stable direct download URL.

### paypal-logo@2x.png — verified, then derived

- Media resources: <https://newsroom.paypal-corp.com/media-resources>
- Archive: <https://newsroom.paypal-corp.com/download/PayPal-Logo-Black-2024.zip>
  (sha256 `3aa7619157c7b1aa333134a4683be83497e470788d82c2778d817c1c32f1768b`)
- That archive contains `Logo - Black/PayPal-Logo-Black-RGB.png` at 2497x839,
  sha256 `181ce9ae4fa6205a2875c17007f0b9dcc146dcd52ea21911037c5b1d74482f99`.
- The shipped file is that image downscaled to 320x107:

  ```
  sips -Z 320 PayPal-Logo-Black-RGB.png --out paypal-logo@2x.png
  ```

  PayPal only provides a raster image. The original size (2497x839) is too
  large for our use case (~80px wide), so we use a scaled down image.

## Re-verifying

Fetch an archive above, extract it, and `cmp` the file against the one shipped
here. Four of the five should be byte-identical; `paypal-logo@2x.png` should
reproduce from the `sips` command. Any mismatch means either the vendor has
published a new version of their mark, or the file here has been altered.
