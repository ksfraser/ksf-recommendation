<?php

declare(strict_types=1);

namespace Ksfraser\Recommendation;

/**
 * Recommendation Engine
 *
 * Generates intelligent recommendations for insurance plan selection based on
 * comprehensive analysis of client needs, plan features, cost-benefit ratios,
 * and risk assessments.
 *
 * Single Responsibility: Generate personalized insurance plan recommendations.
 *
 * @author AI Assistant
 * @version 1.0
 * @since 7 November 2025
 */
class RecommendationEngine
{
    /**
     * Recommendation confidence levels
     */
    public const CONFIDENCE_HIGH = 'high';
    public const CONFIDENCE_MEDIUM = 'medium';
    public const CONFIDENCE_LOW = 'low';

    /**
     * Recommendation types
     */
    public const RECOMMENDATION_PRIMARY = 'primary';
    public const RECOMMENDATION_ALTERNATIVE = 'alternative';
    public const RECOMMENDATION_CONDITIONAL = 'conditional';

    /**
     * Generate comprehensive recommendations
     *
     * @param array $plans Array of plan data
     * @param array $clientProfile Client financial profile
     * @param array $policyAnalysis Policy analysis results
     * @param array $costBenefitAnalysis Cost-benefit analysis results
     * @return array Recommendation results
     */
    public function generateRecommendations(
        array $plans,
        array $clientProfile,
        array $policyAnalysis,
        array $costBenefitAnalysis
    ): array {
        $recommendations = [
            'primary_recommendation' => null,
            'alternative_recommendations' => [],
            'conditional_recommendations' => [],
            'key_findings' => [],
            'confidence_level' => self::CONFIDENCE_MEDIUM,
            'rationale' => [],
            'risk_assessment' => [],
            'next_steps' => []
        ];

        // Score and rank plans
        $planScores = $this->scoreAndRankPlans($plans, $clientProfile, $policyAnalysis, $costBenefitAnalysis);

        // Generate primary recommendation
        $recommendations['primary_recommendation'] = $this->selectPrimaryRecommendation($planScores);

        // Generate alternative recommendations
        $recommendations['alternative_recommendations'] = $this->selectAlternativeRecommendations($planScores);

        // Generate conditional recommendations
        $recommendations['conditional_recommendations'] = $this->generateConditionalRecommendations(
            $plans,
            $clientProfile,
            $planScores
        );

        // Extract key findings
        $recommendations['key_findings'] = $this->extractKeyFindings($policyAnalysis, $costBenefitAnalysis);

        // Calculate confidence level
        $recommendations['confidence_level'] = $this->calculateConfidenceLevel($planScores, $clientProfile);

        // Generate rationale
        $recommendations['rationale'] = $this->generateRationale($planScores, $clientProfile);

        // Risk assessment
        $recommendations['risk_assessment'] = $this->performRiskAssessment($plans, $clientProfile, $planScores);

        // Next steps
        $recommendations['next_steps'] = $this->generateNextSteps($recommendations);

        return $recommendations;
    }

    /**
     * Score and rank plans based on multiple criteria
     *
     * @param array $plans Array of plans
     * @param array $clientProfile Client profile
     * @param array $policyAnalysis Policy analysis
     * @param array $costBenefitAnalysis Cost-benefit analysis
     * @return array Scored and ranked plans
     */
    private function scoreAndRankPlans(
        array $plans,
        array $clientProfile,
        array $policyAnalysis,
        array $costBenefitAnalysis
    ): array {
        $scoredPlans = [];

        foreach ($plans as $index => $plan) {
            $planId = $plan['id'] ?? 'plan_' . $index;

            $score = $this->calculateComprehensiveScore(
                $plan,
                $clientProfile,
                $policyAnalysis,
                $costBenefitAnalysis
            );

            $scoredPlans[$planId] = [
                'plan' => $plan,
                'total_score' => $score['total'],
                'component_scores' => $score,
                'rank' => 0 // Will be set after sorting
            ];
        }

        // Sort by total score (descending)
        arsort($scoredPlans);

        // Assign ranks
        $rank = 1;
        foreach ($scoredPlans as $planId => &$planData) {
            $planData['rank'] = $rank++;
        }

        return $scoredPlans;
    }

