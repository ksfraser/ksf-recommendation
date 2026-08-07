<?php

declare(strict_types=1);

namespace Ksfraser\Recommendation;

use KSFII\CanadaLife\Calculations\CalculationEngineInterface;
use KSFII\CanadaLife\Calculations\CalculationContext;
use KSFII\CanadaLife\Calculations\CalculationResult;
use KSFII\CanadaLife\Calculations\CalculationException;

/**
 * Risk-Adjusted Investment Recommendation Engine
 *
 * Generates personalized investment recommendations based on client risk tolerance,
 * time horizon, financial goals, and market conditions. Provides goal-based allocations
 * with diversification optimization and tax-advantaged account recommendations.
 *
 * @requirement FR-112: Risk-Adjusted Investment Recommendation Engine
 * @author AI Assistant
 * @version 1.0
 * @since 4 November 2025
 */
class RiskAdjustedRecommendationEngine implements CalculationEngineInterface
{
    /**
     * Risk tolerance levels
     */
    const RISK_VERY_CONSERVATIVE = 'very_conservative';
    const RISK_CONSERVATIVE = 'conservative';
    const RISK_MODERATE = 'moderate';
    const RISK_AGGRESSIVE = 'aggressive';
    const RISK_VERY_AGGRESSIVE = 'very_aggressive';

    /**
     * Time horizons
     */
    const HORIZON_VERY_SHORT = 'very_short'; // < 1 year
    const HORIZON_SHORT = 'short'; // 1-3 years
    const HORIZON_MEDIUM = 'medium'; // 3-7 years
    const HORIZON_LONG = 'long'; // 7-15 years
    const HORIZON_VERY_LONG = 'very_long'; // 15+ years

    /**
     * Financial goals
     */
    const GOAL_RETIREMENT = 'retirement';
    const GOAL_EDUCATION = 'education';
    const GOAL_WEALTH_PRESERVATION = 'wealth_preservation';
    const GOAL_INCOME_GENERATION = 'income_generation';
    const GOAL_GROWTH = 'growth';
    const GOAL_ESTATE = 'estate';

    /**
     * Market conditions
     */
    const MARKET_BULL = 'bull';
    const MARKET_BEAR = 'bear';
    const MARKET_SIDEWAYS = 'sideways';
    const MARKET_VOLATILE = 'volatile';

    /**
     * Account types
     */
    const ACCOUNT_RRSP = 'rrsp';
    const ACCOUNT_TFSA = 'tfsa';
    const ACCOUNT_NON_REGISTERED = 'non_registered';

    /**
     * Get the calculation type identifier
     *
     * @return string
     */
    public function getCalculationType(): string
    {
        return 'risk_adjusted_recommendations';
    }

    /**
     * Get required parameters for this calculation
     *
     * @return array<string, ParameterDefinition>
     */
    public function getRequiredParameters(): array
    {
        return [
            'client_age' => new ParameterDefinition(
                'client_age',
                'integer',
                'Client\'s current age in years',
                ['min' => 18, 'max' => 100]
            ),
            'risk_tolerance' => new ParameterDefinition(
                'risk_tolerance',
                'string',
                'Client\'s risk tolerance level',
                ['enum' => [self::RISK_VERY_CONSERVATIVE, self::RISK_CONSERVATIVE,
                           self::RISK_MODERATE, self::RISK_AGGRESSIVE, self::RISK_VERY_AGGRESSIVE]]
            ),
            'time_horizon' => new ParameterDefinition(
                'time_horizon',
                'string',
                'Investment time horizon',
                ['enum' => [self::HORIZON_VERY_SHORT, self::HORIZON_SHORT,
                           self::HORIZON_MEDIUM, self::HORIZON_LONG, self::HORIZON_VERY_LONG]]
            ),
            'primary_goal' => new ParameterDefinition(
                'primary_goal',
                'string',
                'Primary financial goal',
                ['enum' => [self::GOAL_RETIREMENT, self::GOAL_EDUCATION,
                           self::GOAL_WEALTH_PRESERVATION, self::GOAL_INCOME_GENERATION,
                           self::GOAL_GROWTH, self::GOAL_ESTATE]]
            ),
            'current_portfolio_value' => new ParameterDefinition(
                'current_portfolio_value',
                'float',
                'Current total portfolio value in CAD',
                ['min' => 0]
            ),
            'annual_income' => new ParameterDefinition(
                'annual_income',
                'float',
                'Client\'s annual income in CAD',
                ['min' => 0]
            ),
            'annual_expenses' => new ParameterDefinition(
                'annual_expenses',
                'float',
                'Client\'s annual expenses in CAD',
                ['min' => 0]
            )
        ];
    }

