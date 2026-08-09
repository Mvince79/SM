from __future__ import annotations

import importlib
import os
import platform
import shutil
import sys
from pathlib import Path

from dotenv import load_dotenv


SERVICE_ROOT = Path(__file__).resolve().parents[1]
load_dotenv(SERVICE_ROOT / ".env")


def check(condition: bool, success: str, failure: str, errors: list[str]) -> None:
    if condition:
        print(f"[OK] {success}")
        return

    print(f"[FAIL] {failure}")
    errors.append(failure)


def main() -> int:
    errors: list[str] = []
    internal_key = os.getenv("INTERNAL_KEY", "")
    app_env = os.getenv("APP_ENV", "local").lower()
    bind_host = os.getenv("BIND_HOST", "127.0.0.1")
    home = Path.home()
    disk = shutil.disk_usage(SERVICE_ROOT)

    print(f"Python: {platform.python_version()}")
    print(f"Architecture: {platform.machine()}")
    print(f"Service root: {SERVICE_ROOT}")

    check(sys.version_info >= (3, 11), "Python version is supported", "Python 3.11+ is required", errors)
    check(app_env == "production", "APP_ENV is production", "APP_ENV must be production on the VPS", errors)
    check((SERVICE_ROOT / ".env").is_file(), ".env exists", "Create ai-service/.env from .env.production.example", errors)
    check(len(internal_key) >= 32, "INTERNAL_KEY length is valid", "INTERNAL_KEY must have at least 32 characters", errors)
    check(
        internal_key not in {
            "change_me",
            "sprint3_internal_secret_key_2026",
            "replace_with_a_unique_random_secret_at_least_32_characters",
        },
        "INTERNAL_KEY is not a known placeholder",
        "Replace the placeholder INTERNAL_KEY",
        errors,
    )
    check(
        bind_host in {"127.0.0.1", "::1", "localhost"},
        "BIND_HOST is loopback-only",
        "BIND_HOST must not expose the AI service publicly",
        errors,
    )
    check(disk.free >= 2 * 1024**3, "At least 2 GB disk is free", "At least 2 GB free disk is required", errors)
    check(os.access(home, os.W_OK), f"HOME is writable: {home}", f"HOME is not writable: {home}", errors)

    for module in ("fastapi", "uvicorn", "deepface", "tensorflow", "PIL"):
        try:
            importlib.import_module(module)
            print(f"[OK] Python module is installed: {module}")
        except Exception as exception:
            message = f"Cannot import {module}: {exception}"
            print(f"[FAIL] {message}")
            errors.append(message)

    if errors:
        print(f"Preflight failed with {len(errors)} error(s).")
        return 1

    print("Preflight passed.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
