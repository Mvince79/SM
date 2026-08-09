from __future__ import annotations

import time

from main import MODEL_NAME, face_service, validate_production_settings


def main() -> int:
    validate_production_settings()
    started = time.perf_counter()
    print(f"Warming up {MODEL_NAME}; model weights may be downloaded on the first run...")
    face_service.warm_up()
    duration = time.perf_counter() - started
    print(f"Model {MODEL_NAME} is ready after {duration:.2f}s.")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
