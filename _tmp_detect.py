import sys, time
sys.path.insert(0, 'ai-service')
from services.face_service import FaceService

fs = FaceService()
img = r'storage/app/public/missing/51/ca1b9dd0-6ef0-4f20-a8f3-9235fda0d417.jpg'
t = time.time()
try:
    r = fs.detect_face(img)
    print('DETECT OK took=%.1fs keys=%s' % (time.time() - t, list(r.keys())))
except Exception as e:
    print('DETECT ERR after %.1fs: %r' % (time.time() - t, e))