    /**
     * Get optional parameters for this calculation
     *
     * @return array<string, ParameterDefinition>
     */
    public function getOptionalParameters(): array
    {
        return [
            'market_condition' => new ParameterDefinition(
                'market_condition',
                'string',
                'Current market condition assessment',
                ['enum' => [self::MARKET_BULL, self::MARKET_BEAR,
                           self::MARKET_SIDEWAYS, self::MARKET_VOLATILE],
                 'default' => self::MARKET_SIDEWAYS]
            ),
            'retirement_age' => new ParameterDefinition(
                'retirement_age',
                'integer',
                'Target retirement age',
                ['min' => 50, 'max' => 75, 'default' => 65]
            ),
            'target_income_replacement' => new ParameterDefinition(
                'target_income_replacement',
                'float',
                'Target retirement income as percentage of pre-retirement income',
                ['min' => 50, 'max' => 100, 'default' => 70]
            ),
            'has_dependents' => new ParameterDefinition(
                'has_dependents',
                'boolean',
                'Whether client has dependents requiring financial support',
                ['default' => false]
            ),
            'emergency_fund_months' => new ParameterDefinition(
                'emergency_fund_months',
                'integer',
                'Emergency fund coverage in months',
                ['min' => 0, 'max' => 24, 'default' => 6]
            ),
            'tax_bracket' => new ParameterDefinition(
                'tax_bracket',
                'float',
                'Client\'s marginal tax rate as decimal',
                ['min' => 0, 'max' => 0.53, 'default' => 0.3]
            ),
            'inflation_rate' => new ParameterDefinition(
                'inflation_rate',
                'float',
                'Expected inflation rate as decimal',
                ['min' => 0, 'max' => 0.1, 'default' => 0.02]
            ),
            'investment_experience_years' => new ParameterDefinition(
                'investment_experience_years',
                'integer',
                'Years of investment experience',
                ['min' => 0, 'max' => 50, 'default' => 5]
            )
        ];
    }

    /**
     * Validate the calculation context
     *
     * @param CalculationContext $context
     * @return ValidationResult
     */
    public function validate(CalculationContext $context): ValidationResult
    {
        $errors = [];
        $warnings = [];

        // Validate required parameters
        $requiredParams = $this->getRequiredParameters();
        foreach ($requiredParams as $paramName => $definition) {
            if (!$context->hasParameter($paramName)) {
                $errors[] = "Required parameter '{$paramName}' is missing";
            }
        }

        // Validate parameter values
        if ($context->hasParameter('client_age')) {
            $age = $context->getParameter('client_age');
            if ($age < 18 || $age > 100) {
                $errors[] = "Client age must be between 18 and 100";
            }
        }

        if ($context->hasParameter('time_horizon')) {
            $horizon = $context->getParameter('time_horizon');
            $validHorizons = [self::HORIZON_VERY_SHORT, self::HORIZON_SHORT,
                            self::HORIZON_MEDIUM, self::HORIZON_LONG, self::HORIZON_VERY_LONG];
            if (!in_array($horizon, $validHorizons)) {
                $errors[] = "Invalid time horizon specified";
            }
        }

        if ($context->hasParameter('risk_tolerance')) {
            $risk = $context->getParameter('risk_tolerance');
            $validRisks = [self::RISK_VERY_CONSERVATIVE, self::RISK_CONSERVATIVE,
                          self::RISK_MODERATE, self::RISK_AGGRESSIVE, self::RISK_VERY_AGGRESSIVE];
            if (!in_array($risk, $validRisks)) {
                $errors[] = "Invalid risk tolerance specified";
            }
        }

        // Business rule validations
        if ($context->hasParameter('annual_expenses') && $context->hasParameter('annual_income')) {
            $expenses = $context->getParameter('annual_expenses');
            $income = $context->getParameter('annual_income');
            if ($expenses > $income * 1.2) {
                $warnings[] = "Annual expenses exceed 120% of income - consider expense reduction strategies";
            }
        }

        if ($context->hasParameter('client_age') && $context->hasParameter('retirement_age')) {
            $currentAge = $context->getParameter('client_age');
            $retirementAge = $context->getParameter('retirement_age');
            if ($retirementAge - $currentAge < 5) {
                $warnings[] = "Less than 5 years until retirement - consider conservative investment approach";
            }
        }

        return new ValidationResult(
            empty($errors),
            $errors,
            $warnings
        );
    }

