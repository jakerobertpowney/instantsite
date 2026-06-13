#!/usr/bin/env python3
"""
gen_postcard.py — Generate a single-page Stannp postcard PDF.

Usage:
  python3 gen_postcard.py --side=front \
      --business-name="Hair Candy" \
      --subdomain="hair-candy.321sites.com" \
      --screenshot-url=https://321sites.com/screenshots/{id}.png \
      --qr-url=https://api.qrserver.com/...

  python3 gen_postcard.py --side=back \
      --business-name="Hair Candy" \
      --city="Stockport" \
      --qr-url=https://api.qrserver.com/... \
      --short-url=321sites.com/claim/{id}

Output: raw PDF bytes written to stdout.
"""

import sys
import argparse
import tempfile
import os
import urllib.request

from reportlab.lib.pagesizes import A5
from reportlab.pdfgen import canvas as rl_canvas
from reportlab.lib.colors import HexColor, white
from reportlab.lib.units import mm
from reportlab.platypus import Paragraph
from reportlab.lib.styles import ParagraphStyle

W, H = A5   # 419.53 × 595.28 pts (148 × 210 mm)

# ── Palette ───────────────────────────────────────────────────────────────────
BG_GREY = HexColor('#f0f1f3')
DARK    = HexColor('#111827')
MID     = HexColor('#374151')
MUTED   = HexColor('#6b7280')
LIGHT   = HexColor('#9ca3af')
BLUE    = HexColor('#1e66f5')
BORDER  = HexColor('#e2e8f0')
BAR_BG  = HexColor('#f3f4f6')
DOT_COL = HexColor('#d1d5db')
CONTENT = HexColor('#f9fafb')


def fetch_image(url: str, suffix: str = '.png') -> str | None:
    """Download a URL to a temp file. Returns path, or None on failure."""
    try:
        tmp = tempfile.NamedTemporaryFile(delete=False, suffix=suffix)
        req = urllib.request.Request(url, headers={'User-Agent': '321Sites-Mailer/1.0'})
        with urllib.request.urlopen(req, timeout=12) as resp:
            tmp.write(resp.read())
        tmp.close()
        return tmp.name
    except Exception:
        return None


def draw_logo(c, x, y, size=10):
    """3•2•1 sites logo."""
    dot_r = size * 0.18
    gap   = dot_r * 4.5
    cx = x
    for i, d in enumerate(['3', '2', '1']):
        c.setFillColor(DARK)
        c.setFont("Helvetica-Bold", size)
        c.drawString(cx, y, d)
        cx += c.stringWidth(d, "Helvetica-Bold", size)
        if i < 2:
            c.setFillColor(BLUE)
            c.circle(cx + dot_r + 1.5, y + size * 0.38, dot_r, fill=1, stroke=0)
            cx += gap
    c.setFillColor(DARK)
    c.setFont("Helvetica-Bold", size)
    c.drawString(cx + 3, y, "sites")


def embed_clipped(c, img_path, x, y, w, h, anchor='n'):
    """Draw an image clipped to a rectangle, anchored to the top."""
    c.saveState()
    p = c.beginPath()
    p.rect(x, y, w, h)
    c.clipPath(p, stroke=0, fill=0)
    c.drawImage(
        img_path, x, y, width=w, height=h,
        preserveAspectRatio=True, anchor=anchor, mask='auto',
    )
    c.restoreState()


# ── FRONT PAGE ────────────────────────────────────────────────────────────────

