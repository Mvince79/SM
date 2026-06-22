# Sprint 3 Face Match AI Service

FastAPI microservice for face encoding and ArcFace cosine matching.

The default detector order is `mtcnn,opencv` because OpenCV can miss clear child
faces in some poses. Override it with `DETECTOR_BACKENDS` if needed.

## Setup

```powershell
cd ai-service
python -m venv venv
.\venv\Scripts\activate
pip install -r requirements.txt
copy .env.example .env
python main.py
```

The service binds to `127.0.0.1:8001` only.

## Endpoints

All endpoints require `X-Internal-Key`.

```powershell
curl -H "X-Internal-Key: sprint3_internal_secret_key_2026" http://127.0.0.1:8001/health
```

- `GET /health`
- `POST /encode` with `multipart/form-data` field `file`
- `POST /compare` with `encoding_a` and `encoding_b`
