<?php

use App\Services\Analytics\AnalyticsMath;

describe('linear regression', function () {
    it('recovers an exact positive slope', function () {
        $fit = AnalyticsMath::linearRegression([0.0, 10.0, 20.0, 30.0]);

        expect($fit['slope'])->toBe(10.0)
            ->and($fit['intercept'])->toBe(0.0)
            ->and($fit['r2'])->toBe(1.0);
    });

    it('recovers slope and intercept with an offset', function () {
        $fit = AnalyticsMath::linearRegression([5.0, 15.0, 25.0, 35.0]);

        expect($fit['slope'])->toBe(10.0)
            ->and($fit['intercept'])->toBe(5.0);
    });

    it('computes a negative slope for a declining series', function () {
        $fit = AnalyticsMath::linearRegression([40.0, 30.0, 20.0, 10.0]);

        expect($fit['slope'])->toBe(-10.0);
    });

    it('returns null below three points', function () {
        expect(AnalyticsMath::linearRegression([1.0, 2.0]))->toBeNull();
    });

    it('returns null for a constant series (no variance)', function () {
        expect(AnalyticsMath::linearRegression([7.0, 7.0, 7.0, 7.0]))->toBeNull();
    });

    it('clamps r2 into 0..1 for noisy data', function () {
        $fit = AnalyticsMath::linearRegression([1.0, 12.0, 3.0, 28.0, 9.0]);

        expect($fit['r2'])->toBeGreaterThan(0.0)
            ->and($fit['r2'])->toBeLessThanOrEqual(1.0);
    });
});

describe('moving average', function () {
    it('returns nulls until the window fills', function () {
        $result = AnalyticsMath::movingAverage([1.0, 2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0], 3);

        expect($result[0])->toBeNull()
            ->and($result[1])->toBeNull()
            ->and($result[2])->toBe(2.0)
            ->and($result[7])->toBe(7.0);
    });

    it('returns all nulls for an empty series', function () {
        expect(AnalyticsMath::movingAverage([], 7))->toBe([]);
    });
});

describe('cumulative sums', function () {
    it('produces a running total', function () {
        expect(AnalyticsMath::cumulative([10.0, 0.0, 5.0]))->toBe([10.0, 10.0, 15.0]);
    });
});

describe('percentiles', function () {
    it('returns the middle value for an odd count', function () {
        expect(AnalyticsMath::median([3.0, 1.0, 2.0]))->toBe(2.0);
    });

    it('interpolates the median for an even count', function () {
        expect(AnalyticsMath::median([1.0, 2.0, 3.0, 4.0]))->toBe(2.5);
    });

    it('computes p90 by linear interpolation', function () {
        $values = range(1, 10);

        expect(AnalyticsMath::percentile(array_map('floatval', $values), 90))->toBe(9.1);
    });

    it('returns null for an empty set', function () {
        expect(AnalyticsMath::percentile([], 50))->toBeNull()
            ->and(AnalyticsMath::mean([]))->toBeNull()
            ->and(AnalyticsMath::median([]))->toBeNull();
    });
});

describe('z-scores', function () {
    it('flags an extreme outlier above two deviations', function () {
        $values = array_fill(0, 11, 10.0);
        $values[10] = 50.0;

        $scores = AnalyticsMath::zScores($values);

        expect($scores[0]['flagged'])->toBeFalse()
            ->and($scores[10]['flagged'])->toBeTrue()
            ->and($scores[10]['z'])->toBeGreaterThan(2.0);
    });

    it('never flags below ten observations', function () {
        $values = [0.0, 0.0, 0.0, 0.0, 100.0];

        $scores = AnalyticsMath::zScores($values);

        expect($scores[4]['flagged'])->toBeFalse();
    });

    it('never flags a constant series', function () {
        $scores = AnalyticsMath::zScores(array_fill(0, 12, 5.0));

        expect(collect($scores)->contains(fn ($score): bool => $score['flagged']))->toBeFalse();
    });
});

describe('percentage change', function () {
    it('computes the relative change', function () {
        expect(AnalyticsMath::percentageChange(150.0, 100.0))->toBe(50.0);
    });

    it('returns null when the previous period is zero', function () {
        expect(AnalyticsMath::percentageChange(50.0, 0.0))->toBeNull();
    });
});

describe('nice axis ceiling', function () {
    it('rounds up to clean 1/2/5 steps', function () {
        expect(AnalyticsMath::niceCeil(60.0))->toBe(100.0)
            ->and(AnalyticsMath::niceCeil(320.0))->toBe(500.0)
            ->and(AnalyticsMath::niceCeil(8000.0))->toBe(10000.0)
            ->and(AnalyticsMath::niceCeil(0.0))->toBe(1.0);
    });
});
