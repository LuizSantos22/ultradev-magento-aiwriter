# UltraDev AI Writer

OpenMage / Magento 1.x module for automatic product listing generation using Artificial Intelligence (Gemini and DeepSeek) with automatic API fallback.

---

## What it does

- Adds a **✨ Create with AI** button to every product edit page in the admin panel
- Given only the product name, the AI automatically generates:
  - **Product Name** — complete, SEO-optimized title
  - **Short Description** — with feature badges and `ultrd-*` HTML structure
  - **Long Description** — full HTML layout including: hero section, YouTube embed, feature cards, technical specifications, box contents, and import alert
  - **Meta Title**, **Meta Description**, **Meta Keywords**, **Meta Page Description**
- Uses an existing product as a **reference template** — the AI replicates the format, tone, and HTML structure, not the content
- **Preview modal** — review and edit all generated fields before applying
- **Automatic fallback** — if the primary API fails, the module silently retries with the secondary API
- Supports **Gemini (Google)** and **DeepSeek** — configurable which is primary

---

## Requirements

- OpenMage LTS or Magento 1.9+
- PHP 7.4 or higher
- cURL extension enabled
- At least one API key: [Gemini](https://aistudio.google.com/app/apikey) or [DeepSeek](https://platform.deepseek.com)
- [modman](https://github.com/colinmollenhour/modman) or [magento-composer-installer](https://github.com/magento-hackathon/magento-composer-installer)

---

## Installation

### Via modman

```bash
cd /var/www/html   # OpenMage root
modman clone https://github.com/LuizSantos22/ultradev-aiwriter
```

### Via Composer

Add to your `composer.json`:

```json
{
    "require": {
        "ultradev/magento-aiwriter": "*"
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/LuizSantos22/ultradev-aiwriter"
        }
    ]
}
```

Then run:

```bash
composer install
```

### Manual installation

Copy the following paths to your OpenMage root:

app/code/community/UltraDev/AIWriter   →   app/code/community/UltraDev/AIWriter
app/etc/modules/UltraDev_AIWriter.xml  →   app/etc/modules/UltraDev_AIWriter.xml
app/design/adminhtml/...               →   app/design/adminhtml/...
skin/adminhtml/...                     →   skin/adminhtml/...

After installing, clear the cache:

**Admin → System → Cache Management → Flush Magento Cache**

---

## Configuration

Go to **Admin → System → Configuration → Catalog → UltraDev AI Writer**

| Setting | Description |
|---|---|
| Enable AI Writer | Enables or disables the module |
| Primary API | Which API to call first (`Gemini` or `DeepSeek`) |
| Gemini API Key | Your Google AI Studio API key |
| DeepSeek API Key | Your DeepSeek platform API key |
| Default Reference Product ID | The product ID whose listing will be used as the format template |

> **Tip:** The reference product should be your best, most complete listing. The AI will replicate its HTML structure, writing style, and tone — not its content.

---

## How to use

1. Open any product in **Catalog → Manage Products → Edit**
2. Click the **✨ Create with AI** button next to the Name field
3. In the modal:
   - Confirm or edit the product name
   - Choose between the default reference product or search for another
4. Click **✨ Generate Listing**
5. Review the generated content across four tabs: **Basic**, **Short Description**, **Long Description**, **Meta Fields**
6. Edit any field directly in the preview if needed
7. Click **✔ Apply Fields** — all fields are populated in the product form
8. Review and click **Save** as usual

---

## How the fallback works

Request → Primary API (e.g. Gemini)
↓ fails (quota, timeout, error)
Secondary API (e.g. DeepSeek)
↓ fails
Error returned to admin UI
↓ logged to var/log/ultradev_aiwriter.log

Both APIs are called with the same prompt. The switch is silent and automatic.

---

## Generated HTML structure

The module generates HTML using the `ultrd-*` CSS classes from the [UltraDev theme](https://ultraeletronicos.com). The structure includes:

```html
<div class="ultrd-product-description">
    <div class="ultrd-hero"> ... </div>
    <div class="ultrd-section"> ... </div>
    <div class="ultrd-features">
        <div class="ultrd-feature-card"> ... </div>
        <div class="ultrd-feature-card"> ... </div>
        <div class="ultrd-feature-card"> ... </div>
    </div>
    <div class="ultrd-box"> <!-- Technical specs --> </div>
    <div class="ultrd-box"> <!-- Box contents + import alert --> </div>
</div>
```

The short description uses:

```html
<p class="ultrd-short-description"> ... </p>
<div class="ultrd-badges">
    <span>Badge 1</span>
    <span>Badge 2</span>
    <span>Badge 3</span>
</div>
```

These classes are styled by the `ultrd-product-description` CSS already present in the theme. No additional frontend CSS is required.

---

## File structure

ultradev-aiwriter/
├── composer.json
├── modman
├── README.md
├── app/
│   ├── etc/modules/
│   │   └── UltraDev_AIWriter.xml
│   └── code/community/UltraDev/AIWriter/
│       ├── etc/
│       │   ├── config.xml
│       │   └── system.xml
│       ├── Helper/
│       │   ├── Api.php          ← API calls + fallback logic
│       │   └── Prompt.php       ← Prompt builder
│       ├── Model/System/Config/Source/
│       │   └── Api.php          ← Dropdown source for primary API
│       └── controllers/Adminhtml/
│           └── AiwriterController.php   ← AJAX endpoints
├── app/design/adminhtml/default/default/
│   ├── layout/
│   │   └── ultradev_aiwriter.xml
│   └── template/ultradev/aiwriter/
│       └── modal.phtml          ← Modal UI + JS config object
└── skin/adminhtml/default/default/ultradev/aiwriter/
├── aiwriter.js              ← All frontend logic
└── aiwriter.css             ← Modal styles

---

## Logs

All API errors and fallback events are logged to:
var/log/ultradev_aiwriter.log

## License

MIT — see [LICENSE](LICENSE)

---

## Author

**UltraDev**