    /**
     * Perform the risk-adjusted recommendation calculation
     *
     * @param CalculationContext $context
     * @return CalculationResult
     * @throws CalculationException
     */
    public function calculate(CalculationContext $context): CalculationResult
    {
        $validation = $this->validate($context);
        if (!$validation->isValid()) {
            throw new CalculationException(
                'Validation failed: ' . implode(', ', $validation->getErrors())
            );
        }

        try {
            // Extract parameters
            $clientAge = $context->getParameter('client_age');
            $riskTolerance = $context->getParameter('risk_tolerance');
            $timeHorizon = $context->getParameter('time_horizon');
            $primaryGoal = $context->getParameter('primary_goal');
            $portfolioValue = $context->getParameter('current_portfolio_value');
            $annualIncome = $context->getParameter('annual_income');
            $annualExpenses = $context->getParameter('annual_expenses');

            // Optional parameters with defaults
            $marketCondition = $context->getParameter('market_condition', self::MARKET_SIDEWAYS);
            $retirementAge = $context->getParameter('retirement_age', 65);
            $incomeReplacement = $context->getParameter('target_income_replacement', 70) / 100;
            $hasDependents = $context->getParameter('has_dependents', false);
            $emergencyFundMonths = $context->getParameter('emergency_fund_months', 6);
            $taxBracket = $context->getParameter('tax_bracket', 0.3);
            $inflationRate = $context->getParameter('inflation_rate', 0.02);
            $investmentExperience = $context->getParameter('investment_experience_years', 5);

            // Calculate emergency fund requirement
            $monthlyExpenses = $annualExpenses / 12;
            $emergencyFundTarget = $monthlyExpenses * $emergencyFundMonths;

            // Calculate investable amount (after emergency fund)
            $investableAmount = max(0, $portfolioValue - $emergencyFundTarget);

            // Generate asset allocation recommendations
            $allocation = $this->calculateAssetAllocation(
                $riskTolerance, $timeHorizon, $clientAge, $primaryGoal, $marketCondition
            );

            // Generate account type recommendations
            $accountRecommendations = $this->calculateAccountTypeRecommendations(
                $clientAge, $taxBracket, $timeHorizon, $primaryGoal, $annualIncome
            );

            // Calculate retirement projections if applicable
            $retirementProjection = null;
            if ($primaryGoal === self::GOAL_RETIREMENT || $clientAge < $retirementAge) {
                $retirementProjection = $this->calculateRetirementProjection(
                    $clientAge, $retirementAge, $annualIncome, $annualExpenses,
                    $incomeReplacement, $investableAmount, $allocation, $inflationRate
                );
            }

            // Generate diversification recommendations
            $diversification = $this->calculateDiversificationRecommendations(
                $allocation, $riskTolerance, $timeHorizon
            );

            // Calculate rebalancing triggers
            $rebalancingTriggers = $this->calculateRebalancingTriggers($allocation);

            // Generate risk warnings and considerations
            $riskConsiderations = $this->generateRiskConsiderations(
                $riskTolerance, $timeHorizon, $clientAge, $marketCondition, $hasDependents
            );

            // Calculate confidence score
            $confidenceScore = $this->calculateConfidenceScore(
                $investmentExperience, $portfolioValue, $timeHorizon
            );

            $result = [
                'recommendation_summary' => $this->generateRecommendationSummary(
                    $allocation, $primaryGoal, $riskTolerance, $timeHorizon
                ),
                'asset_allocation' => $allocation,
                'account_type_recommendations' => $accountRecommendations,
                'emergency_fund_status' => [
                    'current_coverage_months' => $portfolioValue > 0 ? ($portfolioValue / $monthlyExpenses) : 0,
                    'target_months' => $emergencyFundMonths,
                    'recommended_action' => $portfolioValue < $emergencyFundTarget ? 'build_emergency_fund' : 'maintain_current'
                ],
                'investable_amount' => $investableAmount,
                'retirement_projection' => $retirementProjection,
                'diversification_recommendations' => $diversification,
                'rebalancing_triggers' => $rebalancingTriggers,
                'risk_considerations' => $riskConsiderations,
                'confidence_score' => $confidenceScore,
                'implementation_priority' => $this->calculateImplementationPriority(
                    $clientAge, $timeHorizon, $portfolioValue, $primaryGoal
                ),
                'next_review_date' => $this->calculateNextReviewDate($timeHorizon, $marketCondition)
            ];

            return new CalculationResult(
                true,
                $result,
                'Risk-adjusted investment recommendations generated successfully'
            );

        } catch (\Exception $e) {
            throw new CalculationException(
                'Failed to generate risk-adjusted recommendations: ' . $e->getMessage()
            );
        }
    }

