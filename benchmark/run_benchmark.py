"""
T-32 AI Benchmark — Face Match ArcFace
Chạy 20 cặp ảnh LFW: 10 positive (cùng người) + 10 negative (khác người)
Tính Accuracy, Precision, Recall, F1-score với threshold 0.70
"""

import csv
import json
import os
import time
from pathlib import Path

import requests

# ── Config ────────────────────────────────────────────────────────────────────
AI_SERVICE_URL  = "http://127.0.0.1:8001"
INTERNAL_KEY    = "sprint3_internal_secret_key_2026"
THRESHOLD       = 0.70
IMAGES_DIR      = Path("benchmark/test_images/lfw-deepfunneled")
RESULTS_DIR     = Path("benchmark/results")
HEADERS         = {"X-Internal-Key": INTERNAL_KEY}

# ── Load pairs từ CSV ─────────────────────────────────────────────────────────
def load_pairs(csv_path: str, is_match: bool, limit: int = 10) -> list[dict]:
    pairs = []
    with open(csv_path, newline="") as f:
        reader = csv.reader(f)
        next(reader)  # bỏ header
        for row in reader:
            if len(pairs) >= limit:
                break
            if is_match and len(row) >= 3:
                name, n1, n2 = row[0], int(row[1]), int(row[2])
                img_a = IMAGES_DIR / name / f"{name}_{str(n1).zfill(4)}.jpg"
                img_b = IMAGES_DIR / name / f"{name}_{str(n2).zfill(4)}.jpg"
                if img_a.exists() and img_b.exists():
                    pairs.append({"img_a": img_a, "img_b": img_b, "label": True, "name": name})
            elif not is_match and len(row) >= 4:
                name_a, n1, name_b, n2 = row[0], int(row[1]), row[2], int(row[3])
                img_a = IMAGES_DIR / name_a / f"{name_a}_{str(n1).zfill(4)}.jpg"
                img_b = IMAGES_DIR / name_b / f"{name_b}_{str(n2).zfill(4)}.jpg"
                if img_a.exists() and img_b.exists():
                    pairs.append({"img_a": img_a, "img_b": img_b, "label": False, "name": f"{name_a} vs {name_b}"})
    return pairs


# ── Encode ảnh ────────────────────────────────────────────────────────────────
def encode_image(img_path: Path) -> list[float] | None:
    with open(img_path, "rb") as f:
        resp = requests.post(
            f"{AI_SERVICE_URL}/encode",
            headers=HEADERS,
            files={"file": (img_path.name, f, "image/jpeg")},
            timeout=60,
        )
    if resp.status_code == 200:
        return resp.json()["encoding"]
    print(f"  [SKIP] encode failed {img_path.name}: {resp.json()}")
    return None


# ── Compare 2 encoding ────────────────────────────────────────────────────────
def compare_encodings(enc_a: list[float], enc_b: list[float]) -> float:
    resp = requests.post(
        f"{AI_SERVICE_URL}/compare",
        headers=HEADERS,
        json={"encoding_a": enc_a, "encoding_b": enc_b},
        timeout=30,
    )
    return resp.json()["score"]


# ── Main benchmark ────────────────────────────────────────────────────────────
def run():
    print("=" * 60)
    print("T-32 AI BENCHMARK — ArcFace Face Matching")
    print("=" * 60)

    # Load pairs
    positive_pairs = load_pairs("benchmark/matchpairsDevTest.csv", is_match=True, limit=10)
    negative_pairs = load_pairs("benchmark/mismatchpairsDevTest.csv", is_match=False, limit=10)
    all_pairs = positive_pairs + negative_pairs

    print(f"Loaded: {len(positive_pairs)} positive + {len(negative_pairs)} negative = {len(all_pairs)} pairs\n")

    results = []
    times   = []

    for i, pair in enumerate(all_pairs, 1):
        label = "SAME" if pair["label"] else "DIFF"
        print(f"[{i:02d}/20] {label} | {pair['name']}")

        t_start = time.time()

        enc_a = encode_image(pair["img_a"])
        enc_b = encode_image(pair["img_b"])

        if enc_a is None or enc_b is None:
            print("       → SKIPPED (no face detected)")
            continue

        score      = compare_encodings(enc_a, enc_b)
        elapsed    = round(time.time() - t_start, 2)
        predicted  = score >= THRESHOLD
        correct    = predicted == pair["label"]

        times.append(elapsed)
        results.append({
            "pair_id":    i,
            "name":       pair["name"],
            "label":      pair["label"],
            "score":      round(score, 4),
            "predicted":  predicted,
            "correct":    correct,
            "time_s":     elapsed,
        })

        status = "✓" if correct else "✗"
        print(f"       → score={score:.4f} | predicted={'SAME' if predicted else 'DIFF'} | {status} ({elapsed}s)")

    # ── Metrics ───────────────────────────────────────────────────────────────
    print("\n" + "=" * 60)
    print("RESULTS")
    print("=" * 60)

    tp = sum(1 for r in results if r["label"] and r["predicted"])
    tn = sum(1 for r in results if not r["label"] and not r["predicted"])
    fp = sum(1 for r in results if not r["label"] and r["predicted"])
    fn = sum(1 for r in results if r["label"] and not r["predicted"])

    total    = len(results)
    accuracy  = round((tp + tn) / total * 100, 1) if total else 0
    precision = round(tp / (tp + fp) * 100, 1) if (tp + fp) else 0
    recall    = round(tp / (tp + fn) * 100, 1) if (tp + fn) else 0
    f1        = round(2 * precision * recall / (precision + recall), 1) if (precision + recall) else 0
    avg_time  = round(sum(times) / len(times), 2) if times else 0

    print(f"Total pairs tested : {total}/20")
    print(f"True Positive (TP) : {tp}")
    print(f"True Negative (TN) : {tn}")
    print(f"False Positive (FP): {fp}")
    print(f"False Negative (FN): {fn}")
    print(f"Accuracy           : {accuracy}%")
    print(f"Precision          : {precision}%")
    print(f"Recall             : {recall}%")
    print(f"F1-score           : {f1}%")
    print(f"Avg time/pair      : {avg_time}s")
    print(f"Threshold used     : {THRESHOLD}")

    # ── Save JSON ─────────────────────────────────────────────────────────────
    RESULTS_DIR.mkdir(parents=True, exist_ok=True)
    report = {
        "model":      "ArcFace",
        "threshold":  THRESHOLD,
        "total":      total,
        "tp": tp, "tn": tn, "fp": fp, "fn": fn,
        "accuracy":   accuracy,
        "precision":  precision,
        "recall":     recall,
        "f1_score":   f1,
        "avg_time_s": avg_time,
        "pairs":      results,
    }
    out_path = RESULTS_DIR / "benchmark_report.json"
    with open(out_path, "w") as f:
        json.dump(report, f, indent=2)

    print(f"\nReport saved → {out_path}")
    print("=" * 60)


if __name__ == "__main__":
    run()