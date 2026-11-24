import { test } from 'node:test';
import { deepStrictEqual } from 'node:assert/strict';
import { calculateCropBounds, calculateCropOutputSize } from '../../resources/js/crop-bounds.js';

test('positions a square crop across a wide image', () => {
    deepStrictEqual(calculateCropBounds(800, 600, 1, 1, 1, 0, 50), {
        x: 0, y: 0, width: 600, height: 600,
    });
    deepStrictEqual(calculateCropBounds(800, 600, 1, 1, 1, 100, 50), {
        x: 200, y: 0, width: 600, height: 600,
    });
});

test('zooms and positions a mobile crop on both axes', () => {
    deepStrictEqual(calculateCropBounds(900, 1600, 9, 16, 2, 100, 0), {
        x: 450, y: 0, width: 450, height: 800,
    });
});

test('supports fractional percentage positioning and zoom', () => {
    deepStrictEqual(calculateCropBounds(1000, 1000, 1, 1, 1.25, 33.25, 87.5), {
        x: 66.5, y: 175, width: 800, height: 800,
    });
});

test('keeps output ratios and bounds output dimensions', () => {
    deepStrictEqual(calculateCropOutputSize({ width: 4000, height: 2250 }, 16, 9), {
        width: 1920, height: 1080,
    });
    deepStrictEqual(calculateCropOutputSize({ width: 450, height: 800 }, 9, 16), {
        width: 450, height: 800,
    });
    deepStrictEqual(calculateCropOutputSize({ width: 600, height: 600 }, 1, 1), {
        width: 600, height: 600,
    });
});
