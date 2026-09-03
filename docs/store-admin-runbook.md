# QR Minimal Store Admin Runbook

## 1) Initial setup checklist

1. In WordPress admin, install and activate WooCommerce.
2. Activate plugin `QR Store Bootstrap`.
3. Activate theme `QR Minimal Store`.
4. In WooCommerce setup, confirm:
   - Currency: `ZAR`
   - Pages: Shop, Cart, Checkout, My Account
   - Payment methods set to sandbox/test mode first
5. In Settings -> Permalinks, confirm `Post name` is active.

## 2) Product import from Yehuda spreadsheet

### Source file
- `C:\Users\USER\Downloads\Yehuda_Solutions_Competitive_Analysis.xlsx`

### Generate WooCommerce CSV
Run from project root:

```bash
python tools/importers/yehuda_xlsx_to_woo_csv.py --input "C:\Users\USER\Downloads\Yehuda_Solutions_Competitive_Analysis.xlsx" --output "d:\Projects\QR\store\data\imports\yehuda-products-woo.csv" --category-map-output "d:\Projects\QR\store\data\imports\category-normalization-map.json"
```

### Import in WooCommerce
1. Go to WooCommerce -> Products -> Import.
2. Upload `data/imports/yehuda-products-woo.csv`.
3. Keep default field mapping where possible.
4. Confirm these columns are mapped:
   - `Name`, `Regular price`, `Categories`
   - `Attribute 1` (Brand), `Attribute 2` (Supplier)
   - `Meta:dealer_price_excl_vat`
5. Run import and review results for skipped/failed rows.

## 3) Catalog quality checks after import

1. Filter products by category and verify taxonomy names are consistent.
2. Open 10 random products and verify:
   - Product title and category
   - Retail price from spreadsheet
   - Brand and Supplier attributes
3. Search for products using header search to confirm discoverability.

## 4) Content updates for future spreadsheets

1. Replace source spreadsheet file with latest version.
2. Re-run the conversion command.
3. Review `category-normalization-map.json` for new/unknown categories.
4. Re-import Woo CSV with update mode enabled if SKUs are introduced later.

## 5) Pre-launch checks

1. Replace WordPress salts in `wp-config.php` with secure unique values.
2. Keep `WP_DEBUG` disabled in production.
3. Verify cart and checkout flows end-to-end with a test payment method.
4. Configure shipping zones and tax settings for live region rules.

