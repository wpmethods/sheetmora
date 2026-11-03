# SheetMora documentation

Standalone HTML, CSS and JavaScript for https://wpmethods.com/sheetmora-documentation/ . No build tool, database, external font or CDN is required.

## Upload

1. Extract the ZIP. Keep the `sheetmora-documentation` folder and its `assets` directory together.
2. Back up any existing page or folder at the destination before replacing it.
3. Upload the folder to the document root of **wpmethods.com**, so `sheetmora-documentation/index.html` is inside that website. On a typical aaPanel layout the full path is `/www/wwwroot/wpmethods.com/sheetmora-documentation/index.html`; use the actual document root shown for your website.
4. Configure that directory to serve `index.html` as an index document if it does not already do so. Preserve existing site and SSL configuration.
5. Visit https://wpmethods.com/sheetmora-documentation/ and verify the stylesheet and JavaScript load. Check desktop/mobile navigation, search, copying an example and an FAQ link such as `#error-unexpected-response`.

This is a complete standalone HTML document. Do not paste the whole file into a WordPress Custom HTML block. If a WordPress page already uses the same slug, coordinate its route with this static directory; avoid serving a cached old page at that address. Purge only the affected page/CDN cache after upload when necessary.

The plugin's **Support & Connect → Documentation** link uses the public URL above. The public link becomes available once you upload the documentation; building the package does not publish it to your server.

## Files

- `index.html`: documentation content, semantic navigation and FAQ sections.
- `assets/sm-docs.css`: responsive Blue/Orange visual styles, focus states, reduced-motion and print styles.
- `assets/sm-docs.js`: local search, keyboard shortcut, code-copy controls, mobile navigation and reading position.
- `assets/sm-mark.svg`: local SheetMora documentation mark.
- `downloads/sm-google-connection-service-1.0.0.zip`: complete service source (index.php, src/class-sm-connection-service.php and SM-README.md). Preserve this download path when uploading the documentation.

The "Your own service" section includes an expandable code editor. Its tabs show the complete files with line numbers and individual Copy code buttons. With JavaScript disabled, all file panels remain readable. Source is escaped text inside index.html; no PHP files execute from the documentation folder. Users deploy the downloaded/exactly copied service files separately to their own HTTPS subdomain.

The release builder refreshes the editor from the canonical Pro connection-service source using scripts/sm-documentation-sources.ps1, then bundles the matching service ZIP under downloads/. Do not hand-edit the generated HTML between the SM-SERVICE-SOURCE markers; update the canonical service first and regenerate the documentation when that service changes.

Edit text in `index.html`. A section's `id` supplies its public deep link. `data-title` and `data-edition` label search results; `data-searchable` includes the section in the client-side index. Do not change IDs without updating incoming links. Keep help examples free of production keys, tokens and customer details.

All content remains readable without JavaScript. Search and code-copy controls are progressive enhancements. Local copy uses the Clipboard API where available and a selection/copy fallback otherwise. No search terms are sent to a server. External documentation and support links are opened only when selected.

The guide describes Free 1.0.0 and Pro 1.0.0. Recheck the relevant code and official Google guides when updating instructions; Google Console screens can change independently of the plugin.
