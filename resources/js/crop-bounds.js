export function calculateCropBounds(imageWidth, imageHeight, ratioWidth, ratioHeight, zoom, horizontal, vertical) {
    const ratio = ratioWidth / ratioHeight;
    const baseWidth = imageWidth / imageHeight > ratio ? imageHeight * ratio : imageWidth;
    const width = baseWidth / zoom;
    const height = baseWidth / ratio / zoom;

    return {
        x: (imageWidth - width) * horizontal / 100,
        y: (imageHeight - height) * vertical / 100,
        width,
        height,
    };
}

export function calculateCropOutputSize(bounds, ratioWidth, ratioHeight) {
    const units = Math.max(1, Math.floor(Math.min(
        bounds.width / ratioWidth,
        bounds.height / ratioHeight,
        1920 / Math.max(ratioWidth, ratioHeight),
    )));

    return { width: units * ratioWidth, height: units * ratioHeight };
}
