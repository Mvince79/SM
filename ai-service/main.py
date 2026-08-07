from __future__ import annotations

import logging
import os
import tempfile
import time
import asyncio
from contextlib import asynccontextmanager
from io import BytesIO
from typing import Annotated

from dotenv import load_dotenv
from fastapi import Depends, FastAPI, File, Header, HTTPException, Request, UploadFile
from fastapi.concurrency import run_in_threadpool
from fastapi.responses import JSONResponse
from PIL import Image, UnidentifiedImageError
from starlette import status

from models.schemas import (
    CompareRequest,
    CompareResponse,
    DetectFaceResponse,
    EncodeResponse,
    HealthResponse,
    VerifyResponse,
)
from services.face_service import FaceService, NoFaceDetectedError

load_dotenv()

MODEL_NAME = os.getenv("MODEL_NAME", "ArcFace")
DETECTOR_BACKENDS = [
    backend.strip()
    for backend in os.getenv("DETECTOR_BACKENDS", "mtcnn,opencv").split(",")
    if backend.strip()
]
INTERNAL_KEY = os.getenv("INTERNAL_KEY", "change_me")
SCORE_THRESHOLD = float(os.getenv("SCORE_THRESHOLD", "0.70"))
MAX_UPLOAD_BYTES = int(os.getenv("MAX_UPLOAD_BYTES", str(10 * 1024 * 1024)))
MAX_IMAGE_PIXELS = int(os.getenv("MAX_IMAGE_PIXELS", "40000000"))
MAX_CONCURRENT_INFERENCES = max(1, int(os.getenv("MAX_CONCURRENT_INFERENCES", "1")))
REQUEST_TIMEOUT_SECONDS = float(os.getenv("REQUEST_TIMEOUT_SECONDS", "30"))
WARMUP_ON_STARTUP = os.getenv("WARMUP_ON_STARTUP", "false").lower() in {"1", "true", "yes", "on"}
BIND_HOST = os.getenv("BIND_HOST", "127.0.0.1")
BIND_PORT = int(os.getenv("BIND_PORT", "8001"))
APP_ENV = os.getenv("APP_ENV", "local").lower()
ALLOWED_IMAGE_MIME_TYPES = {"image/jpeg", "image/png"}

logging.basicConfig(
    level=os.getenv("LOG_LEVEL", "INFO"),
    format="%(asctime)s %(levelname)s %(name)s %(message)s",
)
logger = logging.getLogger("face-match-service")

face_service = FaceService(model_name=MODEL_NAME, threshold=SCORE_THRESHOLD, detector_backends=DETECTOR_BACKENDS)
inference_semaphore = asyncio.Semaphore(MAX_CONCURRENT_INFERENCES)


def validate_production_settings() -> None:
    if APP_ENV != "production":
        return

    errors: list[str] = []

    if len(INTERNAL_KEY) < 32 or INTERNAL_KEY in {
        "change_me",
        "sprint3_internal_secret_key_2026",
        "replace_with_a_unique_random_secret_at_least_32_characters",
    }:
        errors.append("INTERNAL_KEY must be a unique secret with at least 32 characters")

    if BIND_HOST not in {"127.0.0.1", "::1", "localhost"}:
        errors.append("BIND_HOST must be loopback-only")

    if errors:
        raise RuntimeError("Invalid production configuration: " + "; ".join(errors))


async def run_inference(function, *args):
    async with inference_semaphore:
        return await run_in_threadpool(function, *args)


async def read_validated_image(upload: UploadFile, field: str) -> tuple[bytes, str]:
    if upload.content_type not in ALLOWED_IMAGE_MIME_TYPES:
        raise HTTPException(
            status_code=status.HTTP_415_UNSUPPORTED_MEDIA_TYPE,
            detail=f"unsupported_media_type:{field}",
        )

    contents = await upload.read(MAX_UPLOAD_BYTES + 1)
    if len(contents) > MAX_UPLOAD_BYTES:
        raise HTTPException(
            status_code=status.HTTP_413_REQUEST_ENTITY_TOO_LARGE,
            detail=f"file_too_large:{field}",
        )

    try:
        with Image.open(BytesIO(contents)) as image:
            width, height = image.size
            detected_mime = image.get_format_mimetype()
            image.verify()
    except (OSError, UnidentifiedImageError, Image.DecompressionBombError):
        raise HTTPException(
            status_code=status.HTTP_415_UNSUPPORTED_MEDIA_TYPE,
            detail=f"invalid_image_content:{field}",
        ) from None

    if detected_mime != upload.content_type or width < 1 or height < 1:
        raise HTTPException(
            status_code=status.HTTP_415_UNSUPPORTED_MEDIA_TYPE,
            detail=f"invalid_image_content:{field}",
        )

    if width * height > MAX_IMAGE_PIXELS:
        raise HTTPException(
            status_code=status.HTTP_413_REQUEST_ENTITY_TOO_LARGE,
            detail=f"image_dimensions_too_large:{field}",
        )

    suffix = ".png" if detected_mime == "image/png" else ".jpg"
    return contents, suffix


@asynccontextmanager
async def lifespan(app: FastAPI):
    validate_production_settings()
    logger.info("Starting face match service", extra={"model": MODEL_NAME, "detectors": DETECTOR_BACKENDS})
    if WARMUP_ON_STARTUP:
        await run_inference(face_service.warm_up)
    yield


