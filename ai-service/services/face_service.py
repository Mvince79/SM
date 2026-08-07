from __future__ import annotations

import math
import base64
from io import BytesIO
from typing import Sequence

import numpy as np
from PIL import Image
from deepface import DeepFace


class NoFaceDetectedError(RuntimeError):
    pass


class FaceService:
    def __init__(
        self,
        model_name: str = "ArcFace",
        threshold: float = 0.70,
        detector_backends: Sequence[str] | None = None,
    ) -> None:
        self.model_name = model_name
        self.threshold = threshold
        self.detector_backends = list(detector_backends or ["mtcnn", "opencv"])
        self._ready = False

    @property
    def is_ready(self) -> bool:
        return self._ready

    def warm_up(self) -> None:
        """Load the recognition model and cache its weights before serving traffic."""
        DeepFace.build_model(model_name=self.model_name)
        self._ready = True

    def encode(self, image_path: str) -> list[float]:
        last_error: Exception | None = None

        for detector_backend in self.detector_backends:
            try:
                representations = DeepFace.represent(
                    img_path=image_path,
                    model_name=self.model_name,
                    detector_backend=detector_backend,
                    enforce_detection=True,
                )
                self._ready = True
                break
            except ValueError as exc:
                last_error = exc
        else:
            raise NoFaceDetectedError(str(last_error) if last_error else "No face detected")

        if not representations:
            raise NoFaceDetectedError("No face representation returned")

        embedding = representations[0].get("embedding")
        if not embedding:
            raise NoFaceDetectedError("No embedding returned")

        return [float(value) for value in embedding]

    def detect_face(self, image_path: str) -> dict:
        last_error: Exception | None = None

        for detector_backend in self.detector_backends:
            try:
                faces = DeepFace.extract_faces(
                    img_path=image_path,
                    detector_backend=detector_backend,
                    align=True,
                    enforce_detection=True,
                )
                break
            except ValueError as exc:
                last_error = exc
        else:
            raise NoFaceDetectedError(str(last_error) if last_error else "No face detected")

        if not faces:
            raise NoFaceDetectedError("No face returned")

        face = faces[0]
        facial_area = face.get("facial_area") or {}
        crop_array = face.get("face")

        if crop_array is None:
            raise NoFaceDetectedError("No face crop returned")

        crop = np.asarray(crop_array)
        if crop.dtype != np.uint8:
            crop = np.clip(crop * 255, 0, 255).astype(np.uint8)

        image = Image.fromarray(crop).convert("RGB").resize((112, 112))
        buffer = BytesIO()
        image.save(buffer, format="JPEG", quality=90)

        detected_gender = "UNKNOWN"
        try:
            analysis = DeepFace.analyze(
                img_path=image_path,
                actions=["gender"],
                detector_backend=detector_backend,
                enforce_detection=False,
            )
            first = analysis[0] if isinstance(analysis, list) else analysis
            gender = str(first.get("dominant_gender", "")).lower()
            if gender in {"man", "male"}:
                detected_gender = "MALE"
            elif gender in {"woman", "female"}:
                detected_gender = "FEMALE"
        except Exception:
            detected_gender = "UNKNOWN"

        return {
            "bounding_box": {
                "x": int(facial_area.get("x", 0)),
                "y": int(facial_area.get("y", 0)),
                "w": int(facial_area.get("w", facial_area.get("width", 0))),
                "h": int(facial_area.get("h", facial_area.get("height", 0))),
            },
            "crop_image_base64": base64.b64encode(buffer.getvalue()).decode("ascii"),
            "detected_gender": detected_gender,
        }

    def compare(self, encoding_a: Sequence[float], encoding_b: Sequence[float]) -> float:
        if len(encoding_a) != len(encoding_b):
            raise ValueError("Encodings must have the same length")

        dot = sum(float(a) * float(b) for a, b in zip(encoding_a, encoding_b))
        norm_a = math.sqrt(sum(float(a) * float(a) for a in encoding_a))
        norm_b = math.sqrt(sum(float(b) * float(b) for b in encoding_b))

        if norm_a == 0 or norm_b == 0:
            return 0.0

        cosine_similarity = dot / (norm_a * norm_b)
        return max(0.0, min(1.0, cosine_similarity))
