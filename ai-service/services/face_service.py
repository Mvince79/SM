from __future__ import annotations

import math
from typing import Sequence

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
