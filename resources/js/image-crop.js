import { calculateCropBounds, calculateCropOutputSize } from './crop-bounds';

const maxImageSize = 2 * 1024 * 1024;
const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
const initializedForms = new WeakSet();

function createCropControl(container) {
    const input = container.querySelector('input[type="file"]');
    const current = container.querySelector('[data-crop-current]');
    const edit = container.querySelector('[data-crop-edit]');
    const editor = container.querySelector('[data-crop-editor]');
    const canvas = container.querySelector('[data-crop-canvas]');
    const zoom = container.querySelector('[data-crop-zoom]');
    const horizontal = container.querySelector('[data-crop-horizontal]');
    const vertical = container.querySelector('[data-crop-vertical]');
    const error = container.querySelector('[data-crop-error]');
    const dismiss = container.querySelector('[data-crop-dismiss]');
    const [ratioWidth, ratioHeight] = container.dataset.cropRatio.split(':').map(Number);
    const controls = [zoom, horizontal, vertical];
    let image = null;
    let imageType = null;
    let loadVersion = 0;

    function showError(message) {
        error.textContent = message;
        error.hidden = false;
        dismiss.hidden = false;
        input.setCustomValidity(message);
    }

    function clearError() {
        error.textContent = '';
        error.hidden = true;
        dismiss.hidden = true;
        input.setCustomValidity('');
    }

    function updateRange(control, updatePercentage = true) {
        const percentage = Number((Number(control.value) * (control === zoom ? 100 : 1)).toFixed(2));
        control.value = control === zoom ? percentage / 100 : percentage;
        const progress = (Number(control.value) - Number(control.min)) / (Number(control.max) - Number(control.min)) * 100;
        control.style.setProperty('--crop-progress', `${progress}%`);
        control.setAttribute('aria-valuetext', `${percentage}%`);
        if (updatePercentage) {
            control.closest('[data-crop-setting]').querySelector('[data-crop-percentage]').value = percentage;
        }
    }

    function setPercentageInputsEnabled(enabled) {
        for (const control of controls) {
            const setting = control.closest('[data-crop-setting]');
            const percentage = setting.querySelector('[data-crop-percentage]');
            percentage.disabled = !enabled;
            percentage.removeAttribute('aria-invalid');
            setting.querySelector('[data-crop-percentage-error]').hidden = true;
        }
    }

    function reset() {
        loadVersion++;
        image = null;
        imageType = null;
        input.value = '';
        editor.hidden = true;
        setPercentageInputsEnabled(false);
        edit.hidden = !container.dataset.cropSource;
        if (current) {
            current.hidden = false;
        }
        clearError();
    }

    function cropBounds() {
        return calculateCropBounds(
            image.naturalWidth,
            image.naturalHeight,
            ratioWidth,
            ratioHeight,
            Number(zoom.value),
            Number(horizontal.value),
            Number(vertical.value),
        );
    }

    function draw(target) {
        const bounds = cropBounds();
        const context = target.getContext('2d');

        if (!context) {
            throw new Error('Your browser could not create the image crop.');
        }

        context.clearRect(0, 0, target.width, target.height);
        context.drawImage(image, bounds.x, bounds.y, bounds.width, bounds.height, 0, 0, target.width, target.height);
    }

    async function load(source) {
        const version = ++loadVersion;
        image = null;
        editor.hidden = true;
        setPercentageInputsEnabled(false);
        edit.hidden = true;
        showError('Loading image for cropping...');

        try {
            let file = source;
            if (typeof source === 'string') {
                const response = await fetch(source);
                if (!response.ok) {
                    throw new Error('The current image could not be loaded. Please select it again.');
                }
                file = await response.blob();
            }

            if (!allowedTypes.includes(file.type)) {
                throw new Error('Choose a JPEG, PNG or WebP image.');
            }
            if (file.size > maxImageSize) {
                throw new Error('The image must be no larger than 2 MB.');
            }

            const objectUrl = URL.createObjectURL(file);
            const loaded = new Image();
            try {
                loaded.src = objectUrl;
                await loaded.decode();
            } finally {
                URL.revokeObjectURL(objectUrl);
            }

            if (version !== loadVersion) {
                return;
            }

            image = loaded;
            imageType = file.type;
            zoom.value = '1';
            horizontal.value = '50';
            vertical.value = '50';
            for (const control of controls) {
                updateRange(control);
            }
            const previewUnits = 640 / Math.max(ratioWidth, ratioHeight);
            canvas.width = Math.round(ratioWidth * previewUnits);
            canvas.height = Math.round(ratioHeight * previewUnits);
            draw(canvas);
            if (current) {
                current.hidden = true;
            }
            editor.hidden = false;
            setPercentageInputsEnabled(true);
            clearError();
        } catch (exception) {
            if (version !== loadVersion) {
                return;
            }
            image = null;
            showError(exception instanceof Error && exception.message
                ? exception.message
                : 'The image could not be loaded for cropping.');
            if (current) {
                current.hidden = false;
            }
            edit.hidden = !container.dataset.cropSource;
        }
    }

    input.addEventListener('change', () => {
        if (input.files.length) {
            load(input.files[0]);
        } else {
            reset();
        }
    });
    edit.addEventListener('click', () => {
        input.value = '';
        load(container.dataset.cropSource);
    });
    container.querySelector('[data-crop-cancel]').addEventListener('click', reset);
    dismiss.addEventListener('click', reset);
    for (const control of controls) {
        const setting = control.closest('[data-crop-setting]');
        const percentage = setting.querySelector('[data-crop-percentage]');
        const percentageError = setting.querySelector('[data-crop-percentage-error]');

        control.addEventListener('input', () => {
            clearError();
            updateRange(control);
            percentage.removeAttribute('aria-invalid');
            percentageError.hidden = true;
            draw(canvas);
        });

        percentage.addEventListener('input', () => {
            const valid = percentage.validity.valid;
            percentageError.hidden = valid;
            percentage.setAttribute('aria-invalid', String(!valid));
            if (!valid) {
                return;
            }

            clearError();
            control.value = control === zoom ? Number(percentage.value) / 100 : percentage.value;
            updateRange(control, false);
            draw(canvas);
        });
    }

    edit.hidden = !container.dataset.cropSource;

    return {
        async prepare() {
            if (!image) {
                return null;
            }

            const bounds = cropBounds();
            const size = calculateCropOutputSize(bounds, ratioWidth, ratioHeight);
            const output = document.createElement('canvas');
            output.width = size.width;
            output.height = size.height;
            draw(output);

            const blob = await new Promise((resolve) => output.toBlob(resolve, imageType, 0.9));
            if (!blob || blob.type !== imageType) {
                throw new Error('Your browser could not save this image format.');
            }
            if (blob.size > maxImageSize) {
                throw new Error('The cropped image exceeds 2 MB. Select a smaller image or adjust the zoom.');
            }

            const extensions = { 'image/jpeg': 'jpg', 'image/png': 'png', 'image/webp': 'webp' };
            const cropped = new File([blob], `cropped-${input.id}.${extensions[imageType]}`, { type: imageType });
            return { input, file: cropped };
        },
        showError,
    };
}

