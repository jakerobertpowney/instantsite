#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
321 Sites — A5 postcard print artwork, ReportLab template.

This is the print-faithful port of "Print Artwork - A5 Postcard.dc.html".
It draws two single-page, full-bleed A5 PDFs (front + back) using ReportLab
canvas primitives — no HTML engine involved.

Merge fields (per recipient) map 1:1 to your existing CLI args:

    --business-name   "Hair Candy"                       (front headline + back body)
    --city            "Stockport"                        (back eyebrow)
    --subdomain       "hair-candy"                        (front browser URL -> {sub}.321sites.com)
    --short-url       "321sites.com/p/ab3xk"             (back "or type:" line)
    --screenshot-url  https://321sites.com/screenshots/{places_id}.png
    --qr-url          https://api.qrserver.com/v1/create-qr-code/?...&data=...

URLs are fetched to temp files at generation time (mirrors your fetch_image()).
You can instead pass already-downloaded files via --screenshot-path / --qr-path.

Page is 154 x 216 mm = A5 portrait + 3 mm bleed all round (Stannp full-bleed
template). NOTE: this is intentionally larger than plain A5 (148 x 210). Stannp's
full-bleed artwork field wants the bleed size; switch your old A5 page size to this.

------------------------------------------------------------------------------
Fonts: the design uses Inter (400/500/600/700). Drop the Inter .ttf files in a
folder and point FONT_DIR / --font-dir at it for an exact match. If Inter is not
found we fall back to Helvetica (metrics differ slightly; headline auto-fit still
keeps it inside the safe zone).
------------------------------------------------------------------------------
"""

import argparse
import os
import re
import tempfile
import urllib.request

from reportlab.lib.units import mm
from reportlab.lib.utils import ImageReader
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.pdfgen import canvas

# ----------------------------------------------------------------------------
# Page geometry (points). Origin is bottom-left in ReportLab.
# ----------------------------------------------------------------------------
PAGE_W = 154 * mm          # full-bleed width  (151 trim + 3 bleed implied)
PAGE_H = 216 * mm          # full-bleed height (213 trim + 3 bleed implied)
TRIM   = 3 * mm            # trim line inset from the bleed edge
SAFE   = 8 * mm            # 5 mm safe zone -> 3 mm bleed + 5 mm = 8 mm inset
PAD    = 13 * mm           # content padding from the physical (bleed) edge
PAD_BOTTOM = 11 * mm
INNER_W = PAGE_W - 2 * PAD  # 128 mm usable column width

# ----------------------------------------------------------------------------
# Palette (matches the HTML proof exactly)
# ----------------------------------------------------------------------------
INK      = (0x11/255, 0x18/255, 0x27/255)  # #111827 headline / strong text
SLATE    = (0x4b/255, 0x55/255, 0x63/255)  # #4b5563 subhead / back body-ish
GREY_374 = (0x37/255, 0x41/255, 0x51/255)  # #374151 url pill / back body
GREY_6B  = (0x6b/255, 0x72/255, 0x80/255)  # #6b7280 secondary
GREY_9C  = (0x9c/255, 0xa3/255, 0xaf/255)  # #9ca3af disclaimer
BLUE     = (0x1e/255, 0x66/255, 0xf5/255)  # #1e66f5 brand blue
BG_FRONT = (0xf7/255, 0xf8/255, 0xfa/255)  # #f7f8fa front background
BAR      = (0xee/255, 0xf0/255, 0xf3/255)  # #eef0f3 browser chrome
DOTS     = (0xd3/255, 0xd6/255, 0xdd/255)  # #d3d6dd traffic-light dots
BORDER   = (0xe2/255, 0xe4/255, 0xea/255)  # #e2e4ea hairline borders
WHITE    = (1, 1, 1)

# ----------------------------------------------------------------------------
# Fonts
# ----------------------------------------------------------------------------
FONT_REG, FONT_MED, FONT_SEMI, FONT_BOLD = (
    "Helvetica", "Helvetica", "Helvetica-Bold", "Helvetica-Bold")


def register_fonts(font_dir):
    """Register Inter if the .ttf files are present; else keep Helvetica."""
    global FONT_REG, FONT_MED, FONT_SEMI, FONT_BOLD
    if not font_dir or not os.path.isdir(font_dir):
        return
    wanted = {
        "Inter-Regular":  "Inter-Regular.ttf",
        "Inter-Medium":   "Inter-Medium.ttf",
        "Inter-SemiBold": "Inter-SemiBold.ttf",
        "Inter-Bold":     "Inter-Bold.ttf",
    }
    found = {}
    for name, fn in wanted.items():
        path = os.path.join(font_dir, fn)
        if os.path.isfile(path):
            pdfmetrics.registerFont(TTFont(name, path))
            found[name] = True
    if "Inter-Regular" in found:
        FONT_REG = "Inter-Regular"
        FONT_MED = "Inter-Medium" if "Inter-Medium" in found else "Inter-Regular"
        FONT_SEMI = "Inter-SemiBold" if "Inter-SemiBold" in found else FONT_MED
        FONT_BOLD = "Inter-Bold" if "Inter-Bold" in found else "Inter-SemiBold"


# ----------------------------------------------------------------------------
# Small helpers
# ----------------------------------------------------------------------------
def yt(top_mm):
    """Convert a 'mm from top of page' value to a ReportLab y coordinate (pt)."""
    return PAGE_H - top_mm * mm


def slugify(name):
    s = name.lower().replace("&", "and")
    s = re.sub(r"[^a-z0-9]+", "-", s)
    return s.strip("-")


def wrap_text(text, font, size_pt, max_w_pt):
    """Greedy word wrap. Returns a list of lines."""
    words = text.split()
    lines, cur = [], ""
    for w in words:
        trial = w if not cur else cur + " " + w
        if pdfmetrics.stringWidth(trial, font, size_pt) <= max_w_pt or not cur:
            cur = trial
        else:
            lines.append(cur)
            cur = w
    if cur:
        lines.append(cur)
    return lines


def draw_paragraph(c, text, x_mm, top_mm, max_w_mm, font, size_pt,
                   color, line_mult=1.2, letter_spacing_mm=0.0, upper=False):
    """
    Draw a wrapped paragraph. x_mm/top_mm are top-down mm. Returns the bottom
    edge of the block in top-down mm so the caller can chain layout.
    """
    if upper:
        text = text.upper()
    c.setFillColorRGB(*color)
    if letter_spacing_mm:
        c.setCharSpace(letter_spacing_mm * mm)
    lines = wrap_text(text, font, size_pt, max_w_mm * mm)
    line_h_mm = (size_pt / mm) * line_mult
    ascent_mm = (size_pt / mm) * 0.80  # cap top -> baseline offset (approx)
    c.setFont(font, size_pt)
    for i, line in enumerate(lines):
        baseline_top = top_mm + ascent_mm + i * line_h_mm
        c.drawString(x_mm * mm, yt(baseline_top), line)
    if letter_spacing_mm:
        c.setCharSpace(0)
    return top_mm + len(lines) * line_h_mm


def fit_headline(text, max_w_mm):
    """
    Pick a headline point size the way the HTML does: step down by character
    count (24 / 34 thresholds), then guarantee it actually fits the column.
    Returns (size_pt, lines).
    """
    n = len(text)
    base_mm = 8.6 if n > 34 else (10.6 if n > 24 else 13.2)
    sizes_mm = [base_mm, base_mm - 1.2, base_mm - 2.4, 7.4, 6.6]
    for s_mm in sizes_mm:
        size_pt = s_mm * mm
        lines = wrap_text(text, FONT_BOLD, size_pt, max_w_mm * mm)
        widest = max(pdfmetrics.stringWidth(l, FONT_BOLD, size_pt) for l in lines)
        if widest <= max_w_mm * mm:
            return size_pt, lines
    return 6.6 * mm, wrap_text(text, FONT_BOLD, 6.6 * mm, max_w_mm * mm)


def draw_logo(c, right_x_mm, center_top_mm, digit_mm):
    """
    Right-aligned '3 • 2 • 1 sites' lockup. right_x_mm is the right edge,
    center_top_mm is the vertical centre (top-down mm).
    """
    size = digit_mm * mm
    dot_d = digit_mm * 0.28 * mm
    sp = digit_mm * 0.30 * mm           # spacing around each dot
    sites_gap = digit_mm * 0.30 * mm
    w_digit = pdfmetrics.stringWidth("3", FONT_BOLD, size)  # all digits equal-ish
    w2 = pdfmetrics.stringWidth("2", FONT_BOLD, size)
    w1 = pdfmetrics.stringWidth("1", FONT_BOLD, size)
    w_sites = pdfmetrics.stringWidth("sites", FONT_SEMI, size)
    total = (w_digit + sp + dot_d + sp + w2 + sp + dot_d + sp + w1
             + sites_gap + w_sites)
    x = right_x_mm * mm - total
    baseline = center_top_mm + digit_mm * 0.33   # centre caps on center_top_mm
    by = yt(baseline)
    dot_cy = yt(center_top_mm - digit_mm * 0.05)  # dots sit ~middle, slight drop

    def digit(ch, w):
        nonlocal x
        c.setFont(FONT_BOLD, size)
        c.setFillColorRGB(*INK)
        c.drawString(x, by, ch)
        x += w

    def dot():
        nonlocal x
        x += sp
        c.setFillColorRGB(*BLUE)
        c.circle(x + dot_d / 2, dot_cy, dot_d / 2, stroke=0, fill=1)
        x += dot_d + sp

    digit("3", w_digit); dot(); digit("2", w2); dot(); digit("1", w1)
    x += sites_gap
    c.setFont(FONT_SEMI, size)
    c.setFillColorRGB(*INK)
    c.drawString(x, by, "sites")


def draw_qr_box(c, left_mm, bottom_mm, qr_mm, pad_mm, qr_path):
    """White bordered card with the QR centred inside. bottom_mm is top-down."""
    box = qr_mm + 2 * pad_mm
    # roundRect wants (x, y_bottom, w, h) in canvas coords; bottom_mm is the
    # lower edge of the card in top-down mm.
    x = left_mm * mm
    y = yt(bottom_mm)
    c.setFillColorRGB(*WHITE)
    c.setStrokeColorRGB(*BORDER)
    c.setLineWidth(0.35 * mm)
    c.roundRect(x, y, box * mm, box * mm, 1.3 * mm, stroke=1, fill=1)
    if qr_path and os.path.isfile(qr_path):
        c.drawImage(qr_path, x + pad_mm * mm, y + pad_mm * mm,
                    qr_mm * mm, qr_mm * mm, preserveAspectRatio=True, mask="auto")
    return box  # total box size in mm


def draw_guides(c):
    """Screen-only trim (magenta) + safe (green) guides. Off by default."""
    c.saveState()
    c.setDash(3, 3)
    c.setLineWidth(0.3)
    c.setStrokeColorRGB(0.85, 0.27, 0.94)
    c.rect(TRIM, TRIM, PAGE_W - 2 * TRIM, PAGE_H - 2 * TRIM, stroke=1, fill=0)
    c.setStrokeColorRGB(0.13, 0.64, 0.29)
    c.rect(SAFE, SAFE, PAGE_W - 2 * SAFE, PAGE_H - 2 * SAFE, stroke=1, fill=0)
    c.restoreState()


# ----------------------------------------------------------------------------
# FRONT
# ----------------------------------------------------------------------------
def draw_front(c, business_name, site_domain, screenshot_path, qr_path,
               guides=False):
    # Background
    c.setFillColorRGB(*BG_FRONT)
    c.rect(0, 0, PAGE_W, PAGE_H, stroke=0, fill=1)

    # Headline: "We built {name} a website."
    headline = u"We built %s a website." % business_name
    size_pt, lines = fit_headline(headline, INNER_W / mm)
    c.setFillColorRGB(*INK)
    c.setCharSpace(-0.02 * (size_pt / mm) * mm)  # ~ -0.02em tracking
    c.setFont(FONT_BOLD, size_pt)
    line_h_mm = (size_pt / mm) * 1.07
    ascent_mm = (size_pt / mm) * 0.80
    for i, line in enumerate(lines):
        c.drawString(PAD, yt(13 + ascent_mm + i * line_h_mm), line)
    c.setCharSpace(0)
    head_bottom = 13 + len(lines) * line_h_mm

    # Subhead
    sub_top = head_bottom + 3.5
    c.setFont(FONT_REG, 5 * mm)
    c.setFillColorRGB(*SLATE)
    c.drawString(PAD, yt(sub_top + (5 * 0.80)), u"It\u2019s free to claim.")
    sub_bottom = sub_top + 5 * 1.0

    # Browser mockup (rotated -2deg), top-down anchor
    browser_top = sub_bottom + 7
    draw_browser(c, browser_top, site_domain, screenshot_path)

    # Footer pinned to the bottom
    draw_front_footer(c, qr_path)

    if guides:
        draw_guides(c)


def draw_browser(c, top_mm, site_domain, screenshot_path):
    """Rotated browser card: chrome bar + URL pill + clipped screenshot."""
    bw = INNER_W                      # browser width (pt)
    bar_h = 11.3 * mm
    shot_h = 92 * mm
    bh = bar_h + shot_h               # browser height (pt)
    radius = 3.3 * mm

    cx = PAGE_W / 2                    # rotate about the column centre
    cy = yt(top_mm) - bh / 2

    c.saveState()
    c.translate(cx, cy)
    c.rotate(-2)
    c.translate(-bw / 2, -bh / 2)     # local origin at browser bottom-left

    # Soft drop shadow (stacked translucent rounded rects)
    for i, (off, a) in enumerate([(2.4, 0.05), (1.4, 0.07), (0.6, 0.08)]):
        c.saveState()
        c.setFillColorRGB(0.07, 0.10, 0.16)
        try:
            c.setFillAlpha(a)
        except Exception:
            pass
        c.roundRect(-0.4 * mm, -off * mm, bw + 0.8 * mm, bh, radius,
                    stroke=0, fill=1)
        c.restoreState()

    # Clip everything to the rounded outer shape
    c.saveState()
    p = c.beginPath()
    p.roundRect(0, 0, bw, bh, radius)
    c.clipPath(p, stroke=0, fill=0)

    # White body
    c.setFillColorRGB(*WHITE)
    c.rect(0, 0, bw, bh, stroke=0, fill=1)

    # Screenshot window (below the chrome bar) — crop the top ~2.14%
    if screenshot_path and os.path.isfile(screenshot_path):
        img = ImageReader(screenshot_path)
        iw, ih = img.getSize()
        disp_h = bw * (ih / float(iw))         # height if drawn at full width
        shift = disp_h * 0.0214                # CSS margin-top: -2.14%
        win_top = bh - bar_h                   # top of the 92mm window (local y)
        # Place image so its top sits 'shift' above the window top, then clip.
        c.saveState()
        wp = c.beginPath()
        wp.rect(0, win_top - shot_h, bw, shot_h)
        c.clipPath(wp, stroke=0, fill=0)
        img_y = win_top + shift - disp_h       # bottom of the image
        c.drawImage(img, 0, img_y, bw, disp_h, mask="auto")
        c.restoreState()
    else:
        c.setFillColorRGB(0.93, 0.94, 0.95)
        c.rect(0, bh - bar_h - shot_h, bw, shot_h, stroke=0, fill=1)

    # Chrome bar
    c.setFillColorRGB(*BAR)
    c.rect(0, bh - bar_h, bw, bar_h, stroke=0, fill=1)
    bar_cy = bh - bar_h / 2
    dot_d = 2.6 * mm
    dx = 4 * mm
    c.setFillColorRGB(*DOTS)
    for _ in range(3):
        c.circle(dx + dot_d / 2, bar_cy, dot_d / 2, stroke=0, fill=1)
        dx += dot_d + 2 * mm
    # URL pill
    pill_x = dx + 1.3 * mm
    pill_r_edge = bw - 4 * mm
    pill_h = 6.6 * mm
    pill_w = pill_r_edge - pill_x
    c.setFillColorRGB(*WHITE)
    c.roundRect(pill_x, bar_cy - pill_h / 2, pill_w, pill_h, pill_h / 2,
                stroke=0, fill=1)
    c.setFont(FONT_MED, 3.5 * mm)
    c.setFillColorRGB(*GREY_374)
    c.drawCentredString(pill_x + pill_w / 2, bar_cy - 3.5 * mm * 0.36, site_domain)
    c.restoreState()  # end clip

    # Hairline border on top
    c.setStrokeColorRGB(0.89, 0.90, 0.92)
    c.setLineWidth(0.35 * mm)
    c.roundRect(0, 0, bw, bh, radius, stroke=1, fill=0)
    c.restoreState()  # end rotation


def draw_front_footer(c, qr_path):
    qr_mm, pad_mm = 16, 2.3
    box = qr_mm + 2 * pad_mm                 # 20.6 mm
    # footer bottom edge sits PAD_BOTTOM from the page bottom -> top-down mm:
    box_bottom_td = (PAGE_H - PAD_BOTTOM) / mm
    draw_qr_box(c, PAD / mm, box_bottom_td, qr_mm, pad_mm, qr_path)

    # Text column
    tx = (PAD + box * mm) / mm + 4.6
    center_td = box_bottom_td - box / 2
    c.setFont(FONT_SEMI, 4.3 * mm)
    c.setFillColorRGB(*INK)
    c.drawString(tx * mm, yt(center_td - 0.6), "See your site live")
    c.setFont(FONT_REG, 3.7 * mm)
    c.setFillColorRGB(*GREY_6B)
    c.drawString(tx * mm, yt(center_td + 4.0), "Scan, or turn over for details")

    # Logo, right-aligned
    draw_logo(c, (PAGE_W - PAD) / mm, center_td, 5.0)


# ----------------------------------------------------------------------------
# BACK
# ----------------------------------------------------------------------------
def draw_back(c, business_name, city, short_url, qr_path, guides=False):
    # White background
    c.setFillColorRGB(*WHITE)
    c.rect(0, 0, PAGE_W, PAGE_H, stroke=0, fill=1)

    # NB: top 56 mm is the Stannp address box + indicia — intentionally BLANK.

    # Eyebrow "BUSINESS · CITY"
    eyebrow = u"%s \u00b7 %s" % (business_name, city)
    top = 58
    c.setFont(FONT_BOLD, 3.6 * mm)
    c.setFillColorRGB(*BLUE)
    c.setCharSpace(0.14 * 3.6 * mm)
    c.drawString(PAD, yt(top + 3.6 * 0.80), eyebrow.upper())
    c.setCharSpace(0)

    # Title
    title_top = top + 3.6 + 2.6
    c.setFont(FONT_BOLD, 11.3 * mm)
    c.setFillColorRGB(*INK)
    c.setCharSpace(-0.015 * 11.3 * mm)
    c.drawString(PAD, yt(title_top + 11.3 * 0.80), u"It\u2019s free to claim")
    c.setCharSpace(0)
    title_bottom = title_top + 11.3 * 1.0

    # Body
    body = (u"We\u2019ve built a free one-page website for %s \u2014 your photos, "
            u"services, opening hours and reviews, all in one link your customers "
            u"can find. Scan the code to see it. If you like it, claiming it takes "
            u"two minutes. If not, bin this card \u2014 no hard feelings." % business_name)
    body_bottom = draw_paragraph(c, body, PAD / mm, title_bottom + 4, 128,
                                 FONT_REG, 4.5 * mm, GREY_374, line_mult=1.6)

    # QR row
    qr_mm, pad_mm = 32, 4
    box = qr_mm + 2 * pad_mm                 # 40 mm
    row_top = body_bottom + 6
    box_bottom_td = row_top + box
    draw_qr_box(c, PAD / mm, box_bottom_td, qr_mm, pad_mm, qr_path)

    # Text column beside QR (vertically centred on the box)
    tx = (PAD + box * mm) / mm + 5.3
    center_td = row_top + box / 2
    c.setFont(FONT_REG, 3.6 * mm)
    c.setFillColorRGB(*GREY_6B)
    c.drawString(tx * mm, yt(center_td - 7.0), "or type:")
    c.setFont(FONT_BOLD, 5.6 * mm)
    c.setFillColorRGB(*INK)
    c.drawString(tx * mm, yt(center_td - 1.2), short_url)
    c.setFont(FONT_REG, 3.6 * mm)
    c.setFillColorRGB(*GREY_6B)
    c.drawString(tx * mm, yt(center_td + 5.0), u"Free to claim \u00b7 No card needed")
    c.drawString(tx * mm, yt(center_td + 9.4),
                 u"Premium features from \u00a39.99/mo")

    # Footer (disclaimer + logo) anchored to the bottom
    foot_bottom_td = (PAGE_H - PAD_BOTTOM) / mm
    disclaimer = (u"This preview was built from publicly available business "
                  u"information. Not interested? Scan and tap \u2018Not interested\u2019 "
                  u"to remove it. 321 Sites Ltd \u00b7 hello@321sites.com")
    # Draw disclaimer growing upward from the bottom: wrap first, then place.
    lines = wrap_text(disclaimer, FONT_REG, 2.9 * mm, 108 * mm)
    line_h = 2.9 * 1.5
    c.setFont(FONT_REG, 2.9 * mm)
    c.setFillColorRGB(*GREY_9C)
    start_top = foot_bottom_td - len(lines) * line_h + 2.9 * 0.80
    for i, line in enumerate(lines):
        c.drawString(PAD, yt(start_top + i * line_h), line)

    draw_logo(c, (PAGE_W - PAD) / mm, foot_bottom_td - 2.2, 4.3)

    if guides:
        draw_guides(c)


# ----------------------------------------------------------------------------
# Rendering entry points
# ----------------------------------------------------------------------------
def render_front(path, business_name, site_domain, screenshot_path, qr_path,
                 guides=False):
    c = canvas.Canvas(path, pagesize=(PAGE_W, PAGE_H))
    draw_front(c, business_name, site_domain, screenshot_path, qr_path, guides)
    c.showPage()
    c.save()


def render_back(path, business_name, city, short_url, qr_path, guides=False):
    c = canvas.Canvas(path, pagesize=(PAGE_W, PAGE_H))
    draw_back(c, business_name, city, short_url, qr_path, guides)
    c.showPage()
    c.save()


def fetch_image(url, suffix=".png"):
    """Download a URL to a temp file and return its path (mirrors your helper)."""
    fd, path = tempfile.mkstemp(suffix=suffix)
    os.close(fd)
    req = urllib.request.Request(url, headers={"User-Agent": "321sites-postcard/1.0"})
    with urllib.request.urlopen(req, timeout=30) as r, open(path, "wb") as f:
        f.write(r.read())
    return path


# ----------------------------------------------------------------------------
# CLI
# ----------------------------------------------------------------------------
def main():
    ap = argparse.ArgumentParser(description="Generate a 321 Sites A5 postcard (front + back PDFs).")
    ap.add_argument("--business-name", required=True)
    ap.add_argument("--city", required=True)
    ap.add_argument("--subdomain", default=None,
                    help="Site subdomain; defaults to a slug of the business name.")
    ap.add_argument("--short-url", required=True)
    # image sources — either a URL (fetched) or a local path
    ap.add_argument("--screenshot-url", default=None)
    ap.add_argument("--screenshot-path", default=None)
    ap.add_argument("--qr-url", default=None)
    ap.add_argument("--qr-path", default=None)
    # output
    ap.add_argument("--front-out", default="postcard-front.pdf")
    ap.add_argument("--back-out", default="postcard-back.pdf")
    ap.add_argument("--font-dir", default=os.environ.get("INTER_FONT_DIR", "fonts"))
    ap.add_argument("--guides", action="store_true",
                    help="Draw screen-only trim/safe guides (proofing only).")
    args = ap.parse_args()

    register_fonts(args.font_dir)

    sub = args.subdomain or slugify(args.business_name)
    site_domain = "%s.321sites.com" % sub

    tmp = []
    screenshot_path = args.screenshot_path
    if not screenshot_path and args.screenshot_url:
        screenshot_path = fetch_image(args.screenshot_url)
        tmp.append(screenshot_path)
    qr_path = args.qr_path
    if not qr_path and args.qr_url:
        qr_path = fetch_image(args.qr_url)
        tmp.append(qr_path)

    try:
        render_front(args.front_out, args.business_name, site_domain,
                     screenshot_path, qr_path, args.guides)
        render_back(args.back_out, args.business_name, args.city,
                    args.short_url, qr_path, args.guides)
    finally:
        for p in tmp:
            try:
                os.remove(p)
            except OSError:
                pass

    print("Wrote %s and %s" % (args.front_out, args.back_out))


if __name__ == "__main__":
    main()
