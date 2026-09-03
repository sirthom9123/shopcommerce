#!/usr/bin/env python3
"""
Convert Yehuda Solutions spreadsheet into a WooCommerce-compatible CSV.
"""

from __future__ import annotations

import argparse
import csv
import json
import re
import xml.etree.ElementTree as ET
import zipfile
from dataclasses import dataclass
from pathlib import Path
from typing import Dict, List, Tuple

MAIN_NS = "{http://schemas.openxmlformats.org/spreadsheetml/2006/main}"
REL_NS = "{http://schemas.openxmlformats.org/package/2006/relationships}"
DOC_REL_NS = "{http://schemas.openxmlformats.org/officeDocument/2006/relationships}"


def col_to_index(col_ref: str) -> int:
	"""Convert Excel column letters to zero-based index."""
	idx = 0
	for char in col_ref:
		if not char.isalpha():
			break
		idx = idx * 26 + (ord(char.upper()) - 64)
	return idx - 1


def parse_price(value: str) -> str:
	clean = re.sub(r"[^0-9.]", "", value or "")
	if not clean:
		return ""
	try:
		return f"{float(clean):.2f}"
	except ValueError:
		return ""


def normalize_category(raw: str) -> str:
	value = (raw or "").strip().lower()
	mapping = {
		"power / ups": "Power > UPS",
		"power protection": "Power > Protection",
		"portable power": "Power > Portable Power",
		"small backup power": "Power > Backup Power",
		"lighting": "Power > Lighting",
		"security / cctv": "Security > CCTV",
		"cctv": "Security > CCTV",
		"nvr": "Security > NVR",
		"smart home security": "Security > Smart Home",
		"audio": "Electronics > Audio",
		"display": "Electronics > Displays",
		"peripherals": "Peripherals",
		"storage": "Accessories > Storage",
		"mobile accessories": "Accessories > Mobile",
		"bags": "Accessories > Bags",
		"cleaning": "Home > Cleaning",
	}
	return mapping.get(value, (raw or "Uncategorized").strip())


@dataclass
class WorkbookData:
	sheets: Dict[str, List[List[str]]]


class XlsxReader:
	def __init__(self, xlsx_path: Path):
		self.xlsx_path = xlsx_path
		self.shared_strings: List[str] = []
		self.sheet_targets: Dict[str, str] = {}

	def load(self) -> WorkbookData:
		with zipfile.ZipFile(self.xlsx_path, "r") as zf:
			self.shared_strings = self._read_shared_strings(zf)
			self.sheet_targets = self._read_sheet_targets(zf)
			workbook_sheets = self._read_workbook_sheets(zf)

			all_rows: Dict[str, List[List[str]]] = {}
			for name, rel_id in workbook_sheets:
				target = self.sheet_targets.get(rel_id)
				if not target:
					continue
				xml_path = target.replace("\\", "/").lstrip("/")
				if not xml_path.startswith("xl/"):
					xml_path = f"xl/{xml_path}"
				all_rows[name] = self._read_sheet_rows(zf, xml_path)
			return WorkbookData(sheets=all_rows)

	def _read_shared_strings(self, zf: zipfile.ZipFile) -> List[str]:
		if "xl/sharedStrings.xml" not in zf.namelist():
			return []
		root = ET.fromstring(zf.read("xl/sharedStrings.xml"))
		values: List[str] = []
		for si in root.findall(f"{MAIN_NS}si"):
			text_chunks = [node.text or "" for node in si.findall(f".//{MAIN_NS}t")]
			values.append("".join(text_chunks))
		return values

	def _read_sheet_targets(self, zf: zipfile.ZipFile) -> Dict[str, str]:
		rels = ET.fromstring(zf.read("xl/_rels/workbook.xml.rels"))
		output: Dict[str, str] = {}
		for rel in rels.findall(f"{REL_NS}Relationship"):
			rel_id = rel.attrib.get("Id", "")
			target = rel.attrib.get("Target", "")
			if rel_id and target:
				output[rel_id] = target
		return output

	def _read_workbook_sheets(self, zf: zipfile.ZipFile) -> List[Tuple[str, str]]:
		root = ET.fromstring(zf.read("xl/workbook.xml"))
		sheets = []
		for sheet in root.findall(f".//{MAIN_NS}sheet"):
			name = sheet.attrib.get("name", "")
			rel_id = sheet.attrib.get(f"{DOC_REL_NS}id", "")
			if name and rel_id:
				sheets.append((name, rel_id))
		return sheets

	def _read_sheet_rows(self, zf: zipfile.ZipFile, xml_path: str) -> List[List[str]]:
		if xml_path not in zf.namelist():
			return []
		root = ET.fromstring(zf.read(xml_path))
		rows: List[List[str]] = []
		for row in root.findall(f".//{MAIN_NS}row"):
			parsed: Dict[int, str] = {}
			max_col = -1
			for cell in row.findall(f"{MAIN_NS}c"):
				ref = cell.attrib.get("r", "")
				col_letters = "".join(ch for ch in ref if ch.isalpha())
				col_idx = col_to_index(col_letters) if col_letters else max_col + 1
				max_col = max(max_col, col_idx)
				parsed[col_idx] = self._cell_value(cell)
			if max_col >= 0:
				rows.append([parsed.get(i, "").strip() for i in range(max_col + 1)])
		return rows

	def _cell_value(self, cell: ET.Element) -> str:
		cell_type = cell.attrib.get("t")
		value_node = cell.find(f"{MAIN_NS}v")
		if value_node is None:
			inline_node = cell.find(f"{MAIN_NS}is/{MAIN_NS}t")
			return (inline_node.text or "") if inline_node is not None else ""
		raw = value_node.text or ""
		if cell_type == "s":
			try:
				return self.shared_strings[int(raw)]
			except (ValueError, IndexError):
				return ""
		return raw