    /**
     * Calculate asset allocation based on risk tolerance and other factors
     */
    private function calculateAssetAllocation(
        string $riskTolerance,
        string $timeHorizon,
        int $clientAge,
        string $primaryGoal,
        string $marketCondition
    ): array {
        // Base allocations by risk tolerance
        $baseAllocations = [
            self::RISK_VERY_CONSERVATIVE => ['fixed_income' => 80, 'equities' => 15, 'cash' => 5],
            self::RISK_CONSERVATIVE => ['fixed_income' => 60, 'equities' => 30, 'cash' => 10],
            self::RISK_MODERATE => ['fixed_income' => 40, 'equities' => 50, 'cash' => 10],
            self::RISK_AGGRESSIVE => ['fixed_income' => 20, 'equities' => 70, 'cash' => 10],
            self::RISK_VERY_AGGRESSIVE => ['fixed_income' => 10, 'equities' => 80, 'cash' => 10]
        ];

        $allocation = $baseAllocations[$riskTolerance];

        // Adjust for time horizon
        $yearsToHorizon = $this->convertHorizonToYears($timeHorizon);
        if ($yearsToHorizon < 3) {
            // Short horizon - more conservative
            $allocation['fixed_income'] += 10;
            $allocation['equities'] -= 10;
        } elseif ($yearsToHorizon > 15) {
            // Long horizon - more aggressive
            $allocation['fixed_income'] -= 5;
            $allocation['equities'] += 5;
        }

        // Adjust for age (age-based risk reduction)
        $ageAdjustment = max(0, ($clientAge - 60) * 2); // Increase fixed income by 2% per year over 60
        $allocation['fixed_income'] = min(90, $allocation['fixed_income'] + $ageAdjustment);
        $allocation['equities'] = max(10, $allocation['equities'] - $ageAdjustment);

        // Adjust for market conditions
        switch ($marketCondition) {
            case self::MARKET_BEAR:
                $allocation['fixed_income'] += 10;
                $allocation['cash'] += 5;
                $allocation['equities'] -= 15;
                break;
            case self::MARKET_VOLATILE:
                $allocation['cash'] += 5;
                $allocation['equities'] -= 5;
                break;
        }

        // Adjust for goals
        switch ($primaryGoal) {
            case self::GOAL_INCOME_GENERATION:
                $allocation['fixed_income'] += 15;
                $allocation['equities'] -= 15;
                break;
            case self::GOAL_WEALTH_PRESERVATION:
                $allocation['fixed_income'] += 10;
                $allocation['cash'] += 5;
                $allocation['equities'] -= 15;
                break;
        }

        // Normalize to 100%
        $total = array_sum($allocation);
        foreach ($allocation as $asset => $percentage) {
            $allocation[$asset] = round($percentage / $total * 100, 1);
        }

        return $allocation;
    }