    /**
     * Calculate comprehensive score for a plan
     *
     * @param array $plan Plan data
     * @param array $clientProfile Client profile
     * @param array $policyAnalysis Policy analysis
     * @param array $costBenefitAnalysis Cost-benefit analysis
     * @return array Component scores and total
     */
    private function calculateComprehensiveScore(
        array $plan,
        array $clientProfile,
        array $policyAnalysis,
        array $costBenefitAnalysis
    ): array {
        $planId = $plan['id'] ?? 'unknown';

        // Coverage adequacy (0-100)
        $coverageScore = $this->calculateCoverageScore($plan, $clientProfile);

        // Cost efficiency (0-100, inverted so lower cost = higher score)
        $costEfficiencyScore = $this->calculateCostEfficiencyScore($costBenefitAnalysis, $planId);

        // Affordability (0-100)
        $affordabilityScore = $this->calculateAffordabilityScore($costBenefitAnalysis, $planId);

        // Feature richness (0-100)
        $featureScore = $this->calculateFeatureScore($plan);

        // Risk-adjusted value (0-100)
        $riskAdjustedScore = $this->calculateRiskAdjustedScore($plan, $clientProfile);

        // Weighted total score
        $totalScore = (
            $coverageScore * 0.35 +          // 35% weight on coverage
            $costEfficiencyScore * 0.25 +    // 25% weight on cost efficiency
            $affordabilityScore * 0.20 +     // 20% weight on affordability
            $featureScore * 0.10 +           // 10% weight on features
            $riskAdjustedScore * 0.10        // 10% weight on risk adjustment
        );

        return [
            'total' => $totalScore,
            'coverage' => $coverageScore,
            'cost_efficiency' => $costEfficiencyScore,
            'affordability' => $affordabilityScore,
            'features' => $featureScore,
            'risk_adjusted' => $riskAdjustedScore
        ];
    }

    /**
     * Calculate coverage adequacy score
     *
     * @param array $plan Plan data
     * @param array $clientProfile Client profile
     * @return float Coverage score (0-100)
     */
    private function calculateCoverageScore(array $plan, array $clientProfile): float
    {
        $requiredCoverage = $clientProfile['required_coverage'] ?? [];
        if (empty($requiredCoverage)) {
            return 50.0; // Neutral score if no requirements specified
        }

        $totalScore = 0.0;
        $coverageTypes = 0;

        foreach ($requiredCoverage as $type => $required) {
            $provided = $plan['coverage'][$type] ?? 0;
            $coverageTypes++;

            if ($provided >= $required) {
                $totalScore += 100.0; // Full coverage
            } elseif ($provided > 0) {
                $totalScore += ($provided / $required) * 100.0; // Partial coverage
            }
            // 0 if no coverage provided
        }

        return $coverageTypes > 0 ? $totalScore / $coverageTypes : 50.0;
    }

    /**
     * Calculate cost efficiency score
     *
     * @param array $costBenefitAnalysis Cost-benefit analysis
     * @param string $planId Plan identifier
     * @return float Cost efficiency score (0-100)
     */
    private function calculateCostEfficiencyScore(array $costBenefitAnalysis, string $planId): float
    {
        $planAnalysis = $costBenefitAnalysis['detailed_analysis'][$planId] ?? [];
        $costBenefitRatio = $planAnalysis['cost_benefit_ratio'] ?? 0;

        // Invert the ratio so lower cost = higher score
        // Assuming good ratio is < 5.0, excellent is < 1.0
        if ($costBenefitRatio <= 0) return 50.0;
        if ($costBenefitRatio < 1.0) return 100.0;
        if ($costBenefitRatio < 2.0) return 80.0;
        if ($costBenefitRatio < 5.0) return 60.0;
        if ($costBenefitRatio < 10.0) return 30.0;
        return 10.0;
    }

    /**
     * Calculate affordability score
     *
     * @param array $costBenefitAnalysis Cost-benefit analysis
     * @param string $planId Plan identifier
     * @return float Affordability score (0-100)
     */
    private function calculateAffordabilityScore(array $costBenefitAnalysis, string $planId): float
    {
        $planAnalysis = $costBenefitAnalysis['detailed_analysis'][$planId] ?? [];
        $annualCostPercentage = $planAnalysis['annual_cost_percentage'] ?? 0;

        // Lower percentage = higher score
        if ($annualCostPercentage < 1.0) return 100.0;
        if ($annualCostPercentage < 3.0) return 80.0;
        if ($annualCostPercentage < 5.0) return 60.0;
        if ($annualCostPercentage < 8.0) return 40.0;
        if ($annualCostPercentage < 12.0) return 20.0;
        return 10.0;
    }

