<?php
/**
 * Copyright (C) 2024 la_rrp
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 *
 * @author    la_rrp
 * @copyright 2024 la_rrp
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License (AFL 3.0)
 */

declare(strict_types=1);

namespace La_rrp\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the la_rrp business logic.
 *
 * These tests exercise pure calculation functions that do not require a live
 * PrestaShop installation.
 */
class LaRrpLogicTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers – mirroring the logic in La_rrp::hookDisplayProductPriceBlock()
    // -------------------------------------------------------------------------

    /**
     * Calculate whether the current price is below the UVP.
     */
    private function isCheaper(float $currentPrice, float $uvp): bool
    {
        return $currentPrice < $uvp;
    }

    /**
     * Calculate the discount value (capped at 0).
     */
    private function discountValue(float $uvp, float $currentPrice): float
    {
        return max(0.0, $uvp - $currentPrice);
    }

    /**
     * Calculate the discount percentage as a rounded integer (capped at 0).
     */
    private function discountPercent(float $uvp, float $currentPrice): int
    {
        if ($uvp <= 0) {
            return 0;
        }

        $discount = $uvp - $currentPrice;

        return max(0, (int) round(($discount / $uvp) * 100));
    }

    /**
     * Determine whether the UVP block should be visible at all.
     */
    private function shouldShow(float $uvp, float $currentPrice): bool
    {
        if ($uvp <= 0) {
            return false;
        }

        if ($currentPrice <= 0) {
            return false;
        }

        // Negative discount guard
        if ($currentPrice < $uvp && $this->discountPercent($uvp, $currentPrice) <= 0) {
            return false;
        }

        return true;
    }

    // -------------------------------------------------------------------------
    // Test: UVP missing / zero → nothing shown
    // -------------------------------------------------------------------------

    public function testNullUvpHidesBlock(): void
    {
        $this->assertFalse($this->shouldShow(0.0, 80.0));
    }

    public function testZeroUvpHidesBlock(): void
    {
        $this->assertFalse($this->shouldShow(0.0, 100.0));
    }

    // -------------------------------------------------------------------------
    // Test: current_price < uvp → savings shown
    // -------------------------------------------------------------------------

    /**
     * @dataProvider savingsProvider
     */
    public function testSavingsDisplayed(
        float $uvp,
        float $currentPrice,
        float $expectedDiscount,
        int $expectedPercent
    ): void {
        $this->assertTrue($this->shouldShow($uvp, $currentPrice));
        $this->assertTrue($this->isCheaper($currentPrice, $uvp));
        $this->assertEqualsWithDelta($expectedDiscount, $this->discountValue($uvp, $currentPrice), 0.001);
        $this->assertSame($expectedPercent, $this->discountPercent($uvp, $currentPrice));
    }

    /**
     * @return array<string, array{float, float, float, int}>
     */
    public static function savingsProvider(): array
    {
        return [
            'standard 20 % discount'   => [100.0, 80.0, 20.0, 20],
            'small 5 % discount'       => [100.0, 95.0, 5.0, 5],
            'large 50 % discount'      => [200.0, 100.0, 100.0, 50],
            'fractional price'         => [99.99, 79.99, 20.0, 20],
        ];
    }

    // -------------------------------------------------------------------------
    // Test: current_price >= uvp → guarantee badge shown
    // -------------------------------------------------------------------------

    public function testPriceEqualToUvpShowsGuarantee(): void
    {
        $uvp = 100.0;
        $currentPrice = 100.0;

        $this->assertTrue($this->shouldShow($uvp, $currentPrice));
        $this->assertFalse($this->isCheaper($currentPrice, $uvp));
    }

    public function testPriceAboveUvpShowsGuarantee(): void
    {
        $uvp = 100.0;
        $currentPrice = 120.0;

        $this->assertTrue($this->shouldShow($uvp, $currentPrice));
        $this->assertFalse($this->isCheaper($currentPrice, $uvp));
    }

    // -------------------------------------------------------------------------
    // Test: edge cases
    // -------------------------------------------------------------------------

    public function testDiscountValueNeverNegative(): void
    {
        // Price above UVP must yield 0 discount, not negative
        $this->assertSame(0.0, $this->discountValue(100.0, 120.0));
    }

    public function testDiscountPercentNeverNegative(): void
    {
        $this->assertSame(0, $this->discountPercent(100.0, 120.0));
    }

    public function testDiscountPercentRoundsCorrectly(): void
    {
        // 33.33...% rounds to 33
        $this->assertSame(33, $this->discountPercent(300.0, 200.0));
    }

    public function testZeroCurrentPriceHidesBlock(): void
    {
        $this->assertFalse($this->shouldShow(100.0, 0.0));
    }

    public function testIsCheaperReturnsFalseWhenEqual(): void
    {
        $this->assertFalse($this->isCheaper(100.0, 100.0));
    }

    public function testIsCheaperReturnsTrueWhenBelow(): void
    {
        $this->assertTrue($this->isCheaper(80.0, 100.0));
    }
}