    /**
     * Calculate account type recommendations
     */
    private function calculateAccountTypeRecommendations(
        int $clientAge,
        float $taxBracket,
        string $timeHorizon,
        string $primaryGoal,
        float $annualIncome
    ): array {
        $recommendations = [];

        // RRSP recommendations
        if ($clientAge < 71) {
            $rrspRoom = $this->estimateRRSPRoom($annualIncome, $clientAge);
            $rrspSuitability = $this->assessRRSPSuitability($taxBracket, $timeHorizon, $primaryGoal);

            $recommendations[self::ACCOUNT_RRSP] = [
                'available' => true,
                'estimated_contribution_room' => $rrspRoom,
                'suitability_score' => $rrspSuitability,
                'recommended_allocation_percentage' => $timeHorizon === self::HORIZON_LONG ? 60 : 40,
                'tax_advantage' => 'defer',
                'withdrawal_flexibility' => 'age_71'
            ];
        }

        // TFSA recommendations
        $tfsaRoom = $this->estimateTFSARoom($clientAge);
        $tfsaSuitability = $this->assessTFSASuitability($taxBracket, $timeHorizon, $primaryGoal);

        $recommendations[self::ACCOUNT_TFSA] = [
            'available' => true,
            'estimated_contribution_room' => $tfsaRoom,
            'suitability_score' => $tfsaSuitability,
            'recommended_allocation_percentage' => 30,
            'tax_advantage' => 'none',
            'withdrawal_flexibility' => 'anytime'
        ];

        // Non-registered recommendations
        $recommendations[self::ACCOUNT_NON_REGISTERED] = [
            'available' => true,
            'estimated_contribution_room' => null, // Unlimited
            'suitability_score' => 0.7,
            'recommended_allocation_percentage' => 10,
            'tax_advantage' => 'capital_gains',
            'withdrawal_flexibility' => 'anytime'
        ];

        return $recommendations;
    }

    /**
     * Calculate retirement projection
     */
    private function calculateRetirementProjection(
        int $currentAge,
        int $retirementAge,
        float $annualIncome,
        float $annualExpenses,
        float $incomeReplacement,
        float $investableAmount,
        array $allocation,
        float $inflationRate
    ): array {
        $yearsToRetirement = $retirementAge - $currentAge;
        $retirementIncomeNeeded = $annualIncome * $incomeReplacement;

        // Adjust for inflation
        $inflationAdjustedExpenses = $annualExpenses * pow(1 + $inflationRate, $yearsToRetirement);
        $inflationAdjustedIncome = $retirementIncomeNeeded * pow(1 + $inflationRate, $yearsToRetirement);

        // Estimate required savings
        $estimatedReturn = $this->estimatePortfolioReturn($allocation, $inflationRate);
        $futureValue = $this->calculateFutureValue($investableAmount, $estimatedReturn, $yearsToRetirement);

        $annualSavingsNeeded = $this->calculateRequiredSavings(
            $inflationAdjustedIncome, $futureValue, $estimatedReturn, $yearsToRetirement
        );

        return [
            'years_to_retirement' => $yearsToRetirement,
            'current_annual_income' => $annualIncome,
            'target_retirement_income' => $retirementIncomeNeeded,
            'inflation_adjusted_target_income' => $inflationAdjustedIncome,
            'estimated_portfolio_return' => $estimatedReturn,
            'projected_portfolio_value' => $futureValue,
            'annual_savings_required' => $annualSavingsNeeded,
            'current_savings_rate' => $investableAmount > 0 ? min(100, ($annualSavingsNeeded / $annualIncome) * 100) : 0,
            'retirement_readiness_score' => $this->calculateRetirementReadiness(
                $futureValue, $inflationAdjustedIncome, $annualSavingsNeeded, $annualIncome
            )
        ];
    }

    /**
     * Calculate diversification recommendations
     */
    private function calculateDiversificationRecommendations(
        array $allocation,
        string $riskTolerance,
        string $timeHorizon
    ): array {
        $recommendations = [];

        // Geographic diversification
        $recommendations['geographic'] = [
            'canadian_equities' => 30,
            'us_equities' => 40,
            'international_equities' => 30
        ];

        // Sector diversification
        $recommendations['sector'] = [
            'technology' => 20,
            'healthcare' => 15,
            'financials' => 15,
            'consumer_goods' => 12,
            'energy' => 10,
            'industrials' => 10,
            'materials' => 8,
            'utilities' => 5,
            'communications' => 5
        ];

        // Investment vehicle mix
        if ($allocation['equities'] > 50) {
            $recommendations['vehicle_mix'] = [
                'segregated_funds' => 40,
                'etfs' => 35,
                'mutual_funds' => 25
            ];
        } else {
            $recommendations['vehicle_mix'] = [
                'segregated_funds' => 50,
                'etfs' => 30,
                'mutual_funds' => 20
            ];
        }

        return $recommendations;
    }