    /**
     * Calculate feature richness score
     *
     * @param array $plan Plan data
     * @return float Feature score (0-100)
     */
    private function calculateFeatureScore(array $plan): float
    {
        $features = $plan['features'] ?? [];
        $featureCount = count($features);

        // Score based on number of features
        if ($featureCount >= 5) return 100.0;
        if ($featureCount >= 3) return 75.0;
        if ($featureCount >= 2) return 50.0;
        if ($featureCount >= 1) return 25.0;
        return 10.0;
    }

    /**
     * Calculate risk-adjusted score
     *
     * @param array $plan Plan data
     * @param array $clientProfile Client profile
     * @return float Risk-adjusted score (0-100)
     */
    private function calculateRiskAdjustedScore(array $plan, array $clientProfile): float
    {
        $baseScore = 50.0; // Neutral starting point

        // Adjust based on risk tolerance
        $riskTolerance = $clientProfile['risk_tolerance'] ?? 'moderate';

        switch ($riskTolerance) {
            case 'conservative':
                // Conservative clients prefer guaranteed benefits
                if (in_array('guaranteed', $plan['features'] ?? [])) {
                    $baseScore += 20;
                }
                break;
            case 'moderate':
                // Moderate clients balance risk and reward
                $baseScore += 10;
                break;
            case 'aggressive':
                // Aggressive clients may prefer higher risk/reward
                if (in_array('investment_component', $plan['features'] ?? [])) {
                    $baseScore += 15;
                }
                break;
        }

        // Adjust based on client age (younger clients have more time to benefit)
        $age = $clientProfile['age'] ?? 30;
        if ($age < 35) $baseScore += 10;
        elseif ($age > 55) $baseScore -= 10;

        return max(0.0, min(100.0, $baseScore));
    }

    /**
     * Select primary recommendation
     *
     * @param array $planScores Scored plans
     * @return array|null Primary recommendation
     */
    private function selectPrimaryRecommendation(array $planScores): ?array
    {
        if (empty($planScores)) {
            return null;
        }

        // Get the highest scoring plan
        $topPlan = reset($planScores);

        return [
            'plan_id' => key($planScores),
            'plan_name' => $topPlan['plan']['name'] ?? 'Unnamed Plan',
            'confidence_score' => $topPlan['total_score'],
            'rank' => $topPlan['rank']
        ];
    }

    /**
     * Select alternative recommendations
     *
     * @param array $planScores Scored plans
     * @return array Alternative recommendations
     */
    private function selectAlternativeRecommendations(array $planScores): array
    {
        $alternatives = [];
        $count = 0;

        foreach ($planScores as $planId => $planData) {
            if ($count >= 2) break; // Limit to 2 alternatives
            if ($planData['rank'] > 1) { // Skip the primary recommendation
                $alternatives[] = [
                    'plan_id' => $planId,
                    'plan_name' => $planData['plan']['name'] ?? 'Unnamed Plan',
                    'confidence_score' => $planData['total_score'],
                    'rank' => $planData['rank'],
                    'score_difference' => $planScores[array_key_first($planScores)]['total_score'] - $planData['total_score']
                ];
                $count++;
            }
        }

        return $alternatives;
    }