def draw_front(c, business_name: str, subdomain: str, screenshot_url: str, qr_url: str):
    margin  = 12 * mm
    avail_w = W - 2 * margin

    # Background
    c.setFillColor(BG_GREY)
    c.rect(0, 0, W, H, fill=1, stroke=0)

    y = H - 13 * mm   # cursor from top

    # ── Headline: "We built [Name] a website." ────────────────────────────────
    # Keep "We built" + business name together, then "a website." on final line
    headline_style = ParagraphStyle(
        'h1',
        fontName='Helvetica-Bold',
        fontSize=34,
        leading=40,
        textColor=DARK,
    )
    headline = Paragraph(f"We built {business_name}<br/>a website.", headline_style)
    hw, hh = headline.wrap(avail_w, 300)
    headline_y = y - hh
    headline.drawOn(c, margin, headline_y)
    y = headline_y - 6 * mm

    # ── Subtitle ──────────────────────────────────────────────────────────────
    c.setFillColor(MUTED)
    c.setFont("Helvetica", 12)
    c.drawString(margin, y, "It’s free to claim.")
    y -= 7 * mm

    # ── Browser mockup ────────────────────────────────────────────────────────
    strip_h = 44 * mm    # bottom strip height reserved
    bar_h   = 9.5 * mm
    mx = margin
    mw = avail_w
    my = strip_h
    mh = y - my          # fills from bottom strip to subtitle
    r  = 8

    # Shadow
    c.setFillColor(HexColor('#d1d5db'))
    c.roundRect(mx + 1.5, my - 1.5, mw, mh, r, fill=1, stroke=0)

    # White frame
    c.setFillColor(white)
    c.setStrokeColor(BORDER)
    c.setLineWidth(0.5)
    c.roundRect(mx, my, mw, mh, r, fill=1, stroke=1)

    # Top bar (rounded top, square bottom half)
    c.setFillColor(BAR_BG)
    c.roundRect(mx, my + mh - bar_h, mw, bar_h, r, fill=1, stroke=0)
    c.rect(mx, my + mh - bar_h, mw, bar_h / 2, fill=1, stroke=0)

    # 3 dots
    dot_cy = my + mh - bar_h / 2
    for dx in [mx + 6 * mm, mx + 10 * mm, mx + 14 * mm]:
        c.setFillColor(DOT_COL)
        c.circle(dx, dot_cy, 1.8, fill=1, stroke=0)

    # URL pill
    pill_w = mw - 20 * mm
    pill_x = mx + 17 * mm
    pill_y = my + mh - bar_h + 1.5 * mm
    pill_h = 6.5 * mm
    c.setFillColor(white)
    c.roundRect(pill_x, pill_y, pill_w, pill_h, 3, fill=1, stroke=0)

    # Subdomain in URL bar
    url_label = subdomain if subdomain else "321sites.com"
    c.setFillColor(MUTED)
    c.setFont("Helvetica", 6.5)
    tw = c.stringWidth(url_label, "Helvetica", 6.5)
    c.drawString(pill_x + (pill_w - tw) / 2, pill_y + 2 * mm, url_label)

    # Content area — grey background
    content_h = mh - bar_h
    c.setFillColor(CONTENT)
    c.rect(mx, my, mw, content_h, fill=1, stroke=0)

    # Embed screenshot (clipped, anchored to top of content area)
    ss_path = fetch_image(screenshot_url, '.png')
    if ss_path:
        try:
            embed_clipped(c, ss_path, mx, my, mw, content_h, anchor='n')
        finally:
            os.unlink(ss_path)
    else:
        c.setFillColor(LIGHT)
        c.setFont("Helvetica-Oblique", 8)
        c.drawCentredString(mx + mw / 2, my + content_h / 2 - 4, "preview unavailable")

    # ── Bottom strip ──────────────────────────────────────────────────────────
    qr_size = 22 * mm
    qr_x    = margin
    qr_y    = (strip_h - qr_size) / 2 + 1 * mm

    qr_path = fetch_image(qr_url, '.png')
    if qr_path:
        try:
            c.setFillColor(white)
            c.setStrokeColor(BORDER)
            c.setLineWidth(0.5)
            c.roundRect(qr_x, qr_y, qr_size, qr_size, 3, fill=1, stroke=1)
            c.drawImage(
                qr_path, qr_x + 1 * mm, qr_y + 1 * mm,
                width=qr_size - 2 * mm, height=qr_size - 2 * mm,
                preserveAspectRatio=True, mask='auto',
            )
        finally:
            os.unlink(qr_path)

    tx    = qr_x + qr_size + 5 * mm
    mid_y = qr_y + qr_size / 2
    c.setFillColor(DARK)
    c.setFont("Helvetica-Bold", 11)
    c.drawString(tx, mid_y + 3 * mm, "See your site live")
    c.setFillColor(MUTED)
    c.setFont("Helvetica", 8)
    c.drawString(tx, mid_y - 3.5 * mm, "Scan, or turn over for details")

    draw_logo(c, W - 49 * mm, mid_y - 4, size=10)


# ── BACK PAGE ─────────────────────────────────────────────────────────────────