app = FastAPI(title="Face Match AI Service", version="1.0.0", lifespan=lifespan)


async def require_internal_key(x_internal_key: Annotated[str | None, Header()] = None) -> None:
    if not INTERNAL_KEY or x_internal_key != INTERNAL_KEY:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="invalid_internal_key",
        )


@app.middleware("http")
async def log_requests(request: Request, call_next):
    started = time.perf_counter()
    try:
        response = await asyncio.wait_for(call_next(request), timeout=REQUEST_TIMEOUT_SECONDS)
    except asyncio.TimeoutError:
        response = JSONResponse(
            status_code=status.HTTP_408_REQUEST_TIMEOUT,
            content={"detail": "request_timeout"},
        )

    duration_ms = round((time.perf_counter() - started) * 1000, 2)
    logger.info(
        "request completed",
        extra={
            "method": request.method,
            "path": request.url.path,
            "status": response.status_code,
            "duration_ms": duration_ms,
            "model": MODEL_NAME,
        },
    )
    return response


@app.get("/health", response_model=HealthResponse, dependencies=[Depends(require_internal_key)])
async def health() -> HealthResponse:
    return HealthResponse(status="ok", model=MODEL_NAME)


@app.get("/ready", response_model=HealthResponse, dependencies=[Depends(require_internal_key)])
async def ready() -> HealthResponse:
    if not face_service.is_ready:
        raise HTTPException(status_code=status.HTTP_503_SERVICE_UNAVAILABLE, detail="model_not_ready")

    return HealthResponse(status="ok", model=MODEL_NAME)


@app.post("/encode", response_model=EncodeResponse, dependencies=[Depends(require_internal_key)])
async def encode(file: UploadFile = File(...)) -> EncodeResponse:
    contents, suffix = await read_validated_image(file, "file")
    with tempfile.NamedTemporaryFile(suffix=suffix, delete=False) as temp_file:
        temp_file.write(contents)
        temp_path = temp_file.name

    try:
        try:
            encoding = await run_inference(face_service.encode, temp_path)
        except NoFaceDetectedError:
            raise HTTPException(status_code=status.HTTP_422_UNPROCESSABLE_ENTITY, detail="no_face_detected") from None

        return EncodeResponse(encoding=encoding, has_face=True, model=MODEL_NAME)
    finally:
        try:
            os.remove(temp_path)
        except OSError:
            logger.warning("Could not remove temporary upload", extra={"path": temp_path})


@app.post("/detect-face", response_model=DetectFaceResponse, dependencies=[Depends(require_internal_key)])
async def detect_face(file: UploadFile = File(...)) -> DetectFaceResponse:
    contents, suffix = await read_validated_image(file, "file")
    with tempfile.NamedTemporaryFile(suffix=suffix, delete=False) as temp_file:
        temp_file.write(contents)
        temp_path = temp_file.name

    try:
        try:
            result = await run_inference(face_service.detect_face, temp_path)
        except NoFaceDetectedError:
            raise HTTPException(status_code=status.HTTP_422_UNPROCESSABLE_ENTITY, detail="no_face_detected") from None

        return DetectFaceResponse(has_face=True, model=MODEL_NAME, **result)
    finally:
        try:
            os.remove(temp_path)
        except OSError:
            logger.warning("Could not remove temporary upload", extra={"path": temp_path})


@app.post("/compare", response_model=CompareResponse, dependencies=[Depends(require_internal_key)])
async def compare(payload: CompareRequest) -> CompareResponse:
    score = await run_inference(face_service.compare, payload.encoding_a, payload.encoding_b)
    is_matched = score >= SCORE_THRESHOLD
    logger.info("face comparison completed", extra={"score": round(score, 4), "model": MODEL_NAME})

    return CompareResponse(score=score, is_matched=is_matched, model=MODEL_NAME)


@app.post("/verify", response_model=VerifyResponse, dependencies=[Depends(require_internal_key)])
async def verify(
    id_document: UploadFile = File(...),
    selfie: UploadFile = File(...),
) -> VerifyResponse:
    uploads = {
        "id_document": id_document,
        "selfie": selfie,
    }
    temp_paths: dict[str, str] = {}

    try:
        for field, upload in uploads.items():
            contents, suffix = await read_validated_image(upload, field)
            with tempfile.NamedTemporaryFile(suffix=suffix, delete=False) as temp_file:
                temp_file.write(contents)
                temp_paths[field] = temp_file.name

        try:
            id_encoding = await run_inference(face_service.encode, temp_paths["id_document"])
            selfie_encoding = await run_inference(face_service.encode, temp_paths["selfie"])
        except NoFaceDetectedError:
            raise HTTPException(
                status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
                detail="no_face_detected",
            ) from None

        score = await run_inference(face_service.compare, id_encoding, selfie_encoding)
        is_matched = score >= SCORE_THRESHOLD
        logger.info("selfie verification completed", extra={"score": round(score, 4), "model": MODEL_NAME})

        return VerifyResponse(score=score, is_matched=is_matched, model=MODEL_NAME)
    finally:
        for temp_path in temp_paths.values():
            try:
                os.remove(temp_path)
            except OSError:
                logger.warning("Could not remove temporary upload", extra={"path": temp_path})


if __name__ == "__main__":
    import uvicorn

    uvicorn.run("main:app", host=BIND_HOST, port=BIND_PORT, reload=False)
