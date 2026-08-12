"""
Generates simple, fully original flat-design cartoon avatar illustrations
to use as placeholder demo photos for missing_children / found_children seeders.

These are NOT photos of real people and contain no third-party artwork —
just basic shapes drawn programmatically with Pillow. This keeps the seed
data legally safe while avoiding broken-image placeholders in the demo.
"""
import math
import os
import random
from PIL import Image, ImageDraw, ImageFilter

random.seed(42)

OUT_DIR = "storage/app/public/demo-photos"
os.makedirs(OUT_DIR, exist_ok=True)

# Palette of skin / hair / outfit colors that give visual variety without
# depicting any specific real ethnicity/person -- purely stylistic.
SKIN_TONES = ["#F6D2B0", "#E8B084", "#C68863", "#8D5A3B", "#F1C39A"]
HAIR_COLORS = ["#2B2118", "#4A2F1B", "#1A1A1A", "#5C3A21", "#332218"]
BG_COLORS = [
    "#FDE68A", "#93C5FD", "#FCA5A5", "#A7F3D0", "#C4B5FD",
    "#FDBA74", "#67E8F9", "#F9A8D4", "#BEF264", "#FCD34D",
    "#7DD3FC", "#FCA5A5",
]
OUTFIT_COLORS = ["#EF4444", "#3B82F6", "#10B981", "#F59E0B", "#8B5CF6", "#EC4899", "#06B6D4"]


def draw_avatar(seed_idx, gender, mood="neutral", size=800):
    rnd = random.Random(seed_idx * 97 + 13)
    img = Image.new("RGB", (size, size), rnd.choice(BG_COLORS))
    draw = ImageDraw.Draw(img)

    cx, cy = size // 2, int(size * 0.42)
    head_r = int(size * 0.22)
    skin = rnd.choice(SKIN_TONES)
    hair = rnd.choice(HAIR_COLORS)
    outfit = rnd.choice(OUTFIT_COLORS)

    # Shoulders / body
    body_top = cy + int(head_r * 0.85)
    draw.ellipse(
        [cx - int(size * 0.32), body_top, cx + int(size * 0.32), size + 40],
        fill=outfit,
    )

    # Head
    draw.ellipse([cx - head_r, cy - head_r, cx + head_r, cy + head_r], fill=skin)

    # Hair (simple cap shape, longer for "female" style, short for "male" style)
    if gender == "F":
        draw.pieslice(
            [cx - head_r - 6, cy - head_r - 10, cx + head_r + 6, cy + head_r + 30],
            180, 360, fill=hair,
        )
        # side locks
        draw.ellipse([cx - head_r - 14, cy - 10, cx - head_r + 20, cy + head_r + 20], fill=hair)
        draw.ellipse([cx + head_r - 20, cy - 10, cx + head_r + 14, cy + head_r + 20], fill=hair)
    else:
        draw.pieslice(
            [cx - head_r - 4, cy - head_r - 12, cx + head_r + 4, cy + head_r],
            180, 360, fill=hair,
        )

    # Eyes
    eye_y = cy + int(head_r * 0.05)
    eye_dx = int(head_r * 0.42)
    eye_r = int(head_r * 0.09)
    for sign in (-1, 1):
        ex = cx + sign * eye_dx
        draw.ellipse([ex - eye_r, eye_y - eye_r, ex + eye_r, eye_y + eye_r], fill="#2B2118")
        draw.ellipse([ex - eye_r // 3, eye_y - eye_r, ex + eye_r // 3, eye_y - eye_r // 3], fill="white")

    # Cheeks
    cheek_r = int(head_r * 0.14)
    for sign in (-1, 1):
        cxk = cx + sign * int(head_r * 0.6)
        cyk = cy + int(head_r * 0.35)
        draw.ellipse([cxk - cheek_r, cyk - cheek_r, cxk + cheek_r, cyk + cheek_r], fill="#F4A3A3")

    # Mouth — smiling (happy/"after found") or neutral/gentle (before/missing)
    mouth_w = int(head_r * 0.5)
    mouth_y = cy + int(head_r * 0.5)
    if mood == "happy":
        draw.arc(
            [cx - mouth_w // 2, mouth_y - mouth_w // 3, cx + mouth_w // 2, mouth_y + mouth_w // 2],
            10, 170, fill="#8B3A2A", width=6,
        )
    else:
        draw.line([cx - mouth_w // 3, mouth_y, cx + mouth_w // 3, mouth_y], fill="#8B3A2A", width=5)

    return img


def save_variants(img, base_path_no_ext):
    """Save original, display, thumb (200x200) and blurred versions, jpg + webp."""
    orig = img.convert("RGB")
    orig.save(f"{base_path_no_ext}_original.jpg", quality=85)
    orig.save(f"{base_path_no_ext}_original.webp", quality=82)

    display = orig.resize((600, 600))
    display.save(f"{base_path_no_ext}_display.jpg", quality=85)
    display.save(f"{base_path_no_ext}_display.webp", quality=82)

    thumb = orig.resize((200, 200))
    thumb.save(f"{base_path_no_ext}_thumb.jpg", quality=85)
    thumb.save(f"{base_path_no_ext}_thumb.webp", quality=82)

    blurred = thumb.filter(ImageFilter.GaussianBlur(radius=14))
    blurred.save(f"{base_path_no_ext}_blurred.jpg", quality=80)

    return {
        "width": orig.width,
        "height": orig.height,
        "file_size": os.path.getsize(f"{base_path_no_ext}_original.jpg"),
        "file_size_display": os.path.getsize(f"{base_path_no_ext}_display.jpg"),
    }


manifest = {}

idx = 1
for gender in ("M", "F"):
    for mood in ("neutral", "happy"):
        for i in range(4):  # 4 variants per gender/mood => 16 total avatars
            name = f"avatar_{gender.lower()}_{mood}_{i+1:02d}"
            img = draw_avatar(idx, gender, mood)
            meta = save_variants(img, os.path.join(OUT_DIR, name))
            manifest[name] = meta
            idx += 1

print(f"Generated {len(manifest)} avatar sets in {OUT_DIR}")
for k, v in list(manifest.items())[:3]:
    print(k, v)
