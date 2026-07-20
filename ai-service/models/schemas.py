from __future__ import annotations

from pydantic import BaseModel, Field, field_validator


class HealthResponse(BaseModel):
    status: str
    model: str


class EncodeResponse(BaseModel):
    encoding: list[float] = Field(min_length=512, max_length=512)
    has_face: bool
    model: str


class DetectFaceResponse(BaseModel):
    has_face: bool
    bounding_box: dict[str, int] | None = None
    crop_image_base64: str | None = None
    detected_gender: str = "UNKNOWN"
    model: str


class CompareRequest(BaseModel):
    encoding_a: list[float] = Field(min_length=512, max_length=512)
    encoding_b: list[float] = Field(min_length=512, max_length=512)

    @field_validator("encoding_a", "encoding_b")
    @classmethod
    def all_values_are_floats(cls, value: list[float]) -> list[float]:
        return [float(item) for item in value]


class CompareResponse(BaseModel):
    score: float = Field(ge=0.0, le=1.0)
    is_matched: bool
    model: str


class VerifyResponse(BaseModel):
    score: float = Field(ge=0.0, le=1.0)
    is_matched: bool
    model: str