    /**
     * Generate conditional recommendations
     *
     * @param array $plans Array of plans
     * @param array $clientProfile Client profile
     * @param array $planScores Scored plans
     * @return array Conditional recommendations
     */
    private function generateConditionalRecommendations(array $plans, array $clientProfile, array $planScores): array
    {
        $conditionals = [];

        // Budget constraint conditional
        $budgetConstraint = $clientProfile['budget_constraint'] ?? null;
        if ($budgetConstraint) {
            $affordablePlans = array_filter($planScores, function($planData) use ($budgetConstraint) {
                $annualPremium = $planData['plan']['premiums']['annual'] ?? 0;
                return $annualPremium <= $budgetConstraint;
            });

            if (!empty($affordablePlans)) {
                $bestAffordable = reset($affordablePlans);
                $conditionals[] = [
                    'type' => 'budget_constraint',
                    'condition' => "Annual premium ≤ $" . number_format($budgetConstraint),
                    'recommended_plan' => key($affordablePlans),
                    'plan_name' => $bestAffordable['plan']['name'] ?? 'Unnamed Plan'
                ];
            }
        }

        // Health condition conditional
        $healthConditions = $clientProfile['health_conditions'] ?? [];
        if (!empty($healthConditions)) {
            // Recommend plans with critical illness coverage for clients with health conditions
            $ciPlans = array_filter($planScores, function($planData) {
                return in_array('critical_illness', $planData['plan']['coverage_types'] ?? []);
            });

            if (!empty($ciPlans)) {
                $bestCiPlan = reset($ciPlans);
                $conditionals[] = [
                    'type' => 'health_condition',
                    'condition' => 'Client has pre-existing health conditions',
                    'recommended_plan' => key($ciPlans),
                    'plan_name' => $bestCiPlan['plan']['name'] ?? 'Unnamed Plan',
                    'reason' => 'Includes critical illness coverage'
                ];
            }
        }

        return $conditionals;
    }

    /**
     * Extract key findings from analysis
     *
     * @param array $policyAnalysis Policy analysis
     * @param array $costBenefitAnalysis Cost-benefit analysis
     * @return array Key findings
     */
    private function extractKeyFindings(array $policyAnalysis, array $costBenefitAnalysis): array
    {
        $findings = [];

        // Coverage gaps
        $coverageGaps = $policyAnalysis['coverage_gaps'] ?? [];
        if (!empty($coverageGaps)) {
            $findings[] = 'Coverage gaps identified: ' . count($coverageGaps) . ' areas need additional protection';
        }

        // Cost efficiency insights
        $summary = $costBenefitAnalysis['summary'] ?? [];
        $avgCostBenefitRatio = $summary['average_cost_benefit_ratio'] ?? 0;
        if ($avgCostBenefitRatio > 0) {
            if ($avgCostBenefitRatio < 2.0) {
                $findings[] = 'Excellent value identified with average cost-benefit ratio below 2.0';
            } elseif ($avgCostBenefitRatio > 5.0) {
                $findings[] = 'High cost-benefit ratios suggest need for more competitive options';
            }
        }

        // Affordability insights
        $avgAffordability = $summary['average_affordability'] ?? 0;
        if ($avgAffordability > 8.0) {
            $findings[] = 'Premiums may exceed recommended 5-8% of income for some options';
        }

        return $findings;
    }

    /**
     * Calculate confidence level of recommendations
     *
     * @param array $planScores Scored plans
     * @param array $clientProfile Client profile
     * @return string Confidence level
     */
    private function calculateConfidenceLevel(array $planScores, array $clientProfile): string
    {
        if (empty($planScores)) {
            return self::CONFIDENCE_LOW;
        }

        $topScore = reset($planScores)['total_score'];
        $scoreSpread = $topScore - (end($planScores)['total_score'] ?? 0);

        // High confidence if clear winner and good data quality
        if ($topScore > 70 && $scoreSpread > 20) {
            return self::CONFIDENCE_HIGH;
        }

        // Medium confidence for moderate differentiation
        if ($topScore > 50 && $scoreSpread > 10) {
            return self::CONFIDENCE_MEDIUM;
        }

        return self::CONFIDENCE_LOW;
    }

    /**
     * Generate rationale for recommendations
     *
     * @param array $planScores Scored plans
     * @param array $clientProfile Client profile
     * @return array Rationale explanations
     */
    private function generateRationale(array $planScores, array $clientProfile): array
    {
        if (empty($planScores)) {
            return ['Insufficient data for comprehensive analysis'];
        }

        $rationale = [];
        $topPlan = reset($planScores);
        $topPlanData = $topPlan['plan'];

        // Coverage rationale
        $coverageScore = $topPlan['component_scores']['coverage'];
        if ($coverageScore > 80) {
            $rationale[] = 'Excellent coverage match for client protection needs';
        } elseif ($coverageScore > 60) {
            $rationale[] = 'Good coverage alignment with client requirements';
        }

        // Cost rationale
        $costEfficiencyScore = $topPlan['component_scores']['cost_efficiency'];
        if ($costEfficiencyScore > 80) {
            $rationale[] = 'Highly cost-effective option providing strong value';
        } elseif ($costEfficiencyScore > 60) {
            $rationale[] = 'Competitive pricing with good coverage value';
        }

        // Affordability rationale
        $affordabilityScore = $topPlan['component_scores']['affordability'];
        if ($affordabilityScore > 80) {
            $rationale[] = 'Highly affordable within client budget constraints';
        }

        // Risk-adjusted rationale
        $riskTolerance = $clientProfile['risk_tolerance'] ?? 'moderate';
        $rationale[] = "Well-suited for {$riskTolerance} risk tolerance profile";

        return $rationale;
    }