function initializeImageCropping() {
    document.querySelectorAll('form').forEach((form) => {
        const containers = form.querySelectorAll('[data-image-crop]');
        if (!containers.length || initializedForms.has(form)) {
            return;
        }

        initializedForms.add(form);
        const controls = Array.from(containers, createCropControl);
        let saving = false;

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (saving) {
                return;
            }
            saving = true;
            const submit = form.querySelector('button[type="submit"]');
            const originalText = submit.textContent;
            submit.disabled = true;
            submit.textContent = 'Preparing images...';

            const prepared = [];
            let activeControl;
            try {
                for (const control of controls) {
                    activeControl = control;
                    const result = await control.prepare();
                    if (result) {
                        prepared.push(result);
                    }
                }
                for (const { input, file } of prepared) {
                    const files = new DataTransfer();
                    files.items.add(file);
                    input.files = files.files;
                }
                HTMLFormElement.prototype.submit.call(form);
            } catch (exception) {
                activeControl.showError(exception instanceof Error && exception.message
                    ? exception.message
                    : 'The cropped image could not be saved.');
                submit.disabled = false;
                submit.textContent = originalText;
                saving = false;
            }
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeImageCropping);
} else {
    initializeImageCropping();
}
document.addEventListener('livewire:navigated', initializeImageCropping);