def draw_back(c, business_name: str, city: str, qr_url: str, short_url: str):
    margin  = 14 * mm
    avail_w = W - 2 * margin

    c.setFillColor(white)
    c.rect(0, 0, W, H, fill=1, stroke=0)

    y = H - 14 * mm   # cursor from top

    # ── Eyebrow "[BUSINESS NAME] · [CITY]" in blue ───────────────────────────
    eyebrow_parts = [business_name.upper()]
    if city:
        eyebrow_parts.append(city.upper())
    eyebrow = "  ·  ".join(eyebrow_parts)
    c.setFillColor(BLUE)
    c.setFont("Helvetica-Bold", 8)
    c.drawString(margin, y, eyebrow)
    y -= 8 * mm

    # ── Heading "It's free to claim" ─────────────────────────────────────────
    heading_style = ParagraphStyle(
        'heading',
        fontName='Helvetica-Bold',
        fontSize=46,
        leading=52,
        textColor=DARK,
    )
    heading = Paragraph("It’s free to claim", heading_style)
    hw, hh = heading.wrap(avail_w, 300)
    heading.drawOn(c, margin, y - hh)
    y -= hh + 8 * mm

    # ── Body paragraph (personalised) ────────────────────────────────────────
    body_text = (
        f"We’ve built a free one-page website for {business_name} — your "
        "photos, services, opening hours and reviews, all in one link "
        "your customers can find. Scan the code to see it. If you like "
        "it, claiming it takes two minutes. If not, bin this card — "
        "no hard feelings."
    )
    body_style = ParagraphStyle(
        'body',
        fontName='Helvetica',
        fontSize=13.5,
        leading=20,
        textColor=MID,
    )
    body = Paragraph(body_text, body_style)
    bw, bh = body.wrap(avail_w, 400)
    body.drawOn(c, margin, y - bh)
    y -= bh + 10 * mm

    # ── QR + URL section ──────────────────────────────────────────────────────
    qr_size = 36 * mm
    qr_x    = margin
    qr_y    = y - qr_size

    qr_path = fetch_image(qr_url, '.png')
    if qr_path:
        try:
            c.setFillColor(white)
            c.setStrokeColor(BORDER)
            c.setLineWidth(0.6)
            c.roundRect(qr_x, qr_y, qr_size, qr_size, 4, fill=1, stroke=1)
            c.drawImage(
                qr_path, qr_x + 1.5 * mm, qr_y + 1.5 * mm,
                width=qr_size - 3 * mm, height=qr_size - 3 * mm,
                preserveAspectRatio=True, mask='auto',
            )
        finally:
            os.unlink(qr_path)

    # "or type:" + short URL
    tx2   = qr_x + qr_size + 7 * mm
    top_y = qr_y + qr_size

    c.setFillColor(MUTED)
    c.setFont("Helvetica", 9)
    c.drawString(tx2, top_y - 4 * mm, "or type:")

    c.setFillColor(DARK)
    c.setFont("Helvetica-Bold", 13.5)
    c.drawString(tx2, top_y - 12 * mm, short_url)

    c.setFillColor(MUTED)
    c.setFont("Helvetica", 8.5)
    c.drawString(tx2, top_y - 20 * mm, "Free to claim  ·  No card needed")
    c.drawString(tx2, top_y - 27 * mm, "Premium features from £9.99/mo")

    # ── Footer ────────────────────────────────────────────────────────────────
    c.setFillColor(LIGHT)
    c.setFont("Helvetica", 6)
    for i, line in enumerate([
        "This preview was built from publicly available business information.",
        "Not interested? Scan and tap ‘Not interested’ to remove it. "
        "321 Sites Ltd  ·  hello@321sites.com",
    ]):
        c.drawString(margin, 16 * mm - i * 7.5, line)

    draw_logo(c, W - 49 * mm, 11 * mm, size=10)


# ── Entry point ───────────────────────────────────────────────────────────────

def main():
    parser = argparse.ArgumentParser(description='Generate a Stannp postcard page PDF.')
    parser.add_argument('--side', choices=['front', 'back'], required=True)
    parser.add_argument('--business-name', default='Your Business')
    parser.add_argument('--city', default='')
    parser.add_argument('--subdomain', default='')
    parser.add_argument('--screenshot-url', default='')
    parser.add_argument('--qr-url', required=True)
    parser.add_argument('--short-url', default='')
    args = parser.parse_args()

    buf = tempfile.NamedTemporaryFile(delete=False, suffix='.pdf')
    buf.close()

    c = rl_canvas.Canvas(buf.name, pagesize=A5)

    if args.side == 'front':
        draw_front(c, args.business_name, args.subdomain, args.screenshot_url, args.qr_url)
    else:
        draw_back(c, args.business_name, args.city, args.qr_url, args.short_url)

    c.showPage()
    c.save()

    with open(buf.name, 'rb') as f:
        sys.stdout.buffer.write(f.read())
    os.unlink(buf.name)


if __name__ == '__main__':
    main()
