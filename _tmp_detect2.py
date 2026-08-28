import sys, time
sys.path.insert(0, 'ai-service')
from services.face_service import FaceService
from PIL import Image

fs = FaceService()
for maxd in [320, 640]:
    im = Image.open(r'resized640.jpg')
    im.thumbnail((maxd, maxd))
    fn = f'_tmp_d{maxd}.jpg'
    im.save(fn)
    t = time.time()
    try:
        r = fs.detect_face(fn)
        print('maxd=%d OK took=%.1fs' % (maxd, time.time() - t))
    except Exception as e:
        print('maxd=%d ERR after %.1fs: %r' % (maxd, time.time() - t, e))
