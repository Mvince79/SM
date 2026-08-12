let registered = false;

const registerKycImageCapture = () => {
    if (registered || !window.Alpine) return;

    registered = true;

    Alpine.data(
        'kycImageCapture',
        (fieldName, facingMode = 'environment') => ({
            stage: 'choice',
            cameraSupported: null,
            permissionError: null,
            stream: null,

            init() {
                this.cameraSupported = !!(
                    navigator.mediaDevices &&
                    navigator.mediaDevices.getUserMedia
                );
            },

            async openCamera() {
                if (this.cameraSupported === false) return;

                this.permissionError = null;

                try {
                    this.stream =
                        await navigator.mediaDevices.getUserMedia({
                            video: {
                                facingMode,
                            },
                        });

                    this.cameraSupported = true;
                    this.stage = 'camera';

                    this.$nextTick(async () => {
                        if (!this.$refs.video) return;

                        this.$refs.video.srcObject = this.stream;

                        try {
                            await this.$refs.video.play();
                        } catch (error) {
                            console.warn(
                                'Không thể phát camera:',
                                error
                            );
                        }
                    });
                } catch (error) {
                    console.warn(
                        'Camera không khả dụng:',
                        error.name,
                        error.message
                    );

                    if (
                        error.name === 'NotFoundError' ||
                        error.name === 'OverconstrainedError'
                    ) {
                        this.cameraSupported = false;
                    } else {
                        this.permissionError = 'denied';
                    }
                }
            },

            capture() {
                const video = this.$refs.video;
                const canvas = this.$refs.canvas;

                if (!video || !canvas) return;

                if (
                    !video.videoWidth ||
                    !video.videoHeight
                ) {
                    console.warn(
                        'Camera chưa sẵn sàng để chụp.'
                    );
                    return;
                }

                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;

                const context = canvas.getContext('2d');

                if (!context) {
                    console.warn(
                        'Không thể lấy canvas context.'
                    );
                    return;
                }

                context.drawImage(
                    video,
                    0,
                    0,
                    canvas.width,
                    canvas.height
                );

                this.stopStream();
                this.stage = 'uploading';

                canvas.toBlob(
                    async (blob) => {
                        if (!blob) {
                            this.stage = 'choice';
                            return;
                        }

                        const file = new File(
                            [blob],
                            `${fieldName}.jpg`,
                            {
                                type: 'image/jpeg',
                            }
                        );

                        try {
                            if (fieldName === 'selfie') {
                                await this.$wire.set(
                                    'selfieCapturedByCamera',
                                    true
                                );
                            }

                            this.$wire.upload(
                                fieldName,
                                file,
                                () => {
                                    // Upload thành công
                                },
                                () => {
                                    // Upload thất bại
                                    this.stage = 'choice';
                                }
                            );
                        } catch (error) {
                            console.error(
                                'Upload ảnh camera thất bại:',
                                error
                            );

                            this.stage = 'choice';
                        }
                    },
                    'image/jpeg',
                    0.9
                );
            },

            cancelCamera() {
                this.stopStream();
                this.stage = 'choice';
            },

            retake() {
                this.stopStream();

                this.stage = 'choice';

                if (fieldName === 'selfie') {
                    this.$wire.set(
                        'selfieCapturedByCamera',
                        false
                    );
                }

                this.$wire.set(fieldName, null);
            },

            stopStream() {
                if (!this.stream) return;

                this.stream
                    .getTracks()
                    .forEach((track) => track.stop());

                this.stream = null;
            },
        })
    );
};

const initializePendingKycComponents = () => {
    if (!window.Alpine) return;

    document
        .querySelectorAll(
            '[x-data^="kycImageCapture("]'
        )
        .forEach((element) => {
            const initialized =
                element._x_dataStack?.some(
                    (scope) =>
                        typeof scope.openCamera ===
                        'function'
                );

            if (initialized) return;

            try {
                window.Alpine.destroyTree(element);
                window.Alpine.initTree(element);
            } catch (error) {
                console.error(
                    'Không thể initialize KYC component:',
                    error
                );
            }
        });
};

const bootKycImageCapture = () => {
    registerKycImageCapture();

    queueMicrotask(
        initializePendingKycComponents
    );
};

if (window.Alpine) {
    bootKycImageCapture();
} else {
    document.addEventListener(
        'alpine:init',
        bootKycImageCapture,
        {
            once: true,
        }
    );
}

document.addEventListener(
    'livewire:navigated',
    bootKycImageCapture
);