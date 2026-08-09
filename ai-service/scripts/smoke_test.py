from __future__ import annotations

import argparse
import mimetypes
import os
from pathlib import Path

import httpx
from dotenv import load_dotenv


SERVICE_ROOT = Path(__file__).resolve().parents[1]
load_dotenv(SERVICE_ROOT / ".env")


def main() -> int:
    parser = argparse.ArgumentParser(description="Verify the deployed internal AI service.")
    parser.add_argument("--base-url", default="http://127.0.0.1:8001")
    parser.add_argument("--image", type=Path, help="Optional real image used to verify /encode")
    args = parser.parse_args()

    internal_key = os.getenv("INTERNAL_KEY", "")
    if not internal_key:
        print("INTERNAL_KEY is missing from ai-service/.env")
        return 1

    headers = {"X-Internal-Key": internal_key}

    with httpx.Client(base_url=args.base_url.rstrip("/"), headers=headers, timeout=10) as client:
        for path in ("/health", "/ready"):
            response = client.get(path)
            if response.status_code != 200:
                print(f"[FAIL] {path}: HTTP {response.status_code} {response.text}")
                return 1
            print(f"[OK] {path}: {response.json()}")

        unauthorized = client.get("/health", headers={"X-Internal-Key": "invalid"})
        if unauthorized.status_code != 401:
            print(f"[FAIL] invalid key returned HTTP {unauthorized.status_code}")
            return 1
        print("[OK] invalid internal key is rejected")

        if args.image:
            if not args.image.is_file():
                print(f"[FAIL] smoke-test image does not exist: {args.image}")
                return 1

            content_type = mimetypes.guess_type(args.image.name)[0] or "application/octet-stream"
            with args.image.open("rb") as image:
                encoded = client.post(
                    "/encode",
                    files={"file": (args.image.name, image, content_type)},
                )

            if encoded.status_code != 200:
                print(f"[FAIL] /encode: HTTP {encoded.status_code} {encoded.text}")
                return 1

            encoding = encoded.json().get("encoding")
            if not isinstance(encoding, list) or len(encoding) != 512:
                print("[FAIL] /encode did not return a 512-value ArcFace vector")
                return 1

            print(f"[OK] /encode returned {len(encoding)} values for {args.image.name}")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
