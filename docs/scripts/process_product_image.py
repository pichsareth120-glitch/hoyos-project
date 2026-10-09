#!/usr/bin/env python3
"""
HOYOS Product Image Post-Processor.
Cuts out fashion models using rembg and composites them onto a shared
dark-red radial gradient background (#2a0006 to #0a0003) with a soft
red glow under the feet (#FF002E).

Produces 3 visibly distinct 4:5 (1200x1500) crops:
  1. {slug}-front.webp: Primary 4:5 portrait view, head preserved, model fills ~92% height
  2. {slug}-detail.webp: Tighter crop focused on torso & fabric texture
  3. {slug}-full.webp: Full head-to-toe silhouette framing with visible floor glow
"""

import sys
import os
import numpy as np
from PIL import Image, ImageFilter, ImageDraw
import rembg

_SESSION = None

def get_rembg_session():
    global _SESSION
    if _SESSION is None:
        try:
            _SESSION = rembg.new_session('u2net_human_seg')
        except Exception:
            _SESSION = rembg.new_session('u2netp')
    return _SESSION


def create_gradient_background(width=1200, height=1500, glow_y=1350, glow_intensity=130):
    """
    Creates the shared dark-red radial gradient background (#2a0006 to #0a0003)
    with a soft red glow under the feet.
    """
    y, x = np.ogrid[:height, :width]
    cx, cy = width / 2.0, height * 0.48
    dx = (x - cx) / (width / 2.0)
    dy = (y - cy) / (height / 2.0)
    dist = np.sqrt(dx * dx + dy * dy)
    
    # Smoothstep interpolation between center (#2a0006) and edge (#0a0003)
    t = np.clip(dist / 1.25, 0.0, 1.0)
    t = t * t * (3.0 - 2.0 * t)
    
    # Center: #2a0006 -> RGB(42, 0, 6)
    # Edge:   #0a0003 -> RGB(10, 0, 3)
    r = (42.0 * (1.0 - t) + 10.0 * t).astype(np.uint8)
    g = np.zeros((height, width), dtype=np.uint8)
    b = (6.0 * (1.0 - t) + 3.0 * t).astype(np.uint8)
    
    bg = Image.fromarray(np.dstack((r, g, b)), 'RGB')
    
    # Soft red glow under the feet (#FF002E: 255, 0, 46)
    if glow_y is not None:
        glow_layer = Image.new('RGBA', (width, height), (0, 0, 0, 0))
        glow_draw = ImageDraw.Draw(glow_layer)
        glow_draw.ellipse(
            [width // 2 - 400, glow_y - 90, width // 2 + 400, glow_y + 90],
            fill=(255, 0, 46, glow_intensity)
        )
        glow_blurred = glow_layer.filter(ImageFilter.GaussianBlur(radius=55))
        bg.paste(glow_blurred, (0, 0), glow_blurred)
        
    return bg


def process_product_image(source_path, output_dir, slug):
    os.makedirs(output_dir, exist_ok=True)
    session = get_rembg_session()
    
    with Image.open(source_path) as orig_img:
        if orig_img.mode != 'RGB':
            orig_img = orig_img.convert('RGB')
        
        # 1. Background removal using rembg
        cutout = rembg.remove(orig_img, session=session)
        bbox = cutout.getbbox()
        if bbox:
            model = cutout.crop(bbox)
        else:
            model = cutout
            
        mw, mh = model.size
        
        # ----------------------------------------------------
        # 1. FRONT IMAGE: 1200x1500 (4:5) Closer Fashion View
        # Head preserved near top (y=65), model fills ~92% height
        # ----------------------------------------------------
        bg_front = create_gradient_background(1200, 1500, glow_y=1400, glow_intensity=110)
        scale_front = min(1040.0 / mw, 1370.0 / mh)
        fw, fh = int(mw * scale_front), int(mh * scale_front)
        m_front = model.resize((fw, fh), Image.Resampling.LANCZOS)
        f_px = (1200 - fw) // 2
        f_py = 65
        bg_front.paste(m_front, (f_px, f_py), m_front)
        
        front_path = os.path.join(output_dir, f"{slug}-front.webp")
        bg_front.save(front_path, "WEBP", quality=90)
        
        # ----------------------------------------------------
        # 2. DETAIL IMAGE: Tight Crop of Torso / Fabric Texture
        # Torso: y from 16% to 62% of model height
        # ----------------------------------------------------
        torso_top = int(mh * 0.16)
        torso_bottom = int(mh * 0.62)
        torso_left = int(mw * 0.10)
        torso_right = int(mw * 0.90)
        torso_crop = model.crop((torso_left, torso_top, torso_right, torso_bottom))
        tw, th = torso_crop.size
        
        bg_detail = create_gradient_background(1200, 1500, glow_y=None)
        scale_detail = max(1160.0 / tw, 1350.0 / th)
        dtw, dth = int(tw * scale_detail), int(th * scale_detail)
        torso_resized = torso_crop.resize((dtw, dth), Image.Resampling.LANCZOS)
        d_px = (1200 - dtw) // 2
        d_py = (1500 - dth) // 2
        bg_detail.paste(torso_resized, (d_px, d_py), torso_resized)
        
        detail_path = os.path.join(output_dir, f"{slug}-detail.webp")
        bg_detail.save(detail_path, "WEBP", quality=90)
        
        # ----------------------------------------------------
        # 3. FULL IMAGE: Full Silhouette with Shoes on Floor Glow
        # Entire outfit visible with atmospheric headroom
        # ----------------------------------------------------
        bg_full = create_gradient_background(1200, 1500, glow_y=1350, glow_intensity=140)
        scale_full = min(920.0 / mw, 1230.0 / mh)
        full_w, full_h = int(mw * scale_full), int(mh * scale_full)
        m_full = model.resize((full_w, full_h), Image.Resampling.LANCZOS)
        full_px = (1200 - full_w) // 2
        full_py = 1360 - full_h
        bg_full.paste(m_full, (full_px, full_py), m_full)
        
        full_path = os.path.join(output_dir, f"{slug}-full.webp")
        bg_full.save(full_path, "WEBP", quality=90)

    print(f"PROCESSED:{front_path}|{detail_path}|{full_path}")
    return True


if __name__ == "__main__":
    if len(sys.argv) < 4:
        print("Usage: python process_product_image.py <source_path> <output_dir> <slug>")
        sys.exit(1)
        
    source = sys.argv[1]
    out_dir = sys.argv[2]
    p_slug = sys.argv[3]
    
    process_product_image(source, out_dir, p_slug)