    /**
     * Perform risk assessment
     *
     * @param array $plans Array of plans
     * @param array $clientProfile Client profile
     * @param array $planScores Scored plans
     * @return array Risk assessment results
     */
    private function performRiskAssessment(array $plans, array $clientProfile, array $planScores): array
    {
        $assessment = [
            'overall_risk_level' => 'medium',
            'coverage_gaps_risk' => 'low',
            'affordability_risk' => 'low',
            'market_risk' => 'low',
            'recommendations' => []
        ];

        // Assess coverage gaps risk
        $coverageGaps = $this->countCoverageGaps($plans, $clientProfile);
        if ($coverageGaps > 2) {
            $assessment['coverage_gaps_risk'] = 'high';
            $assessment['recommendations'][] = 'Address significant coverage gaps with additional protection';
        } elseif ($coverageGaps > 0) {
            $assessment['coverage_gaps_risk'] = 'medium';
            $assessment['recommendations'][] = 'Consider supplemental coverage for identified gaps';
        }

        // Assess affordability risk
        $highCostPlans = array_filter($planScores, function($planData) {
            return $planData['component_scores']['affordability'] < 40;
        });
        if (count($highCostPlans) > 1) {
            $assessment['affordability_risk'] = 'high';
            $assessment['recommendations'][] = 'Multiple options exceed affordability thresholds';
        }

        // Calculate overall risk level
        $riskLevels = [$assessment['coverage_gaps_risk'], $assessment['affordability_risk'], $assessment['market_risk']];
        if (in_array('high', $riskLevels)) {
            $assessment['overall_risk_level'] = 'high';
        } elseif (in_array('medium', $riskLevels)) {
            $assessment['overall_risk_level'] = 'medium';
        } else {
            $assessment['overall_risk_level'] = 'low';
        }

        return $assessment;
    }

    /**
     * Count coverage gaps
     *
     * @param array $plans Array of plans
     * @param array $clientProfile Client profile
     * @return int Number of coverage gaps
     */
    private function countCoverageGaps(array $plans, array $clientProfile): int
    {
        $requiredCoverage = $clientProfile['required_coverage'] ?? [];
        $gaps = 0;

        foreach ($requiredCoverage as $type => $required) {
            $maxCoverage = 0;
            foreach ($plans as $plan) {
                $maxCoverage = max($maxCoverage, $plan['coverage'][$type] ?? 0);
            }
            if ($maxCoverage < $required) {
                $gaps++;
            }
        }

        return $gaps;
    }

    /**
     * Generate next steps recommendations
     *
     * @param array $recommendations Complete recommendations
     * @return array Next steps
     */
    private function generateNextSteps(array $recommendations): array
    {
        $nextSteps = [];

        // Primary recommendation action
        if ($recommendations['primary_recommendation']) {
            $nextSteps[] = 'Review ' . $recommendations['primary_recommendation']['plan_name'] . ' policy details and application process';
        }

        // Conditional recommendations
        if (!empty($recommendations['conditional_recommendations'])) {
            $nextSteps[] = 'Evaluate conditional recommendations based on specific circumstances';
        }

        // Risk mitigation
        $riskAssessment = $recommendations['risk_assessment'];
        if ($riskAssessment['overall_risk_level'] === 'high') {
            $nextSteps[] = 'Address high-risk items before proceeding with policy selection';
        }

        // Documentation and review
        $nextSteps[] = 'Schedule follow-up meeting to review detailed policy illustrations';
        $nextSteps[] = 'Prepare comprehensive needs analysis documentation';

        return $nextSteps;
    }
}