{{--
    Reusable Camera Capture Modal
    Include once per page. Controlled via window.GoatCamera (resources/js/goat-camera.js).

    The camera modal is shared across all capture buttons on the page.
    Each capture button calls GoatCamera.open({ modalId, videoId, canvasId, errorId, targetInputId, previewImgId }).
--}}
@push('scripts')
    <script src="{{ asset('js/goat-camera.js') }}"></script>

    <!-- Camera capture modal -->
    <div id="camera-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-2xl w-full overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">📸 Capture Photo</h3>
                <button type="button" onclick="GoatCamera.close(cameraConfig)" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
            </div>

            <div class="p-4">
                <p id="camera-error" class="hidden mb-3 rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-700"></p>

                <div class="relative bg-gray-900 rounded-xl overflow-hidden">
                    <video id="camera-video" class="w-full max-h-[60vh] object-contain" autoplay playsinline></video>
                </div>

                <!-- Hidden canvas for capture -->
                <canvas id="camera-canvas" class="hidden"></canvas>
            </div>

            <div class="flex items-center justify-center gap-3 px-4 py-4 bg-gray-50 border-t border-gray-100">
                <button type="button" onclick="GoatCamera.capture(cameraConfig)"
                    class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 active:bg-indigo-800">
                    📷 Capture
                </button>
                <button type="button" onclick="GoatCamera.close(cameraConfig)"
                    class="px-4 py-2.5 bg-gray-100 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-200">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <script>
        // Shared config — all camera buttons use this same modal/canvas
        const cameraConfig = {
            modalId:    'camera-modal',
            videoId:    'camera-video',
            canvasId:   'camera-canvas',
            errorId:    'camera-error',
        };

        /**
         * Open the camera, targeting a specific file input + preview image.
         * @param {string} targetInputId — the <input type="file"> to assign the captured image to
         * @param {string} previewImgId — the <img> element to show the preview
         */
        function openCamera(targetInputId, previewImgId) {
            GoatCamera.open({
                ...cameraConfig,
                targetInputId,
                previewImgId,
            });
        }
    </script>
@endpush
