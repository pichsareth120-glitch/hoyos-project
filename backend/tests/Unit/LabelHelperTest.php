<?php

use App\Support\LabelHelper;

test('label helper humanizes entity names and action tokens correctly', function () {
    expect(LabelHelper::humanize('product_variants'))->toBe('Product variants');
    expect(LabelHelper::humanize('product_images'))->toBe('Product images');
    expect(LabelHelper::humanize('update_status'))->toBe('Update status');
    expect(LabelHelper::humanize('cod'))->toBe('COD');
    expect(LabelHelper::humanize('aba_khqr'))->toBe('ABA KHQR');
    expect(LabelHelper::humanize('order_confirmed'))->toBe('Order confirmed');
    expect(LabelHelper::humanize('out_for_delivery'))->toBe('Out for delivery');
    expect(LabelHelper::humanize('soft_delete'))->toBe('Soft delete');
});

test('label helper handles null and empty string safely', function () {
    expect(LabelHelper::humanize(null))->toBe('');
    expect(LabelHelper::humanize(''))->toBe('');
});

test('label helper capitalizes default underscore tokens', function () {
    expect(LabelHelper::humanize('custom_action_name'))->toBe('Custom action name');
});