    /**
     * Calculate rebalancing triggers
     */
    private function calculateRebalancingTriggers(array $allocation): array
    {
        $triggers = [];

        foreach ($allocation as $asset => $targetPercentage) {
            $triggers[$asset] = [
                'target_percentage' => $targetPercentage,
                'tolerance_band' => 5, // ±5% tolerance
                'lower_trigger' => max(0, $targetPercentage - 5),
                'upper_trigger' => min(100, $targetPercentage + 5),
                'rebalance_frequency' => 'quarterly'
            ];
        }

        return $triggers;
    }

    /**
     * Generate risk considerations and warnings
     */
    private function generateRiskConsiderations(
        string $riskTolerance,
        string $timeHorizon,
        int $clientAge,
        string $marketCondition,
        bool $hasDependents
    ): array {
        $considerations = [];

        // Age-based considerations
        if ($clientAge > 65) {
            $considerations[] = "Consider gradual risk reduction approaching retirement";
        }

        // Time horizon considerations
        $yearsToHorizon = $this->convertHorizonToYears($timeHorizon);
        if ($yearsToHorizon < 3) {
            $considerations[] = "Short time horizon increases importance of capital preservation";
        }

        // Market condition warnings
        if ($marketCondition === self::MARKET_VOLATILE) {
            $considerations[] = "Current market volatility suggests conservative positioning";
        }

        // Dependent considerations
        if ($hasDependents) {
            $considerations[] = "Dependents increase need for stable, predictable returns";
        }

        // Risk tolerance alignment
        if ($riskTolerance === self::RISK_VERY_AGGRESSIVE && $clientAge > 60) {
            $considerations[] = "Very aggressive risk tolerance may not align with age and time horizon";
        }

        return $considerations;
    }

    /**
     * Calculate confidence score for recommendations
     */
    private function calculateConfidenceScore(
        int $investmentExperience,
        float $portfolioValue,
        string $timeHorizon
    ): float {
        $score = 0.5; // Base score

        // Experience factor
        $experienceFactor = min(1.0, $investmentExperience / 20); // Max at 20 years
        $score += $experienceFactor * 0.2;

        // Portfolio size factor
        $sizeFactor = min(1.0, log10($portfolioValue / 10000) / 2); // Better with larger portfolios
        $score += $sizeFactor * 0.15;

        // Time horizon factor
        $horizonYears = $this->convertHorizonToYears($timeHorizon);
        $horizonFactor = min(1.0, $horizonYears / 10); // Better with longer horizons
        $score += $horizonFactor * 0.15;

        return min(1.0, $score);
    }

    /**
     * Generate recommendation summary
     */
    private function generateRecommendationSummary(
        array $allocation,
        string $primaryGoal,
        string $riskTolerance,
        string $timeHorizon
    ): string {
        $riskLabel = ucfirst(str_replace('_', ' ', $riskTolerance));
        $goalLabel = ucfirst(str_replace('_', ' ', $primaryGoal));
        $horizonLabel = ucfirst(str_replace('_', ' ', $timeHorizon));

        $equityPercent = $allocation['equities'];
        $fixedPercent = $allocation['fixed_income'];

        return "Based on your {$riskLabel} risk tolerance and {$horizonLabel} time horizon, " .
               "we recommend a {$goalLabel}-focused portfolio with {$equityPercent}% equities and " .
               "{$fixedPercent}% fixed income. This allocation balances growth potential with " .
               "risk management appropriate for your profile.";
    }

    /**
     * Helper methods
     */
    private function convertHorizonToYears(string $horizon): int
    {
        return [
            self::HORIZON_VERY_SHORT => 1,
            self::HORIZON_SHORT => 2,
            self::HORIZON_MEDIUM => 5,
            self::HORIZON_LONG => 10,
            self::HORIZON_VERY_LONG => 20
        ][$horizon] ?? 5;
    }

    private function estimateRRSPRoom(float $annualIncome, int $age): float
    {
        // Simplified RRSP room calculation (actual calculation is more complex)
        $baseRoom = min($annualIncome * 0.18, 31020); // 2024 RRSP limit
        $ageAdjustment = max(0, (71 - $age) * 1000); // Rough adjustment
        return $baseRoom + $ageAdjustment;
    }