def to_woo_rows(catalog_rows: List[List[str]]) -> Tuple[List[Dict[str, str]], Dict[str, str]]:
	if not catalog_rows:
		raise ValueError("Product Catalog sheet is empty.")

	header = [h.strip() for h in catalog_rows[0]]
	header_map = {name: idx for idx, name in enumerate(header)}
	required = [
		"Product Name",
		"Brand",
		"Category",
		"Supplier",
		"Dealer Price (excl. VAT)",
		"Est. Retail Price (incl. VAT)",
		"Notes",
	]
	missing = [col for col in required if col not in header_map]
	if missing:
		raise ValueError(f"Missing required columns: {', '.join(missing)}")

	woo_rows: List[Dict[str, str]] = []
	category_map: Dict[str, str] = {}
	position = 1

	for row in catalog_rows[1:]:
		if not any(cell.strip() for cell in row):
			continue

		def get(name: str) -> str:
			idx = header_map[name]
			return row[idx].strip() if idx < len(row) else ""

		name = get("Product Name")
		if not name:
			continue

		brand = get("Brand")
		supplier = get("Supplier")
		original_category = get("Category")
		normalized_category = normalize_category(original_category)
		category_map[original_category] = normalized_category

		retail_price = parse_price(get("Est. Retail Price (incl. VAT)"))
		dealer_price = parse_price(get("Dealer Price (excl. VAT)"))
		notes = get("Notes")

		short_description_parts = [f"Brand: {brand}" if brand else "", f"Supplier: {supplier}" if supplier else ""]
		short_description = " | ".join(part for part in short_description_parts if part)

		woo_rows.append(
			{
				"Type": "simple",
				"SKU": "",
				"Name": name,
				"Published": "1",
				"Is featured?": "0",
				"Visibility in catalog": "visible",
				"Short description": short_description,
				"Description": notes,
				"Tax status": "taxable",
				"In stock?": "1",
				"Regular price": retail_price,
				"Categories": normalized_category,
				"Position": str(position),
				"Attribute 1 name": "Brand",
				"Attribute 1 value(s)": brand,
				"Attribute 1 visible": "1",
				"Attribute 1 global": "1",
				"Attribute 2 name": "Supplier",
				"Attribute 2 value(s)": supplier,
				"Attribute 2 visible": "0",
				"Attribute 2 global": "1",
				"Meta:dealer_price_excl_vat": dealer_price,
			}
		)
		position += 1

	return woo_rows, category_map


def write_csv(rows: List[Dict[str, str]], output_file: Path) -> None:
	output_file.parent.mkdir(parents=True, exist_ok=True)
	fieldnames = [
		"Type",
		"SKU",
		"Name",
		"Published",
		"Is featured?",
		"Visibility in catalog",
		"Short description",
		"Description",
		"Tax status",
		"In stock?",
		"Regular price",
		"Categories",
		"Position",
		"Attribute 1 name",
		"Attribute 1 value(s)",
		"Attribute 1 visible",
		"Attribute 1 global",
		"Attribute 2 name",
		"Attribute 2 value(s)",
		"Attribute 2 visible",
		"Attribute 2 global",
		"Meta:dealer_price_excl_vat",
	]
	with output_file.open("w", encoding="utf-8", newline="") as handle:
		writer = csv.DictWriter(handle, fieldnames=fieldnames)
		writer.writeheader()
		writer.writerows(rows)


def write_category_mapping(mapping: Dict[str, str], output_file: Path) -> None:
	ordered = dict(sorted(mapping.items(), key=lambda kv: kv[0].lower()))
	output_file.parent.mkdir(parents=True, exist_ok=True)
	with output_file.open("w", encoding="utf-8") as handle:
		json.dump(ordered, handle, indent=2, ensure_ascii=True)


def main() -> int:
	parser = argparse.ArgumentParser(description="Create WooCommerce CSV from Yehuda XLSX")
	parser.add_argument("--input", required=True, help="Path to source XLSX file")
	parser.add_argument("--output", required=True, help="Path to output Woo CSV")
	parser.add_argument(
		"--category-map-output",
		required=True,
		help="Path to output category normalization map JSON",
	)
	args = parser.parse_args()

	reader = XlsxReader(Path(args.input))
	workbook = reader.load()
	catalog = workbook.sheets.get("Product Catalog")
	if not catalog:
		raise ValueError("Sheet 'Product Catalog' not found.")

	woo_rows, category_map = to_woo_rows(catalog)
	write_csv(woo_rows, Path(args.output))
	write_category_mapping(category_map, Path(args.category_map_output))

	print(f"Rows exported: {len(woo_rows)}")
	print(f"CSV: {args.output}")
	print(f"Category map: {args.category_map_output}")
	return 0


if __name__ == "__main__":
	raise SystemExit(main())

