#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Batch postcard generator for 321 Sites.

Reads the marketing CSV and writes a front + back A5 PDF for every row, using
postcard_template.py. Field mapping (CSV column -> postcard arg):

    business_name   -> --business-name      (headline + back body)
    city            -> --city               (back eyebrow)
    places_id       -> screenshot + QR URLs (see below)
    (derived slug)  -> --subdomain          (front browser URL)
    (derived)       -> --short-url          (back "or type:" line)

Per-row URLs (built here, fetched by postcard_template.fetch_image):

    screenshot_url = https://321sites.com/screenshots/{places_id}.png
    site_url       = https://321sites.com/go/{unique_code}
    qr_url         = https://api.qrserver.com/v1/create-qr-code/?size=600x600
                     &margin=0&data={url-encoded site_url}

Output layout (one folder per business):

    out/0013_hair-candy/front.pdf
    out/0013_hair-candy/back.pdf

Usage:
    pip install reportlab
    python generate_batch.py --csv uploads/321sites_marketing.csv --out out

    # only rows still pending, first 10, with Inter fonts:
    python generate_batch.py --csv uploads/321sites_marketing.csv --out out \
        --status pending --limit 10 --font-dir fonts

Re-runs skip rows whose PDFs already exist unless you pass --overwrite.
"""

import argparse
import csv
import os
import sys
import urllib.parse

import postcard_template as pt

SCREENSHOT_BASE = "https://321sites.com/screenshots/{places_id}.png"
SITE_BASE = "https://321sites.com/go/{unique_code}"
QR_BASE = ("https://api.qrserver.com/v1/create-qr-code/"
           "?size=600x600&margin=0&data={data}")


def build_urls(row):
    places_id = (row.get("places_id") or "").strip()
    unique_code = (row.get("unique_code") or "").strip()
    slug = pt.slugify(row.get("business_name") or "")
    site_url = SITE_BASE.format(unique_code=unique_code)
    return {
        "slug": slug,
        "site_domain": site_url.replace("https://", ""),       # front URL pill
        "short_url": site_url.replace("https://", ""),          # back "or type:"
        "screenshot_url": SCREENSHOT_BASE.format(places_id=places_id),
        "qr_url": QR_BASE.format(data=urllib.parse.quote(site_url, safe="")),
    }


def main():
    ap = argparse.ArgumentParser(description="Batch-generate 321 Sites postcards from a CSV.")
    ap.add_argument("--csv", required=True)
    ap.add_argument("--out", default="out")
    ap.add_argument("--font-dir", default=os.environ.get("INTER_FONT_DIR", "fonts"))
    ap.add_argument("--status", default=None,
                    help="Only process rows whose 'status' column equals this (e.g. pending).")
    ap.add_argument("--limit", type=int, default=None,
                    help="Process at most N matching rows (handy for a test batch).")
    ap.add_argument("--overwrite", action="store_true",
                    help="Re-render rows even if their PDFs already exist.")
    ap.add_argument("--guides", action="store_true",
                    help="Draw trim/safe guides (proofing only).")
    ap.add_argument("--continue-on-error", action="store_true", default=True,
                    help="Keep going if one row fails (e.g. missing screenshot). Default on.")
    args = ap.parse_args()

    pt.register_fonts(args.font_dir)
    os.makedirs(args.out, exist_ok=True)

    with open(args.csv, newline="", encoding="utf-8") as f:
        rows = list(csv.DictReader(f))

    if args.status:
        rows = [r for r in rows if (r.get("status") or "").strip() == args.status]
    if args.limit:
        rows = rows[:args.limit]

    total = len(rows)
    ok = skipped = failed = 0
    failures = []
    print("Processing %d row(s) -> %s/\n" % (total, args.out))

    for i, row in enumerate(rows, 1):
        rid = (row.get("id") or str(i)).strip()
        name = (row.get("business_name") or "").strip()
        u = build_urls(row)
        folder = os.path.join(args.out, "%04d_%s" % (int(rid) if rid.isdigit() else i, u["slug"]))
        front_pdf = os.path.join(folder, "front.pdf")
        back_pdf = os.path.join(folder, "back.pdf")

        tag = "[%d/%d] %s" % (i, total, name)
        if not args.overwrite and os.path.exists(front_pdf) and os.path.exists(back_pdf):
            print("  %s — skipped (exists)" % tag)
            skipped += 1
            continue

        os.makedirs(folder, exist_ok=True)
        tmp = []
        try:
            shot = pt.fetch_image(u["screenshot_url"])
            tmp.append(shot)
        except Exception as e:
            shot = None
            print("  %s — WARNING no screenshot (%s); drawing placeholder" % (tag, e))
        try:
            qr = pt.fetch_image(u["qr_url"])
            tmp.append(qr)
        except Exception as e:
            qr = None
            print("  %s — WARNING no QR (%s)" % (tag, e))

        try:
            pt.render_front(front_pdf, name, u["site_domain"], shot, qr, args.guides)
            pt.render_back(back_pdf, name, (row.get("city") or "").strip(),
                           u["short_url"], qr, args.guides)
            print("  %s — OK" % tag)
            ok += 1
        except Exception as e:
            print("  %s — FAILED: %s" % (tag, e))
            failed += 1
            failures.append((rid, name, str(e)))
            if not args.continue_on_error:
                _cleanup(tmp)
                sys.exit(1)
        finally:
            _cleanup(tmp)

    print("\nDone. %d ok, %d skipped, %d failed (of %d)." % (ok, skipped, failed, total))
    if failures:
        print("Failures:")
        for rid, name, err in failures:
            print("  id=%s %s -> %s" % (rid, name, err))


def _cleanup(paths):
    for p in paths:
        try:
            os.remove(p)
        except OSError:
            pass


if __name__ == "__main__":
    main()