    private function estimateTFSARoom(int $age): float
    {
        // Simplified TFSA room calculation
        $baseRoom = 7000; // 2024 TFSA limit
        $ageAdjustment = max(0, ($age - 18) * 500); // Rough lifetime accumulation
        return $baseRoom + $ageAdjustment;
    }

    private function assessRRSPSuitability(float $taxBracket, string $timeHorizon, string $goal): float
    {
        $score = 0.5;

        if ($taxBracket > 0.3) $score += 0.2; // Higher tax bracket = more suitable
        if ($timeHorizon === self::HORIZON_LONG) $score += 0.15; // Long horizon = more suitable
        if ($goal === self::GOAL_RETIREMENT) $score += 0.15; // Retirement goal = more suitable

        return min(1.0, $score);
    }

    private function assessTFSASuitability(float $taxBracket, string $timeHorizon, string $goal): float
    {
        $score = 0.5;

        if ($taxBracket < 0.25) $score += 0.2; // Lower tax bracket = more suitable
        if ($timeHorizon === self::HORIZON_MEDIUM) $score += 0.15; // Medium horizon = suitable
        if ($goal === self::GOAL_EDUCATION) $score += 0.15; // Education goal = suitable

        return min(1.0, $score);
    }

    private function estimatePortfolioReturn(array $allocation, float $inflationRate): float
    {
        // Simplified return estimation
        $expectedReturns = [
            'equities' => 0.08, // 8% expected return
            'fixed_income' => 0.04, // 4% expected return
            'cash' => 0.02 // 2% expected return
        ];

        $weightedReturn = 0;
        foreach ($allocation as $asset => $weight) {
            $weightedReturn += ($expectedReturns[$asset] ?? 0.03) * ($weight / 100);
        }

        return $weightedReturn - $inflationRate; // Real return
    }

    private function calculateFutureValue(float $presentValue, float $rate, int $years): float
    {
        return $presentValue * pow(1 + $rate, $years);
    }

    private function calculateRequiredSavings(
        float $targetIncome,
        float $currentValue,
        float $expectedReturn,
        int $years
    ): float {
        if ($years <= 0) return $targetIncome;

        // Estimate future value needed
        $futureValueNeeded = $targetIncome / $expectedReturn;

        // Calculate annual savings required
        if ($expectedReturn <= 0) return $futureValueNeeded / $years;

        $annualSavings = ($futureValueNeeded - $currentValue) *
                        ($expectedReturn / (pow(1 + $expectedReturn, $years) - 1));

        return max(0, $annualSavings);
    }

    private function calculateRetirementReadiness(
        float $projectedValue,
        float $targetIncome,
        float $requiredSavings,
        float $currentIncome
    ): float {
        $incomeRatio = $currentIncome > 0 ? $requiredSavings / $currentIncome : 1;
        $valueRatio = $targetIncome > 0 ? $projectedValue / $targetIncome : 0;

        $score = ($valueRatio * 0.6) + ((1 - $incomeRatio) * 0.4);
        return max(0, min(1, $score));
    }

    private function calculateImplementationPriority(
        int $clientAge,
        string $timeHorizon,
        float $portfolioValue,
        string $primaryGoal
    ): string {
        $yearsToHorizon = $this->convertHorizonToYears($timeHorizon);

        if ($yearsToHorizon < 2) return 'immediate';
        if ($clientAge > 60 && $primaryGoal === self::GOAL_RETIREMENT) return 'high';
        if ($portfolioValue < 50000) return 'high';
        if ($yearsToHorizon < 5) return 'medium';

        return 'normal';
    }

    private function calculateNextReviewDate(string $timeHorizon, string $marketCondition): string
    {
        $baseMonths = [
            self::HORIZON_VERY_SHORT => 3,
            self::HORIZON_SHORT => 6,
            self::HORIZON_MEDIUM => 12,
            self::HORIZON_LONG => 12,
            self::HORIZON_VERY_LONG => 24
        ][$timeHorizon] ?? 12;

        if ($marketCondition === self::MARKET_VOLATILE) {
            $baseMonths = max(3, $baseMonths / 2);
        }

        return date('Y-m-d', strtotime("+{$baseMonths} months"));
    }
}