#!/usr/bin/env python3
"""
gen_postcard.py — Generate a single-page Stannp postcard PDF with real images.

Usage:
  python3 gen_postcard.py --side=front \\
      --screenshot-url=https://321sites.com/screenshots/{id}.png \\
      --qr-url=https://api.qrserver.com/... \\
      --short-url=321sites.com/claim/{id}

  python3 gen_postcard.py --side=back \\
      --qr-url=https://api.qrserver.com/... \\
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
from reportlab.lib.utils import ImageReader

W, H = A5  # 419.53 × 595.28 pts (148 × 210 mm)

# ── Palette ───────────────────────────────────────────────────────────────────
BG_GREY  = HexColor('#eceef1')
DARK     = HexColor('#111827')
MID      = HexColor('#374151')
MUTED    = HexColor('#6b7280')
LIGHT    = HexColor('#9ca3af')
BLUE     = HexColor('#1e66f5')
BORDER   = HexColor('#e5e7eb')
BAR_BG   = HexColor('#eef0f3')
DOT_GREY = HexColor('#cdd1d9')
CONTENT  = HexColor('#f3f4f6')


def fetch_image(url: str, suffix: str = '.png') -> str | None:
    """Download an image URL to a temp file. Returns path, or None on failure."""
    try:
        tmp = tempfile.NamedTemporaryFile(delete=False, suffix=suffix)
        req = urllib.request.Request(url, headers={'User-Agent': '321Sites-Mailer/1.0'})
        with urllib.request.urlopen(req, timeout=10) as resp:
            tmp.write(resp.read())
        tmp.close()
        return tmp.name
    except Exception:
        return None


def draw_logo(c, x, y, size=9):
    """Render the 3•2•1 sites logo."""
    dot_r = size * 0.20
    gap   = dot_r * 2 + 1.5
    c.setFont("Helvetica-Bold", size)
    c.setFillColor(DARK)
    cx = x
    for i, d in enumerate(['3', '2', '1']):
        c.drawString(cx, y, d)
        cx += c.stringWidth(d, "Helvetica-Bold", size)
        if i < 2:
            c.setFillColor(BLUE)
            c.circle(cx + dot_r, y + size * 0.38, dot_r, fill=1, stroke=0)
            cx += gap + dot_r * 2 + 1.5
            c.setFillColor(DARK)
    c.drawString(cx, y, "  sites")


def draw_browser_chrome(c, mx, my, mw, mh, bar_h=9*mm, radius=6):
    """Draw the browser frame (shadow, white card, top bar, dots, URL pill)."""
    # Shadow
    c.setFillColor(HexColor('#d1d5db'))
    c.roundRect(mx + 1.5, my - 1.5, mw, mh, radius, fill=1, stroke=0)
    # White frame
    c.setFillColor(white)
    c.setStrokeColor(BORDER)
    c.setLineWidth(0.5)
    c.roundRect(mx, my, mw, mh, radius, fill=1, stroke=1)
    # Top bar (rounded top, square bottom)
    c.setFillColor(BAR_BG)
    c.roundRect(mx, my + mh - bar_h, mw, bar_h, radius, fill=1, stroke=0)
    c.rect(mx, my + mh - bar_h, mw, bar_h / 2, fill=1, stroke=0)
    # 3 dots
    dot_y = my + mh - bar_h / 2
    for dx in [mx + 7*mm, mx + 11.5*mm, mx + 16*mm]:
        c.setFillColor(DOT_GREY)
        c.circle(dx, dot_y, 2, fill=1, stroke=0)
    # URL pill
    ub_x = mx + 21*mm
    ub_y = my + mh - bar_h + 1.8*mm
    c.setFillColor(white)
    c.roundRect(ub_x, ub_y, mw - 25*mm, 5.5*mm, 2.5, fill=1, stroke=0)


def embed_image_clipped(c, img_path, x, y, w, h):
    """Draw an image clipped to the given rectangle (no rounding needed here)."""
    c.saveState()
    p = c.beginPath()
    p.rect(x, y, w, h)
    c.clipPath(p, stroke=0, fill=0)
    c.drawImage(img_path, x, y, width=w, height=h,
                preserveAspectRatio=True, anchor='c', mask='auto')
    c.restoreState()


# ── FRONT PAGE ────────────────────────────────────────────────────────────────

def draw_front(c, screenshot_url: str, qr_url: str):
    margin = 15 * mm
    bar_h  = 9 * mm

    # Background
    c.setFillColor(BG_GREY)
    c.rect(0, 0, W, H, fill=1, stroke=0)

    # Subtitle
    c.setFillColor(MID)
    c.setFont("Helvetica", 10)
    c.drawString(margin, H - 38*mm, "It’s free to claim.")

    # Browser mockup dimensions
    mx = margin
    mw = W - 2 * margin
    mh = 115 * mm
    my = H - 38*mm - 4*mm - mh

    draw_browser_chrome(c, mx, my, mw, mh, bar_h)

    # Content area — grey fallback background
    content_h = mh - bar_h
    c.setFillColor(CONTENT)
    c.rect(mx, my, mw, content_h, fill=1, stroke=0)

    # Embed screenshot (clipped to content area)
    screenshot_path = fetch_image(screenshot_url, '.png')
    if screenshot_path:
        try:
            embed_image_clipped(c, screenshot_path, mx, my, mw, content_h)
        finally:
            os.unlink(screenshot_path)
    else:
        # Graceful fallback: grey placeholder
        c.setFillColor(LIGHT)
        c.setFont("Helvetica-Oblique", 8)
        c.drawCentredString(mx + mw / 2, my + content_h / 2 - 4, "preview unavailable")

    # Bottom strip
    strip_h = my
    qr_size = 22 * mm
    qr_y    = (strip_h - qr_size) / 2 + 1 * mm
    qr_x    = margin

    # QR code image
    qr_path = fetch_image(qr_url, '.png')
    if qr_path:
        try:
            c.setFillColor(white)
            c.setStrokeColor(BORDER)
            c.setLineWidth(0.6)
            c.roundRect(qr_x, qr_y, qr_size, qr_size, 3, fill=1, stroke=1)
            c.drawImage(qr_path, qr_x + 1*mm, qr_y + 1*mm,
                        width=qr_size - 2*mm, height=qr_size - 2*mm,
                        preserveAspectRatio=True, mask='auto')
        finally:
            os.unlink(qr_path)

    # Divider
    div_x = qr_x + qr_size + 5*mm
    c.setStrokeColor(BORDER)
    c.setLineWidth(0.4)
    c.line(div_x, qr_y + 2*mm, div_x, qr_y + qr_size - 2*mm)

    # Text
    tx    = div_x + 5*mm
    mid_y = qr_y + qr_size / 2
    c.setFillColor(DARK)
    c.setFont("Helvetica-Bold", 9.5)
    c.drawString(tx, mid_y + 2.5*mm, "See your site live")
    c.setFillColor(MUTED)
    c.setFont("Helvetica", 7.5)
    c.drawString(tx, mid_y - 3*mm, "Scan, or turn over for details")

    draw_logo(c, W - 43*mm, mid_y - 4, size=9)


# ── BACK PAGE ─────────────────────────────────────────────────────────────────

def draw_back(c, qr_url: str, short_url: str):
    margin = 15 * mm

    c.setFillColor(white)
    c.rect(0, 0, W, H, fill=1, stroke=0)

    # Heading
    c.setFillColor(DARK)
    c.setFont("Helvetica-Bold", 36)
    c.drawString(margin, H - 65*mm, "It’s free")
    c.drawString(margin, H - 65*mm - 42, "to claim")

    # Subtext
    c.setFillColor(MUTED)
    c.setFont("Helvetica", 9.5)
    sub_y = H - 65*mm - 42 - 16
    c.drawString(margin, sub_y,      "We built this from your Google Business listing.")
    c.drawString(margin, sub_y - 14, "Claim it in minutes — no credit card required.")

    # QR code
    qr2_size = 32 * mm
    qr2_x    = margin
    qr2_y    = 42 * mm

    qr_path = fetch_image(qr_url, '.png')
    if qr_path:
        try:
            c.setFillColor(white)
            c.setStrokeColor(BORDER)
            c.setLineWidth(0.6)
            c.roundRect(qr2_x, qr2_y, qr2_size, qr2_size, 3, fill=1, stroke=1)
            c.drawImage(qr_path, qr2_x + 1*mm, qr2_y + 1*mm,
                        width=qr2_size - 2*mm, height=qr2_size - 2*mm,
                        preserveAspectRatio=True, mask='auto')
        finally:
            os.unlink(qr_path)

    # URL block
    tx2 = qr2_x + qr2_size + 7*mm
    c.setFillColor(MUTED)
    c.setFont("Helvetica", 8)
    c.drawString(tx2, qr2_y + qr2_size - 3.5*mm, "or type:")

    c.setFillColor(DARK)
    c.setFont("Helvetica-Bold", 9.5)
    c.drawString(tx2, qr2_y + qr2_size - 14, short_url)

    c.setFillColor(MUTED)
    c.setFont("Helvetica", 7.5)
    c.drawString(tx2, qr2_y + 12*mm, "Free to claim  ·  No card needed")
    c.drawString(tx2, qr2_y + 5.5*mm, "Premium features from £9.99/mo")

    # Footer
    c.setFillColor(LIGHT)
    c.setFont("Helvetica", 6)
    for i, line in enumerate([
        "This preview was built from publicly available business information.",
        "Not interested? Scan and tap ‘Not interested’ to remove it.",
        "321 Sites Ltd  ·  hello@321sites.com",
    ]):
        c.drawString(margin, 27*mm - i * 7.5, line)

    draw_logo(c, W - 44*mm, 13*mm, size=9)


# ── Entry point ───────────────────────────────────────────────────────────────

def main():
    parser = argparse.ArgumentParser(description='Generate a Stannp postcard page PDF.')
    parser.add_argument('--side', choices=['front', 'back'], required=True)
    parser.add_argument('--screenshot-url', default='')
    parser.add_argument('--qr-url', required=True)
    parser.add_argument('--short-url', default='')
    args = parser.parse_args()

    buf = tempfile.NamedTemporaryFile(delete=False, suffix='.pdf')
    buf.close()

    c = rl_canvas.Canvas(buf.name, pagesize=A5)

    if args.side == 'front':
        draw_front(c, args.screenshot_url, args.qr_url)
    else:
        draw_back(c, args.qr_url, args.short_url)

    c.showPage()
    c.save()

    with open(buf.name, 'rb') as f:
        sys.stdout.buffer.write(f.read())

    os.unlink(buf.name)


if __name__ == '__main__':
    main()
