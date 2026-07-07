from __future__ import annotations

import unittest
import time
from unittest.mock import patch

from fastapi.testclient import TestClient

import main
from services.face_service import NoFaceDetectedError


class FaceMatchApiTest(unittest.TestCase):
    def setUp(self) -> None:
        main.INTERNAL_KEY = "test-key"
        main.SCORE_THRESHOLD = 0.70
        self.client = TestClient(main.app)

    def test_health_requires_internal_key(self) -> None:
        response = self.client.get("/health")

        self.assertEqual(response.status_code, 401)

    def test_health_returns_model(self) -> None:
        response = self.client.get("/health", headers={"X-Internal-Key": "test-key"})

        self.assertEqual(response.status_code, 200)
        self.assertEqual(response.json(), {"status": "ok", "model": "ArcFace"})

    def test_encode_returns_512_dimension_vector(self) -> None:
        with patch.object(main.face_service, "encode", return_value=[0.1] * 512):
            response = self.client.post(
                "/encode",
                headers={"X-Internal-Key": "test-key"},
                files={"file": ("face.jpg", b"fake image bytes", "image/jpeg")},
            )

        self.assertEqual(response.status_code, 200)
        payload = response.json()
        self.assertTrue(payload["has_face"])
        self.assertEqual(len(payload["encoding"]), 512)

    def test_encode_no_face_returns_422(self) -> None:
        with patch.object(main.face_service, "encode", side_effect=NoFaceDetectedError()):
            response = self.client.post(
                "/encode",
                headers={"X-Internal-Key": "test-key"},
                files={"file": ("blank.jpg", b"fake image bytes", "image/jpeg")},
            )

        self.assertEqual(response.status_code, 422)
        self.assertEqual(response.json()["detail"], "no_face_detected")

    def test_detect_face_returns_crop_metadata(self) -> None:
        with patch.object(
            main.face_service,
            "detect_face",
            return_value={
                "bounding_box": {"x": 10, "y": 20, "w": 80, "h": 80},
                "crop_image_base64": "ZmFrZS1qcGVn",
                "detected_gender": "MALE",
            },
        ):
            response = self.client.post(
                "/detect-face",
                headers={"X-Internal-Key": "test-key"},
                files={"file": ("face.jpg", b"fake image bytes", "image/jpeg")},
            )

        self.assertEqual(response.status_code, 200)
        payload = response.json()
        self.assertTrue(payload["has_face"])
        self.assertEqual(payload["bounding_box"]["w"], 80)
        self.assertEqual(payload["detected_gender"], "MALE")

    def test_detect_face_no_face_returns_422(self) -> None:
        with patch.object(main.face_service, "detect_face", side_effect=NoFaceDetectedError()):
            response = self.client.post(
                "/detect-face",
                headers={"X-Internal-Key": "test-key"},
                files={"file": ("blank.jpg", b"fake image bytes", "image/jpeg")},
            )

        self.assertEqual(response.status_code, 422)
        self.assertEqual(response.json()["detail"], "no_face_detected")

    def test_compare_returns_match_result(self) -> None:
        payload = {
            "encoding_a": [1.0] * 512,
            "encoding_b": [1.0] * 512,
        }

        response = self.client.post(
            "/compare",
            headers={"X-Internal-Key": "test-key"},
            json=payload,
        )

        self.assertEqual(response.status_code, 200)
        self.assertGreaterEqual(response.json()["score"], 0.99)
        self.assertTrue(response.json()["is_matched"])

    def test_request_timeout_returns_408(self) -> None:
        previous_timeout = main.REQUEST_TIMEOUT_SECONDS
        main.REQUEST_TIMEOUT_SECONDS = 0.01

        try:
            with patch.object(main.face_service, "compare", side_effect=lambda a, b: time.sleep(0.2)):
                response = self.client.post(
                    "/compare",
                    headers={"X-Internal-Key": "test-key"},
                    json={
                        "encoding_a": [1.0] * 512,
                        "encoding_b": [1.0] * 512,
                    },
                )
        finally:
            main.REQUEST_TIMEOUT_SECONDS = previous_timeout

        self.assertEqual(response.status_code, 408)
        self.assertEqual(response.json()["detail"], "request_timeout")


if __name__ == "__main__":
    unittest.main()
